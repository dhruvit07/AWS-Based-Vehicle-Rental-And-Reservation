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
            $table->string('kyc_status')->default('unsubmitted')->after('is_active');
            $table->string('license_number', 50)->nullable()->after('kyc_status');
            $table->date('license_expiry')->nullable()->after('license_number');
            $table->string('license_front_image')->nullable()->after('license_expiry');
            $table->string('license_back_image')->nullable()->after('license_front_image');
            $table->text('kyc_rejection_reason')->nullable()->after('license_back_image');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'kyc_status',
                'license_number',
                'license_expiry',
                'license_front_image',
                'license_back_image',
                'kyc_rejection_reason',
                'kyc_verified_at',
            ]);
        });
    }
};
