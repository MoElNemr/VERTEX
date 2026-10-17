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
        Schema::create('bulk_message_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
            $table->string('type'); // topup, deduct, refund, manual_adjustment
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->integer('credits')->default(0);
            $table->decimal('balance_after', 10, 2)->default(0.00);
            $table->integer('credits_after')->default(0);
            $table->string('platform')->nullable(); // whatsapp, telegram, facebook, instagram
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_message_transactions');
    }
};
