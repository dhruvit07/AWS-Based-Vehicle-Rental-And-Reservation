<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Console\Command;

class ExpirePendingReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire pending reservations older than 30 minutes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiryThreshold = now()->subMinutes(30);

        $count = Reservation::where('status', ReservationStatus::PENDING)
            ->where('created_at', '<', $expiryThreshold)
            ->update([
                'status' => ReservationStatus::CANCELLED,
                'cancellation_reason' => 'Payment/checkout session expired',
                'cancelled_at' => now(),
            ]);

        $this->info("Expired {$count} pending reservation(s).");

        return Command::SUCCESS;
    }
}
