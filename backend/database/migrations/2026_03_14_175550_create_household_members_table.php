<?php

use App\Enums\HouseholdMemberRole;
use App\Enums\HouseholdMemberStatus;
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
        Schema::create('household_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('role', array_column(HouseholdMemberRole::cases(), 'value'));
            $table->enum('status', array_column(HouseholdMemberStatus::cases(), 'value'))
                ->default(HouseholdMemberStatus::Pending->value);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_members');
    }
};
