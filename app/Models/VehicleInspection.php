<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleInspection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reservation_id',
        'car_id',
        'inspector_id',
        'inspection_type',
        'odometer',
        'fuel_level',
        'overall_condition',
        'damages',
        'standard_deposit',
        'total_damage_cost',
        'deposit_deducted',
        'deposit_refunded',
        'settlement_status',
        'settlement_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'odometer' => 'integer',
        'damages' => 'array',
        'standard_deposit' => 'decimal:2',
        'total_damage_cost' => 'decimal:2',
        'deposit_deducted' => 'decimal:2',
        'deposit_refunded' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the reservation for this inspection.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the car inspected.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Get the inspector (admin) user.
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
