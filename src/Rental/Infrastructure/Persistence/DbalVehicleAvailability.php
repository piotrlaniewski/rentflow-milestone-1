<?php

declare(strict_types=1);

namespace App\Rental\Infrastructure\Persistence;

use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Rental\Domain\Reservation\VehicleAvailability;
use App\Shared\Domain\VehicleId;
use Doctrine\DBAL\Connection;

final readonly class DbalVehicleAvailability implements VehicleAvailability
{
    public function __construct(private Connection $connection) {}

    public function isAvailable(VehicleId $vehicleId, ReservationPeriod $period): bool
    {
        $overlappingReservations = $this->connection->fetchOne(
            <<<'SQL'
                SELECT COUNT(*)
                FROM reservation
                WHERE vehicle_id = :vehicleId
                  AND status IN ('pending', 'confirmed')
                  AND tstzrange(date_from, date_to, '[)') && tstzrange(:dateFrom, :dateTo, '[)')
            SQL,
            [
                'vehicleId' => $vehicleId->toString(),
                'dateFrom' => $period->from()->format(DATE_ATOM),
                'dateTo' => $period->to()->format(DATE_ATOM),
            ],
        );

        return (int) $overlappingReservations === 0;
    }
}

