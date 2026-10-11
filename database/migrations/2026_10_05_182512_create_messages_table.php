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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ['contact', 'agent', 'bot'])->default('contact');
            $table->foreignId('sender_id')->nullable()->constrained('team_members')->nullOnDelete();
            $table->string('platform_message_id')->nullable()->index();
            $table->enum('type', ['text', 'image', 'video', 'audio', 'document', 'location', 'comment'])->default('text');
            $table->text('body')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('status', ['sent', 'delivered', 'read', 'failed'])->default('sent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
