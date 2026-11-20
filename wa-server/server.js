const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');
const pino = require('pino');
const qrcode = require('qrcode');
const {
    default: makeWASocket,
    fetchLatestBaileysVersion,
    DisconnectReason,
    Browsers,
    initAuthCreds,
    BufferJSON,
} = require('@whiskeysockets/baileys');

const app = express();
const allowedOrigins = process.env.CORS_ORIGIN ? process.env.CORS_ORIGIN.split(',') : false;
app.use(cors(allowedOrigins ? { origin: allowedOrigins } : { origin: false }));
app.use(express.json());

const PORT = process.env.PORT || 3000;
const API_KEY = process.env.WHATSAPP_SERVER_API_KEY || process.env.API_KEY;

if (!API_KEY || API_KEY === 'default_wa_secret_key') {
    console.error('FATAL: A secure WHATSAPP_SERVER_API_KEY or API_KEY environment variable is required.');
    process.exit(1);
}

const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL || 'http://127.0.0.1:8000';
const SESSIONS_DIR = path.join(__dirname, 'sessions');

if (!fs.existsSync(SESSIONS_DIR)) {
    fs.mkdirSync(SESSIONS_DIR, { recursive: true });
}

const LOGGER = pino({ level: 'silent' });

function sanitizeSessionId(sessionId) {
    if (!sessionId || typeof sessionId !== 'string') return null;
    const clean = sessionId.trim();
    if (!/^[a-zA-Z0-9_-]{1,64}$/.test(clean)) return null;
    return clean;
}

app.param('sessionId', (req, res, next, id) => {
    const clean = sanitizeSessionId(id);
    if (!clean) {
        return res.status(400).json({ error: 'Invalid sessionId parameter' });
    }
    req.params.sessionId = clean;
    next();
});

// Global active sessions map: sessionId -> { sock, qr, qrImage, status, businessId, phoneNumber, userJid, authHelper }
const sessions = new Map();

/**
 * Middleware: Verify x-api-key
 */
const authenticate = (req, res, next) => {
    const providedKey = req.headers['x-api-key'] || req.headers['authorization']?.replace(/^Bearer\s+/, '');
    if (!providedKey || providedKey !== API_KEY) {
        return res.status(401).json({ error: 'Unauthorized: Invalid or missing API key' });
    }
    next();
};

/**
 * Debounced In-Memory + Single-File Atomic JSON Auth State
 * Prevents Signal pre-key filesystem thrashing and persists session metadata
 */
function useDebouncedAuthState(sessionId) {
    const sessionFile = path.join(SESSIONS_DIR, `${sessionId}_auth.json`);
    let creds;
    let keys = {};
    let meta = { sessionId };
    let saveTimeout = null;

    if (fs.existsSync(sessionFile)) {
        try {
            const raw = fs.readFileSync(sessionFile, 'utf-8');
            const parsed = JSON.parse(raw, BufferJSON.reviver);
            creds = parsed.creds || initAuthCreds();
            keys = parsed.keys || {};
            meta = parsed.meta || { sessionId };
        } catch (e) {
            creds = initAuthCreds();
            keys = {};
            meta = { sessionId };
        }
    } else {
        creds = initAuthCreds();
        keys = {};
    }

    const flushSync = () => {
        if (saveTimeout) {
            clearTimeout(saveTimeout);
            saveTimeout = null;
        }
        try {
            const tmpFile = `${sessionFile}.tmp`;
            const serialized = JSON.stringify({ creds, keys, meta }, BufferJSON.replacer);
            fs.writeFileSync(tmpFile, serialized, 'utf-8');
            fs.renameSync(tmpFile, sessionFile);
        } catch (err) {
            // write error fallback
        }
    };

    const scheduleSave = () => {
        if (saveTimeout) return;
        saveTimeout = setTimeout(() => {
            saveTimeout = null;
            flushSync();
        }, 5000);
    };

    const state = {
        creds,
        keys: {
            get: (type, ids) => {
                const data = {};
                for (const id of ids) {
                    const value = keys[`${type}-${id}`];
                    if (value) {
                        data[id] = value;
                    }
                }
                return data;
            },
            set: (data) => {
                for (const category in data) {
                    for (const id in data[category]) {
                        const value = data[category][id];
                        const key = `${category}-${id}`;
                        if (value) {
                            keys[key] = value;
                        } else {
                            delete keys[key];
                        }
                    }
                }
                scheduleSave();
            },
        },
    };

    return {
        state,
        meta,
        saveCreds: () => scheduleSave(),
        flushSync,
        updateMeta: (newMeta) => {
            meta = { ...meta, ...newMeta };
            scheduleSave();
        },
    };
}

/**
 * Forward webhook events to Laravel
 */
