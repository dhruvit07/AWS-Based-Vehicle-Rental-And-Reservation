<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Car;
use App\Models\CarReview;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarReviewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample realistic reviews data
        $reviewPool = [
            [
                'rating' => 5,
                'title' => 'Absolute perfection for our highway road trip',
                'comment' => 'The car was handed over in immaculate condition. Fresh interior, whisper quiet engine, and effortless passing power. Handover was completed in under 5 minutes.',
            ],
            [
                'rating' => 5,
                'title' => 'Spotless car, smooth pickup and drop-off',
                'comment' => 'Booked for an anniversary weekend trip. The car smelled wonderful and drove like a dream. Fuel economy was surprisingly great too.',
            ],
            [
                'rating' => 5,
                'title' => 'Top notch service and luxury feel',
                'comment' => 'Everything from booking to vehicle condition was 5 stars. The suspension soaked up all bumps on the expressway effortlessly.',
            ],
            [
                'rating' => 4,
                'title' => 'Very solid ride and extremely comfortable',
                'comment' => 'Great car for the family. Plenty of boot space for four large suitcases. Minor delay at pickup desk, but the staff was courteous.',
            ],
            [
                'rating' => 5,
                'title' => 'Premium experience without any fuss',
                'comment' => 'The vehicle drove like brand new with only 8,000 km on the clock. Crisp sound system and crystal clear CarPlay integration.',
            ],
            [
                'rating' => 5,
                'title' => 'Smooth hill climb and confident handling',
                'comment' => 'Took this up to the Western Ghats. Steering feedback was sharp and braking was reassuring on wet mountain curves. Would rent again in a heartbeat.',
            ],
            [
                'rating' => 4,
                'title' => 'Delightful weekend cruise',
                'comment' => 'Very clean cabin and chilled air conditioning. Fuel tank was 90% full at handover rather than 100%, but customer support quickly credited the difference.',
            ],
            [
                'rating' => 5,
                'title' => 'Best rental experience in years',
                'comment' => 'Unbeatable convenience. Key handover was contactless and prompt. The engine response in sports mode puts a wide grin on your face.',
            ],
            [
                'rating' => 3,
                'title' => 'Good car, average infotainment sync',
                'comment' => 'Mechanical condition was flawless and very clean. However, Bluetooth occasionally disconnected on long stretches. Otherwise a great trip.',
            ],
            [
                'rating' => 5,
                'title' => 'Impeccable condition, felt like showroom delivery',
                'comment' => 'Literally not a single scratch on the bodywork. Seats were ergonomically contoured for 6+ hour driving without fatigue.',
            ],
            [
                'rating' => 4,
                'title' => 'Great road trip companion',
                'comment' => 'Smooth automatic transmission and comfortable seating for five adults. Delivered right to the airport terminal on time.',
            ],
            [
                'rating' => 5,
                'title' => 'Exceptional comfort and punchy acceleration',
                'comment' => 'Cruising at highway speeds felt rock stable. High quality sound system and the cooled seats were a lifesaver in the afternoon sun.',
            ],
            [
                'rating' => 5,
                'title' => 'Outstanding support and pristine vehicle',
                'comment' => 'Needed to extend our booking by 4 hours due to flight rescheduling; the team handled it seamlessly through WhatsApp. Highly recommended!',
            ],
            [
                'rating' => 4,
                'title' => 'Comfortable and fuel efficient',
                'comment' => 'Got over 16 km/l on the expressway. Suspension is tuned nicely for Indian roads. Check-in procedure was very straightforward.',
            ],
            [
                'rating' => 5,
                'title' => 'Exceeded every expectation',
                'comment' => 'This vehicle turned heads everywhere we parked. Clean interiors, fresh mats, and zero mechanical quirks. Will definitely book again.',
            ],
            [
                'rating' => 3,
                'title' => 'Decent experience with minor pickup delay',
                'comment' => 'Car was in very good shape and drove well. Handover took 20 minutes longer than expected due to counter rush at Terminal 2.',
            ],
            [
                'rating' => 5,
                'title' => 'Silent cabin and effortless overtaking',
                'comment' => 'The NVH levels in this car are extraordinary. You barely hear the road noise even at 100 km/h. Great braking confidence too.',
            ],
            [
                'rating' => 5,
                'title' => 'Seamless digital experience and luxury car',
                'comment' => 'Booked online in under 3 minutes, uploaded KYC, and received keys directly at the pickup bay. Car was gleaming clean.',
            ],
            [
                'rating' => 4,
                'title' => 'Spacious and responsive',
                'comment' => 'Huge boot and supportive seats for all passengers. Good highway mileage and easy to maneuver in tight city traffic.',
            ],
            [
                'rating' => 5,
                'title' => 'The gold standard for vehicle rentals',
                'comment' => 'Have rented from international brands before, but Real Rent Car beat them all on vehicle cleanliness, pricing transparency, and vehicle condition.',
            ],
            [
                'rating' => 5,
                'title' => 'Pure driving pleasure',
                'comment' => 'Instant torque, communicative steering, and plush upholstery. Made our weekend getaway so memorable.',
            ],
            [
                'rating' => 4,
                'title' => 'Well maintained and reliable',
                'comment' => 'Everything worked as intended: ADAS, lane keep assist, navigation, and cruise control. Return inspection was done in 2 minutes.',
            ],
            [
                'rating' => 5,
                'title' => 'Superb condition for an outstation trip',
                'comment' => 'Drove over 900 km during the 4-day rental. Never missed a beat. AC was icy cold and tyres had deep tread.',
            ],
            [
                'rating' => 5,
                'title' => 'Five stars for cleanliness and luxury',
                'comment' => 'You can tell this fleet is cared for by real car enthusiasts. Interior leather was conditioned and spotless.',
            ],
            [
                'rating' => 4,
                'title' => 'Very satisfied with the booking',
                'comment' => 'Comfortable, powerful, and reasonable price per day. Staff at the pickup location were polite and professional.',
            ],
            [
                'rating' => 5,
                'title' => 'Fantastic performance and convenience',
                'comment' => 'GPS navigation was preloaded with offline maps which helped a lot in weak signal zones. Great attention to detail.',
            ],
            [
                'rating' => 3,
                'title' => 'Satisfactory overall',
                'comment' => 'Car performed well on the highway. Some minor cosmetic wear on the steering wheel leather, but mechanically sound.',
            ],
            [
                'rating' => 5,
                'title' => 'Simply unmatched quality and comfort',
                'comment' => 'Traveled with elderly parents who specifically noted how easy ingress/egress and ride cushioning was. 10/10.',
            ],
            [
                'rating' => 5,
                'title' => 'Smooth, safe, and premium',
                'comment' => 'Everything was effortless. Zero surprises, zero hidden charges at return. Truly the best rental experience.',
            ],
            [
                'rating' => 4,
                'title' => 'Great value for money',
                'comment' => 'Used the promo code and got fantastic savings. Car was washed, sanitized, and ready when we arrived.',
            ],
            [
                'rating' => 5,
                'title' => 'High quality fleet vehicle',
                'comment' => 'Suspension dampening and cabin insulation made our 5-hour drive feel like a quick breeze. Highly recommended.',
            ],
            [
                'rating' => 5,
                'title' => 'Exceptional service and pristine condition',
                'comment' => 'Quickest return process ever. Handed the keys, inspector checked the fuel level, and deposit release was confirmed instantly.',
            ],
        ];

        $cars = Car::all();
        if ($cars->isEmpty()) {
            return;
        }

        // Get or create client users to author reviews
        $clients = User::where('role', UserRole::CLIENT)->get();
        if ($clients->count() < 5) {
            // Create a few additional client users if needed
            for ($i = 1; $i <= 5; $i++) {
                $clients->push(User::firstOrCreate(
                    ['email' => "client{$i}@example.com"],
                    [
                        'name' => "Renter {$i}",
                        'password' => bcrypt('00000000'),
                        'role' => UserRole::CLIENT,
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]
                ));
            }
        }

        $demoClient = User::where('email', 'client@example.com')->first();

        // 1. First, check existing completed reservations that don't have reviews yet
        $existingCompleted = Reservation::where('status', ReservationStatus::COMPLETED)
            ->whereDoesntHave('review')
            ->get();

        $reviewIndex = 0;
        foreach ($existingCompleted as $res) {
            if ($reviewIndex >= count($reviewPool)) break;

            $data = $reviewPool[$reviewIndex++];
            CarReview::create([
                'car_id' => $res->car_id,
                'user_id' => $res->user_id,
                'reservation_id' => $res->id,
                'rating' => $data['rating'],
                'title' => $data['title'],
                'comment' => $data['comment'],
                'is_published' => true,
                'created_at' => Carbon::parse($res->end_date)->addHours(rand(2, 48)),
                'updated_at' => Carbon::parse($res->end_date)->addHours(rand(2, 48)),
            ]);
        }

        // 2. Generate additional completed reservations in the past and attach reviews to reach 32+ total reviews
        $carCount = $cars->count();
        $clientCount = $clients->count();

        while ($reviewIndex < count($reviewPool)) {
            $data = $reviewPool[$reviewIndex];
            $car = $cars[$reviewIndex % $carCount];
            // Rotate clients, avoiding giving everything to demo client
            $user = $clients[$reviewIndex % $clientCount];

            // Past dates 15 to 90 days ago
            $daysAgo = rand(15, 90);
            $duration = rand(2, 5);
            $startDate = Carbon::now()->subDays($daysAgo);
            $endDate = (clone $startDate)->addDays($duration);
            $subtotal = round($car->price_per_day * $duration, 2);
            $taxAmount = round($subtotal * 0.07, 2);
            $totalAmount = round($subtotal + $taxAmount, 2);

            $reservation = Reservation::create([
                'reservation_number' => 'RES-' . strtoupper(Str::random(8)),
                'user_id' => $user->id,
                'car_id' => $car->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'pickup_location' => 'Airport Terminal 1',
                'return_location' => 'Airport Terminal 1',
                'total_days' => $duration,
                'daily_rate' => $car->price_per_day,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'status' => ReservationStatus::COMPLETED,
                'created_at' => $startDate->copy()->subDays(3),
                'updated_at' => $endDate->copy()->addHours(2),
            ]);

            Payment::create([
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
                'amount' => $totalAmount,
                'currency' => config('app.currency_code', 'INR'),
                'payment_method' => PaymentMethod::CREDIT_CARD,
                'status' => PaymentStatus::COMPLETED,
                'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
                'processed_at' => $startDate->copy()->subDays(3),
            ]);

            CarReview::create([
                'car_id' => $car->id,
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'rating' => $data['rating'],
                'title' => $data['title'],
                'comment' => $data['comment'],
                'is_published' => true,
                'created_at' => $endDate->copy()->addHours(rand(4, 36)),
                'updated_at' => $endDate->copy()->addHours(rand(4, 36)),
            ]);

            $reviewIndex++;
        }

        // 3. For the demo client (`client@example.com`):
        // Ensure they have ONE completed reservation WITHOUT a review (for car #1 or first car),
        // so they can test/demonstrate submitting a review!
        if ($demoClient) {
            $firstCar = $cars->first();
            $hasUnreviewed = Reservation::where('user_id', $demoClient->id)
                ->where('status', ReservationStatus::COMPLETED)
                ->whereDoesntHave('review')
                ->exists();

            if (! $hasUnreviewed && $firstCar) {
                $pastStart = Carbon::now()->subDays(10);
                $pastEnd = Carbon::now()->subDays(7);
                $sub = round($firstCar->price_per_day * 3, 2);
                $tax = round($sub * 0.07, 2);
                $tot = round($sub + $tax, 2);

                $res = Reservation::create([
                    'reservation_number' => 'RES-' . strtoupper(Str::random(8)),
                    'user_id' => $demoClient->id,
                    'car_id' => $firstCar->id,
                    'start_date' => $pastStart->format('Y-m-d'),
                    'end_date' => $pastEnd->format('Y-m-d'),
                    'pickup_location' => 'Downtown Office',
                    'return_location' => 'Downtown Office',
                    'total_days' => 3,
                    'daily_rate' => $firstCar->price_per_day,
                    'subtotal' => $sub,
                    'tax_amount' => $tax,
                    'discount_amount' => 0,
                    'total_amount' => $tot,
                    'status' => ReservationStatus::COMPLETED,
                    'created_at' => $pastStart->copy()->subDays(2),
                    'updated_at' => $pastEnd->copy()->addHours(1),
                ]);

                Payment::create([
                    'reservation_id' => $res->id,
                    'user_id' => $demoClient->id,
                    'amount' => $tot,
                    'currency' => config('app.currency_code', 'INR'),
                    'payment_method' => PaymentMethod::CREDIT_CARD,
                    'status' => PaymentStatus::COMPLETED,
                    'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
                    'processed_at' => $pastStart->copy()->subDays(2),
                ]);
            }
        }
    }
}
