<?php

use App\Enums\TransactionType;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('spender_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('type', array_column(TransactionType::cases(), 'value'));
            $table->text('description')->nullable();
            $table->datetimeTz('transaction_at');
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
