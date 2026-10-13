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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('retargeting'); // retargeting, whatsapp_bulk, facebook_bulk, instagram_bulk, telegram_bulk
            $table->json('platforms')->nullable(); // ['whatsapp', 'facebook', 'instagram', 'telegram']
            $table->text('message')->nullable();
            $table->json('platform_messages')->nullable();
            $table->string('status')->default('draft'); // draft, processing, paused, completed, cancelled, failed
            $table->unsignedInteger('interval_seconds')->default(10);
            $table->unsignedInteger('jitter_seconds')->default(2);
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
