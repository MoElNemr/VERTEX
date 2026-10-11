<?php

namespace App\Services;

use App\Models\BusinessAiSetting;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * خدمة التفاعل مع الذكاء الاصطناعي (OpenAI-compatible API)
 */
class AiService
{
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.openai.key') ?: env('OPENAI_API_KEY', ''));
        $this->apiUrl = (string) (config('services.openai.url') ?: env('OPENAI_API_URL', 'https://api.openai.com/v1'));
    }

    /**
     * Generate a suggested reply for the agent.
     */
    public function suggestReply(Conversation $conversation, BusinessAiSetting $setting): ?string
    {
        $messages = $conversation->messages()->oldest()->limit(20)->get();
        $history = $this->buildHistory($messages, $setting);
        $history[] = ['role' => 'user', 'content' => 'اقترح ردًا مناسبًا للوكيل للرسالة الأخيرة من العميل. أجب بالرد فقط دون شرح.'];
        return $this->callOpenAI($history, $setting->ai_model ?: 'gpt-4o-mini');
    }

    /**
     * Summarize a conversation.
     */
    public function summarize(Conversation $conversation, BusinessAiSetting $setting): ?string
    {
        $messages = $conversation->messages()->oldest()->get();
        $history = $this->buildHistory($messages, $setting);
        $history[] = ['role' => 'user', 'content' => 'لخص هذه المحادثة في فقرة قصيرة تشمل المشكلة والحل المقترح.'];
        return $this->callOpenAI($history, $setting->ai_model ?: 'gpt-4o-mini');
    }

    /**
     * Generate an auto-reply for an inbound message.
     */
    public function autoReply(Conversation $conversation, BusinessAiSetting $setting, string $inboundMessage): ?string
    {
        $messages = $conversation->messages()->oldest()->limit(10)->get();
        $history = $this->buildHistory($messages, $setting);
        $history[] = ['role' => 'user', 'content' => $inboundMessage];
        return $this->callOpenAI($history, $setting->ai_model ?: 'gpt-4o-mini');
    }

    protected function buildHistory($messages, BusinessAiSetting $setting): array
    {
        $system = $setting->ai_personality ?: 'أنت مساعد دعم عملاء مفيد ومهذب.';
        if ($setting->ai_instructions) {
            $system .= "\n" . $setting->ai_instructions;
        }
        $history = [['role' => 'system', 'content' => $system]];
        foreach ($messages as $msg) {
            $history[] = [
                'role' => $msg->sender_type === 'agent' ? 'assistant' : 'user',
                'content' => $msg->body,
            ];
        }
        return $history;
    }

    protected function callOpenAI(array $messages, string $model = 'gpt-4o-mini'): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('OPENAI_API_KEY not configured.');
            return null;
        }
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post(rtrim($this->apiUrl, '/') . '/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                ]);
            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
            Log::warning('OpenAI API error: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('OpenAI service error: ' . $e->getMessage());
        }
        return null;
    }
}
