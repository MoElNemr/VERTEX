<?php

namespace App\Services;

use App\Models\BusinessAiSetting;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * خدمة التفاعل مع الذكاء الاصطناعي عبر GROQ Cloud API مباشرة
 */
class AiService
{
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = (string) (config('services.groq.url') ?: env('GROQ_API_URL', 'https://api.groq.com/openai/v1'));
    }

    /**
     * Generate a suggested reply for the agent.
     */
    public function suggestReply(Conversation $conversation, BusinessAiSetting $setting): ?string
    {
        $messages = $conversation->messages()->oldest()->limit(20)->get();
        $history = $this->buildHistory($messages, $setting);
        $history[] = ['role' => 'user', 'content' => 'اقترح ردًا مناسبًا للوكيل للرسالة الأخيرة من العميل. أجب بالرد فقط دون شرح.'];

        return $this->callGroq($history, $setting->ai_model ?: 'llama-3.3-70b-versatile', $setting->api_key);
    }

    /**
     * Summarize a conversation.
     */
    public function summarize(Conversation $conversation, BusinessAiSetting $setting): ?string
    {
        $messages = $conversation->messages()->oldest()->get();
        $history = $this->buildHistory($messages, $setting);
        $history[] = ['role' => 'user', 'content' => 'لخص هذه المحادثة في فقرة قصيرة تشمل المشكلة والحل المقترح.'];

        return $this->callGroq($history, $setting->ai_model ?: 'llama-3.3-70b-versatile', $setting->api_key);
    }

    /**
     * Generate an auto-reply for an inbound message.
     */
    public function autoReply(Conversation $conversation, BusinessAiSetting $setting, string $inboundMessage): ?string
    {
        $messages = $conversation->messages()->oldest()->limit(10)->get();
        $history = $this->buildHistory($messages, $setting);
        $history[] = ['role' => 'user', 'content' => $inboundMessage];

        return $this->callGroq($history, $setting->ai_model ?: 'llama-3.3-70b-versatile', $setting->api_key);
    }

    protected function buildHistory($messages, BusinessAiSetting $setting): array
    {
        $system = $setting->ai_personality ?: 'أنت مساعد دعم عملاء مفيد ومهذب.';
        if ($setting->ai_instructions) {
            $system .= "\n".$setting->ai_instructions;
        }
        $history = [['role' => 'system', 'content' => $system]];
        foreach ($messages as $msg) {
            $history[] = [
                'role' => in_array($msg->sender_type, ['agent', 'bot'], true) ? 'assistant' : 'user',
                'content' => $msg->body,
            ];
        }

        return $history;
    }

    /**
     * Map model identifier to supported Groq model names.
     */
    protected function mapModel(string $model): string
    {
        $map = [
            'gpt-4o-mini' => 'llama-3.3-70b-versatile',
            'gpt-4o' => 'llama-3.3-70b-versatile',
            'gpt-4-turbo' => 'llama-3.3-70b-versatile',
            'claude-3-5-sonnet' => 'llama-3.3-70b-versatile',
            'gemini-1.5-flash' => 'llama-3.1-8b-instant',
        ];

        return $map[$model] ?? $model;
    }

    /**
     * Call Groq Cloud API directly.
     */
    public function callGroq(array $messages, string $model = 'llama-3.3-70b-versatile', ?string $apiKey = null): ?string
    {
        $resolvedKey = trim((string) ($apiKey ?? ''));
        if (empty($resolvedKey)) {
            Log::warning('AI request blocked: business has no GROQ API key configured.');

            return null;
        }

        $resolvedModel = $this->mapModel($model);

        try {
            $response = Http::withToken($resolvedKey)
                ->timeout(30)
                ->post(rtrim($this->apiUrl, '/').'/chat/completions', [
                    'model' => $resolvedModel,
                    'messages' => $messages,
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::warning('Groq API error: '.$response->body());
        } catch (\Throwable $e) {
            Log::error('Groq service error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Backward compatibility helper.
     */
    protected function callOpenAI(array $messages, string $model = 'llama-3.3-70b-versatile'): ?string
    {
        return $this->callGroq($messages, $model);
    }
}
