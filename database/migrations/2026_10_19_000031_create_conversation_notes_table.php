<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->unsignedBigInteger('author_id'); // team member or user ID
            $table->string('author_type'); // team_member, user
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at'], 'cn_conv_idx');
            $table->index(['business_id', 'author_id', 'author_type'], 'cn_author_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_notes');
    }
};
