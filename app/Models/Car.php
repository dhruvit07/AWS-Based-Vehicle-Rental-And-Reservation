<?php

namespace App\Models;

use App\Enums\CarColor;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use MohamedGaldi\ViltFilepond\Traits\HasFiles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ReservationStatus;

class Car extends Model
{
    use SoftDeletes;
    use HasFiles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'make',
        'model',
        'year',
        'license_plate',
        'color',
        'price_per_day',
        'mileage',
        'transmission',
        'seats',
        'fuel_type',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'price_per_day' => 'decimal:2',
        'mileage' => 'integer',
        'seats' => 'integer',
        'status' => CarStatus::class,
        'fuel_type' => FuelType::class,
        'color' => CarColor::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'image_url',
        'average_rating',
        'reviews_count',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the formatted price attribute.
     *
     * @return string
     */
    public function getFormattedPriceAttribute()
    {
        return config('app.currency_symbol') . number_format($this->price_per_day, 2);
    }

    /**
     * Get the full car name (make + model + year).
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    /**
     * Get the car image URL. Uses the first file in the 'image' collection if available,
     * otherwise falls back to the default public image.
     */
    public function getImageUrlAttribute(): string
    {
        // If the relation is already loaded, use it to avoid N+1
        $file = null;
        if ($this->relationLoaded('files')) {
            $file = $this->files->firstWhere('collection', 'image');
        }

        // Otherwise query for the first image file
        if (!$file) {
            $file = $this->files()->where('collection', 'image')->first();
        }

        if ($file && $file->path) {
            return Storage::url($file->path);
        }

        // Fallback to the public default image
        return asset('images/car-default.jpg');
    }

    /**
     * Get the reservations for the car.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get the reviews for the car.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CarReview::class, 'car_id');
    }

    /**
     * Get all inspections for this vehicle.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(VehicleInspection::class, 'car_id');
    }

    /**
     * Get the average rating of the car.
     */
    public function getAverageRatingAttribute(): float
    {
        if (array_key_exists('average_rating', $this->attributes) && $this->attributes['average_rating'] !== null) {
            return round((float) $this->attributes['average_rating'], 1);
        }

        if ($this->relationLoaded('reviews')) {
            $published = $this->reviews->where('is_published', true);
            return $published->count() > 0 ? round((float) $published->avg('rating'), 1) : 0.0;
        }

        $avg = $this->reviews()->where('is_published', true)->avg('rating');
        return $avg ? round((float) $avg, 1) : 0.0;
    }

    /**
     * Get the total number of published reviews.
     */
    public function getReviewsCountAttribute(): int
    {
        if (array_key_exists('reviews_count', $this->attributes) && $this->attributes['reviews_count'] !== null) {
            return (int) $this->attributes['reviews_count'];
        }

        if ($this->relationLoaded('reviews')) {
            return $this->reviews->where('is_published', true)->count();
        }

        return (int) $this->reviews()->where('is_published', true)->count();
    }

    /**
     * Get the rating distribution breakdown (counts & percentages for 1..5 stars).
     */
    public function getRatingDistributionAttribute(): array
    {
        $reviews = $this->relationLoaded('reviews')
            ? $this->reviews->where('is_published', true)
            : $this->reviews()->where('is_published', true)->get(['rating']);

        $total = $reviews->count();
        $distribution = [];

        for ($star = 5; $star >= 1; $star--) {
            $count = $reviews->where('rating', $star)->count();
            $percentage = $total > 0 ? (int) round(($count / $total) * 100) : 0;
            $distribution[$star] = [
                'star' => $star,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $distribution;
    }

    /**
     * Check if car is available for given date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeReservationId
     * @return bool
     */
    public function isAvailable(string $startDate, string $endDate, ?int $excludeReservationId = null): bool
    {
        $query = $this->reservations()
            ->where(function ($q) {
                $q->whereIn('status', [
                    ReservationStatus::CONFIRMED,
                    ReservationStatus::ACTIVE,
                ])->orWhere(function ($pending) {
                    $pending->where('status', ReservationStatus::PENDING)
                        ->where('created_at', '>=', now()->subMinutes(30));
                });
            })
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->count() === 0;
    }
}