async function forwardWebhook(businessId, event, data) {
    if (!businessId) return;
    try {
        const url = `${LARAVEL_WEBHOOK_URL.replace(/\/$/, '')}/webhooks/whatsapp/${businessId}`;
        await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'x-api-key': API_KEY,
            },
            body: JSON.stringify({ event, data }),
        });
    } catch (e) {
        // network error
    }
}

/**
 * Start or boot a WhatsApp socket session with low-RAM configuration
 */
async function createSession(sessionId, businessId = null) {
    const cleanSessionId = sanitizeSessionId(sessionId);
    if (!cleanSessionId) {
        throw new Error('Invalid sessionId');
    }

    if (sessions.has(cleanSessionId)) {
        const existing = sessions.get(cleanSessionId);
        if (businessId && !existing.businessId) existing.businessId = businessId;
        if (existing.authHelper) {
            existing.authHelper.updateMeta({ businessId: existing.businessId });
        }
        if (existing.sock && existing.status === 'connected') return existing;
        if (existing.sock && existing.status !== 'connected') {
            try {
                existing.sock.end();
            } catch (e) {}
        }
    }

    const authHelper = useDebouncedAuthState(cleanSessionId);
    const { state, saveCreds, flushSync, updateMeta } = authHelper;
    
    // Merge provided metadata with stored metadata
    const finalBusinessId = businessId || authHelper.meta?.businessId || null;
    updateMeta({ sessionId: cleanSessionId, businessId: finalBusinessId });

    let version;
    try {
        const v = await fetchLatestBaileysVersion();
        version = v.version;
    } catch (e) {
        version = [2, 3000, 1015901307];
    }

    const sock = makeWASocket({
        version,
        auth: state,
        logger: LOGGER,
        printQRInTerminal: false,
        browser: Browsers.ubuntu('Chrome'),
        connectTimeoutMs: 45000,
        keepAliveIntervalMs: 30000,
        syncFullHistory: false,
        markOnlineOnConnect: false,
        fireInitQueries: false,
        shouldSyncHistoryMessage: () => false,
        shouldIgnoreJid: (jid) => !jid || jid === 'status@broadcast' || jid.endsWith('@broadcast') || jid.endsWith('@newsletter') || jid.endsWith('@g.us'),
        getMessage: async () => undefined,
    });

    const sessionObj = {
        sock,
        qr: null,
        qrImage: null,
        status: 'connecting',
        businessId: finalBusinessId,
        phoneNumber: authHelper.meta?.phoneNumber || null,
        userJid: null,
        authHelper,
    };
    sessions.set(cleanSessionId, sessionObj);

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            sessionObj.qr = qr;
            sessionObj.status = 'scan_qr';
            try {
                sessionObj.qrImage = await qrcode.toDataURL(qr);
            } catch (e) {
                sessionObj.qrImage = null;
            }

            await forwardWebhook(sessionObj.businessId, 'connection.update', {
                status: 'connecting',
                qr_code: sessionObj.qr,
                qr_image: sessionObj.qrImage,
                sessionId: cleanSessionId,
                session_id: cleanSessionId,
            });
        }

        if (connection === 'open') {
            sessionObj.status = 'connected';
            sessionObj.qr = null;
            sessionObj.qrImage = null;
            sessionObj.userJid = sock.user?.id || null;
            if (sock.user?.id) {
                sessionObj.phoneNumber = sock.user.id.split('@')[0].split(':')[0];
                updateMeta({ phoneNumber: sessionObj.phoneNumber });
            }
            flushSync();

            await forwardWebhook(sessionObj.businessId, 'connection.update', {
                status: 'connected',
                user: sock.user,
                phone_number: sessionObj.phoneNumber,
                phoneNumber: sessionObj.phoneNumber,
                sessionId: cleanSessionId,
                session_id: cleanSessionId,
            });
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            sessionObj.status = 'disconnected';
            sessionObj.qr = null;
            sessionObj.qrImage = null;

            await forwardWebhook(sessionObj.businessId, 'connection.update', {
                status: 'disconnected',
                statusCode,
                sessionId: cleanSessionId,
                session_id: cleanSessionId,
            });

            if (shouldReconnect) {
                setTimeout(() => createSession(cleanSessionId, sessionObj.businessId), 5000);
            } else {
                sessions.delete(cleanSessionId);
            }
        }
    });

    sock.ev.on('messages.upsert', async (upsert) => {
        if (upsert.type === 'notify') {
            for (const msg of upsert.messages) {
                if (msg.key.fromMe) continue;
                if (!msg.message) continue;

                const senderJid = msg.key.remoteJid;
                if (!senderJid || senderJid === 'status@broadcast' || senderJid.endsWith('@broadcast') || senderJid.endsWith('@newsletter') || senderJid.endsWith('@g.us')) {
                    continue;
                }

                const text = msg.message.conversation ||
                    msg.message.extendedTextMessage?.text ||
                    msg.message.imageMessage?.caption ||
                    '';

                await forwardWebhook(sessionObj.businessId, 'messages.upsert', {
                    sessionId: cleanSessionId,
                    session_id: cleanSessionId,
                    message_id: msg.key.id,
                    from: senderJid,
                    push_name: msg.pushName || null,
                    text,
                    timestamp: msg.messageTimestamp,
                    account_phone_number: sessionObj.phoneNumber,
                    raw: msg,
                });
            }
        }
    });

    return sessionObj;
}

