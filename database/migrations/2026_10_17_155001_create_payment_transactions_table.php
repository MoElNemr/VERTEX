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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_number')->unique();
            $table->string('fawry_reference_number')->nullable()->index();
            $table->string('type')->default('subscription'); // subscription, bulk_credit
            $table->string('billing_cycle')->nullable(); // monthly, yearly
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('EGP');
            $table->string('status')->default('pending'); // pending, paid, failed, cancelled
            $table->string('payment_method')->default('fawry');
            $table->json('payment_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
