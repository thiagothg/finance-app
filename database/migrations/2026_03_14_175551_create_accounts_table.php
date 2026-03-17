<?php

use App\Enums\AccountType;
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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->enum('type', array_column(AccountType::cases(), 'value'));
            $table->decimal('initial_balance', 12, 2)->default(0);
            $table->string('currency')->default('BRL');
            $table->boolean('is_closed')->default(false);
            $table->datetimeTz('close_at')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('bank', 50);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['user_id', 'bank', 'name', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