/**
 * Staggered Auto-Boot Queue (eliminates CPU/RAM spikes on boot)
 */
async function autoBootSessions() {
    try {
        const files = fs.readdirSync(SESSIONS_DIR);
        const authFiles = files.filter((f) => f.endsWith('_auth.json'));

        for (const file of authFiles) {
            try {
                const sessionFile = path.join(SESSIONS_DIR, file);
                const raw = fs.readFileSync(sessionFile, 'utf-8');
                const parsed = JSON.parse(raw, BufferJSON.reviver);
                const meta = parsed.meta || {};
                const rawSessionId = meta.sessionId || file.replace('_auth.json', '');
                const sessionId = sanitizeSessionId(rawSessionId);
                const businessId = meta.businessId || null;

                if (sessionId) {
                    await createSession(sessionId, businessId);
                    await new Promise((resolve) => setTimeout(resolve, 2000));
                }
            } catch (err) {
                // skip corrupted session
            }
        }
    } catch (e) {
        // sessions dir empty or unreadable
    }
}

// ---------------------------------------------------------------------
// REST API ROUTES
// ---------------------------------------------------------------------

app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        activeSessions: sessions.size,
        uptime: process.uptime(),
    });
});

/**
 * SuperAdmin WhatsApp Sessions Overview
 */
app.get('/api/sessions', authenticate, (req, res) => {
    const sessionsMap = {};
    for (const [id, s] of sessions.entries()) {
        sessionsMap[id] = {
            sessionId: id,
            session_id: id,
            status: s.status,
            user: s.userJid,
            phoneNumber: s.phoneNumber || (s.userJid ? s.userJid.split('@')[0].split(':')[0] : null),
            phone_number: s.phoneNumber || (s.userJid ? s.userJid.split('@')[0].split(':')[0] : null),
            businessId: s.businessId,
            business_id: s.businessId,
        };
    }

    res.json({
        sessions: sessionsMap,
        active_instances: Array.from(sessions.keys()),
        count: sessions.size,
    });
});

/**
 * Start/Initialize WhatsApp Session
 * Supports snake_case and camelCase parameters.
 * Waits up to 6s for QR generation.
 */
