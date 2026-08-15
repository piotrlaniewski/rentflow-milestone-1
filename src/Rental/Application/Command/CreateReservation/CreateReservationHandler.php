<?php

declare(strict_types=1);

namespace App\Rental\Application\Command\CreateReservation;

use App\Rental\Application\Exception\VehicleNotAvailable;
use App\Rental\Domain\Reservation\Reservation;
use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Rental\Domain\Reservation\ReservationPricing;
use App\Rental\Domain\Reservation\ReservationRepository;
use App\Rental\Domain\Reservation\VehicleAvailability;
use App\Shared\Domain\CustomerId;
use App\Shared\Domain\VehicleId;

final readonly class CreateReservationHandler
{
    public function __construct(
        private ReservationRepository $reservations,
        private VehicleAvailability $availability,
        private ReservationPricing $pricing,
    ) {
    }

    public function __invoke(CreateReservation $command): void
    {
        $vehicleId = VehicleId::fromString(
            $command->vehicleId
        );

        $period = new ReservationPeriod(
            from: $command->from,
            to: $command->to,
        );

        if (!$this->availability->isAvailable($vehicleId, $period)) {
            throw VehicleNotAvailable::create(
                $vehicleId->toString()
            );
        }

        $price = $this->pricing->calculate(
            $vehicleId,
            $period,
        );

        $reservation = Reservation::create(
            id: ReservationId::generate(),
            customerId: CustomerId::fromString(
                $command->customerId
            ),
            vehicleId: $vehicleId,
            period: $period,
            price: $price,
        );

        $this->reservations->save($reservation);
    }
}
