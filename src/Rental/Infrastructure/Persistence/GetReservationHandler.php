<?php

declare(strict_types=1);

namespace App\Rental\Infrastructure\Persistence;

use App\Rental\Application\Query\GetReservation\GetReservation;
use App\Rental\Application\Query\GetReservation\ReservationView;
use App\Rental\Domain\Reservation\Exception\ReservationNotFound;
use Doctrine\DBAL\Connection;

final readonly class GetReservationHandler
{
    public function __construct(private Connection $connection) {}

    public function __invoke(GetReservation $query): ReservationView
    {
        $row = $this->connection->fetchAssociative(
            <<<'SQL'
                SELECT id, customer_id, vehicle_id, date_from, date_to, status,
                       price_amount, price_currency
                FROM reservation
                WHERE id = :id
            SQL,
            ['id' => $query->reservationId],
        );

        if ($row === false) {
            throw ReservationNotFound::withId($query->reservationId);
        }

        return new ReservationView(
            id: (string) $row['id'],
            customerId: (string) $row['customer_id'],
            vehicleId: (string) $row['vehicle_id'],
            from: (new \DateTimeImmutable((string) $row['date_from']))->format(DATE_ATOM),
            to: (new \DateTimeImmutable((string) $row['date_to']))->format(DATE_ATOM),
            status: (string) $row['status'],
            priceAmount: (int) $row['price_amount'],
            priceCurrency: (string) $row['price_currency'],
        );
    }
}
