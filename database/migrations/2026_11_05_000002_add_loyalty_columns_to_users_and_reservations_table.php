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
        Schema::table('users', function (Blueprint $table) {
            $table->string('loyalty_tier')->default('bronze')->after('kyc_status'); // bronze, silver, gold, platinum
            $table->decimal('lifetime_spend', 12, 2)->default(0.00)->after('loyalty_tier');
            $table->integer('completed_trips_count')->default(0)->after('lifetime_spend');
            $table->timestamp('tier_updated_at')->nullable()->after('completed_trips_count');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('loyalty_discount_amount', 10, 2)->default(0.00)->after('discount_amount');
            $table->string('applied_loyalty_tier')->nullable()->after('loyalty_discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_tier',
                'lifetime_spend',
                'completed_trips_count',
                'tier_updated_at',
            ]);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_discount_amount',
                'applied_loyalty_tier',
            ]);
        });
    }
};
