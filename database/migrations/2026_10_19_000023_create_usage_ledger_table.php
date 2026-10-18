<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entry_type'); // debit, credit, adjustment, refund
            $table->string('category'); // message, campaign, ai, wallet_topup, admin_adjustment, payment
            $table->decimal('amount', 12, 4)->default(0); // monetary amount (positive)
            $table->integer('credits')->default(0); // credit units (positive)
            $table->string('currency', 3)->default('EGP');
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable(); // MessageAttempt, Campaign, PaymentTransaction
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('balance_after', 12, 4)->nullable(); // snapshot for reconciliation
            $table->integer('credits_after')->nullable();
            $table->string('recorded_by')->nullable(); // system, admin_id, payment_gateway
            $table->text('reason')->nullable(); // required for admin_adjustment entries
            $table->timestamps();

            // Immutable — no updates allowed in application code
            $table->index(['user_id', 'category', 'created_at'], 'ul_user_category_idx');
            $table->index(['business_id', 'entry_type', 'created_at'], 'ul_business_entry_idx');
            $table->index(['reference_type', 'reference_id'], 'ul_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_ledger');
    }
};
