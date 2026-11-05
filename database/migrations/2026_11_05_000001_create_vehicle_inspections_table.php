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
        Schema::create('vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('inspector_id')->constrained('users')->onDelete('cascade');
            $table->string('inspection_type')->default('checkin'); // checkin, checkout
            $table->integer('odometer');
            $table->string('fuel_level'); // full, good, low, empty
            $table->string('overall_condition')->default('pristine'); // pristine, minor_wear, damaged, severe
            $table->json('damages')->nullable(); // [{ id, zone, type, severity, cost, notes, x, y }]
            $table->decimal('standard_deposit', 10, 2)->default(5000.00);
            $table->decimal('total_damage_cost', 10, 2)->default(0.00);
            $table->decimal('deposit_deducted', 10, 2)->default(0.00);
            $table->decimal('deposit_refunded', 10, 2)->default(5000.00);
            $table->string('settlement_status')->default('refunded_full'); // refunded_full, deducted_partial, forfeited_full, shortfall_owed
            $table->text('settlement_notes')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'inspection_type']);
            $table->index(['car_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_inspections');
    }
};
