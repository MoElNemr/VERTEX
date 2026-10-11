<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->string('provider')->default('openai'); // openai, gemini, etc.
            $table->text('api_key')->nullable();
            $table->string('model')->default('gpt-4o-mini');
            $table->text('system_prompt')->nullable();
            $table->json('auto_reply_platforms')->nullable(); // ['whatsapp', 'facebook', etc]
            $table->unsignedInteger('reply_delay_seconds')->default(3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_ai_settings');
    }
};
