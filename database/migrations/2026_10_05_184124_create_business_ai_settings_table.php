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
            $table->boolean('ai_enabled')->default(false);
            $table->string('provider')->default('openai'); // openai, gemini, etc.
            $table->string('model')->default('gpt-4o-mini');
            $table->string('ai_model')->default('gpt-4o-mini');
            $table->text('api_key')->nullable();
            $table->text('system_prompt')->nullable();
            $table->text('ai_personality')->nullable();
            $table->text('ai_instructions')->nullable();
            $table->boolean('working_hours_only')->default(false);
            $table->unsignedTinyInteger('work_start_hour')->default(9);
            $table->unsignedTinyInteger('work_end_hour')->default(18);
            $table->json('auto_reply_platforms')->nullable(); // ['whatsapp', 'facebook', 'instagram', 'telegram']
            $table->unsignedInteger('reply_delay_seconds')->default(2);
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
