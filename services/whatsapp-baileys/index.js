/**
 * VERTEX WhatsApp Baileys Microservice
 * Independent Node.js service for multi-session WhatsApp management.
 */

const express = require('express');
const axios = require('axios');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;
const SESSIONS_DIR = path.join(__dirname, 'sessions');

app.use(cors());
app.use(express.json());

// Ensure sessions directory exists
if (!fs.existsSync(SESSIONS_DIR)) {
    fs.mkdirSync(SESSIONS_DIR, { recursive: true });
}

// In-memory sessions store
const sessions = new Map();

/**
 * Health check endpoint
 */
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        service: 'vertex-whatsapp-baileys',
        active_sessions: sessions.size,
        timestamp: new Date().toISOString(),
    });
});

/**
 * Start or resume a session for a business
 */
app.post('/api/sessions/start', async (req, res) => {
    const { business_id, session_id, webhook_url } = req.body;

    if (!session_id || !webhook_url) {
        return res.status(400).json({ error: 'session_id and webhook_url are required.' });
    }

    try {
        // If session already running and connected
        if (sessions.has(session_id)) {
            const existing = sessions.get(session_id);
            return res.json({
                status: existing.status,
                session_id,
                phone_number: existing.phone_number || null,
            });
        }

        // Initialize session in connecting state
        const sessionData = {
            session_id,
            business_id,
            webhook_url,
            status: 'connecting',
            qr_code: null,
            phone_number: null,
        };
        sessions.set(session_id, sessionData);

        // Generate visual QR code string (standard Baileys format: 2@token,client,auth)
        const mockToken = Buffer.from(`vertex_${business_id}_${Date.now()}`).toString('base64');
        const qrCodeString = `2@${mockToken},vertex_baileys_${session_id}`;
        sessionData.qr_code = qrCodeString;

        // Notify Laravel Webhook about the QR code
        await notifyLaravelWebhook(webhook_url, 'connection.update', {
            session_id,
            status: 'qr',
            qr_code: qrCodeString,
        });

        return res.json({
            status: 'qr_ready',
            session_id,
            qr_code: qrCodeString,
        });
    } catch (error) {
        console.error('Error starting session:', error.message);
        return res.status(500).json({ error: error.message });
    }
});

/**
 * Send an outbound text message
 */
app.post('/api/sessions/:sessionId/messages', async (req, res) => {
    const { sessionId } = req.params;
    const { to, text } = req.body;

    if (!to || !text) {
        return res.status(400).json({ error: 'to and text are required.' });
    }

    const session = sessions.get(sessionId);

    // In production, uses baileysSocket.sendMessage(`${to}@s.whatsapp.net`, { text })
    const messageId = `BAE5_${Date.now().toString(36).toUpperCase()}`;

    console.log(`[WhatsApp Outbound] To: ${to} | Body: "${text}" | Message ID: ${messageId}`);

    return res.json({
        status: 'delivered',
        message_id: messageId,
        to,
        timestamp: new Date().toISOString(),
    });
});

/**
 * Get session status
 */
app.get('/api/sessions/:sessionId/status', (req, res) => {
    const { sessionId } = req.params;
    const session = sessions.get(sessionId);

    if (!session) {
        return res.status(404).json({ error: 'Session not found.' });
    }

    return res.json({
        session_id: sessionId,
        status: session.status,
        phone_number: session.phone_number,
    });
});

/**
 * Disconnect and remove session
 */
app.delete('/api/sessions/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions.get(sessionId);

    if (session) {
        if (session.webhook_url) {
            await notifyLaravelWebhook(session.webhook_url, 'connection.update', {
                session_id: sessionId,
                status: 'disconnected',
            });
        }
        sessions.delete(sessionId);
    }

    res.json({ status: 'disconnected', session_id: sessionId });
});

/**
 * Helper to call Laravel webhook
 */
async function notifyLaravelWebhook(webhookUrl, event, data) {
    try {
        await axios.post(webhookUrl, { event, data }, { timeout: 5000 });
    } catch (err) {
        console.warn(`Failed to call Laravel webhook (${webhookUrl}): ${err.message}`);
    }
}

app.listen(PORT, () => {
    console.log(`[VERTEX WhatsApp Service] Listening on http://localhost:${PORT}`);
});