app.post('/api/sessions/start', authenticate, async (req, res) => {
    const rawSessionId = req.body.session_id || req.body.sessionId;
    const sessionId = sanitizeSessionId(rawSessionId);
    const businessId = req.body.business_id || req.body.businessId || null;

    if (!sessionId) {
        return res.status(400).json({ error: 'Valid alphanumeric sessionId is required' });
    }

    try {
        const session = await createSession(sessionId, businessId);

        if (session.status === 'connected') {
            return res.json({
                status: 'connected',
                session_id: sessionId,
                sessionId,
                qr_code: null,
                qr_image: null,
                phone_number: session.phoneNumber,
            });
        }

        // Wait up to 6 seconds for QR code generation if not immediately ready
        const startTime = Date.now();
        while (!session.qr && session.status !== 'connected' && Date.now() - startTime < 6000) {
            await new Promise((r) => setTimeout(r, 400));
        }

        let qrImage = session.qrImage;
        if (session.qr && !qrImage) {
            try {
                qrImage = await qrcode.toDataURL(session.qr);
                session.qrImage = qrImage;
            } catch (e) {
                qrImage = null;
            }
        }

        res.json({
            status: session.status,
            session_id: sessionId,
            sessionId,
            qr_code: session.qr,
            qr_image: qrImage,
            qr: session.qr,
            phone_number: session.phoneNumber,
        });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

/**
 * 8-Digit Pairing Code Generation
 */
app.post('/api/sessions/pairing-code', authenticate, async (req, res) => {
    const rawSessionId = req.body.session_id || req.body.sessionId;
    const sessionId = sanitizeSessionId(rawSessionId);
    const businessId = req.body.business_id || req.body.businessId || null;
    const phoneNumber = req.body.phone_number || req.body.phoneNumber;

    if (!sessionId) {
        return res.status(400).json({ error: 'Valid alphanumeric sessionId is required' });
    }
    if (!phoneNumber) {
        return res.status(400).json({ error: 'phone_number is required' });
    }

    const cleanPhone = String(phoneNumber).replace(/[^0-9]/g, '');

    try {
        const session = await createSession(sessionId, businessId);

        if (session.status === 'connected') {
            return res.json({
                status: 'already_connected',
                session_id: sessionId,
                phone_number: session.phoneNumber,
            });
        }

        // Wait briefly for socket handshake readiness if just created
        let attempts = 0;
        while (!session.sock?.requestPairingCode && attempts < 15) {
            await new Promise((r) => setTimeout(r, 300));
            attempts++;
        }

        if (!session.sock?.requestPairingCode) {
            return res.status(500).json({ error: 'Socket initialization failed' });
        }

        const rawCode = await session.sock.requestPairingCode(cleanPhone);
        const formattedCode = rawCode?.match(/.{1,4}/g)?.join('-') || rawCode;

        res.json({
            status: 'pairing_code_ready',
            session_id: sessionId,
            sessionId,
            pairing_code: formattedCode,
            raw_code: rawCode,
            phone_number: cleanPhone,
        });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

/**
 * Session Status
 */
app.get('/api/sessions/:sessionId/status', authenticate, (req, res) => {
    const { sessionId } = req.params;
    const session = sessions.get(sessionId);
    if (!session) {
        return res.json({
            status: 'disconnected',
            session_id: sessionId,
            sessionId,
            qr: null,
            qr_code: null,
            qr_image: null,
        });
    }
    res.json({
        status: session.status,
        session_id: sessionId,
        sessionId,
        qr: session.qr,
        qr_code: session.qr,
        qr_image: session.qrImage,
        user: session.userJid,
        phone_number: session.phoneNumber,
        phoneNumber: session.phoneNumber,
        business_id: session.businessId,
        businessId: session.businessId,
    });
});

app.get('/api/sessions/:sessionId', authenticate, (req, res) => {
    const { sessionId } = req.params;
    const session = sessions.get(sessionId);
    if (!session) {
        return res.status(404).json({ error: 'Session not found' });
    }
    res.json({
        status: session.status,
        session_id: sessionId,
        sessionId,
        qr_code: session.qr,
        qr_image: session.qrImage,
        phone_number: session.phoneNumber,
        business_id: session.businessId,
    });
});

/**
 * Send WhatsApp Message with Anti-Ban Human Typing Presence Simulation
 */
app.post('/api/sessions/:sessionId/messages', authenticate, async (req, res) => {
    const { sessionId } = req.params;
    const { to, text } = req.body;

    if (!to || !text) {
        return res.status(400).json({ error: 'to and text are required' });
    }

    const session = sessions.get(sessionId);
    if (!session || !session.sock || session.status !== 'connected') {
        return res.status(503).json({ error: 'Session is not connected' });
    }

    try {
        const jid = to.includes('@') ? to : `${to.replace(/[^0-9]/g, '')}@s.whatsapp.net`;

        // Anti-ban typing presence simulation
        try {
            await session.sock.sendPresenceUpdate('composing', jid);
            const typingDelay = Math.min(1200, Math.max(400, String(text).length * 18));
            await new Promise((r) => setTimeout(r, typingDelay));
            await session.sock.sendPresenceUpdate('paused', jid);
        } catch (presenceErr) {
            // non-fatal
        }

        const result = await session.sock.sendMessage(jid, { text });

        res.json({
            status: 'sent',
            message_id: result.key.id,
        });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

/**
 * Restart WhatsApp Session
 */
app.post('/api/sessions/:sessionId/restart', authenticate, async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions.get(sessionId);
    const businessId = session?.businessId || null;

    if (session && session.sock) {
        try {
            session.sock.end(new Error('Manual restart'));
        } catch (e) {}
    }
    sessions.delete(sessionId);

    const newSession = await createSession(sessionId, businessId);
    res.json({
        status: 'restarted',
        session_id: sessionId,
        sessionId,
        session_status: newSession.status,
    });
});

/**
 * Disconnect & Delete Session
 */
app.delete('/api/sessions/:sessionId', authenticate, async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions.get(sessionId);
    if (session && session.sock) {
        try {
            await session.sock.logout();
        } catch (e) {}
        sessions.delete(sessionId);
    }

    const sessionFile = path.join(SESSIONS_DIR, `${sessionId}_auth.json`);
    if (fs.existsSync(sessionFile)) {
        try {
            fs.unlinkSync(sessionFile);
        } catch (e) {}
    }

    res.json({ status: 'deleted', session_id: sessionId, sessionId });
});

// Clean shutdown handler to flush debounced auth states
const handleShutdown = () => {
    for (const [, session] of sessions.entries()) {
        if (session.authHelper?.flushSync) {
            session.authHelper.flushSync();
        }
    }
    process.exit(0);
};
process.on('SIGINT', handleShutdown);
process.on('SIGTERM', handleShutdown);

app.listen(PORT, () => {
    console.log(`WhatsApp Baileys Service running on port ${PORT}`);
    autoBootSessions();
});
