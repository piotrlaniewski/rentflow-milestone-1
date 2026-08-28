<?php

declare(strict_types=1);

namespace App\Rental\Infrastructure\Persistence;

use App\Rental\Domain\Reservation\Currency;
use App\Rental\Domain\Reservation\Event\DomainEvent;
use App\Rental\Domain\Reservation\Event\ReservationCancelled;
use App\Rental\Domain\Reservation\Event\ReservationConfirmed;
use App\Rental\Domain\Reservation\Event\ReservationCreated;
use App\Rental\Domain\Reservation\Exception\ReservationNotFound;
use App\Rental\Domain\Reservation\Money;
use App\Rental\Domain\Reservation\Reservation;
use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Rental\Domain\Reservation\ReservationRepository;
use App\Rental\Domain\Reservation\ReservationStatus;
use App\Shared\Domain\CustomerId;
use App\Shared\Domain\VehicleId;
use Doctrine\DBAL\Connection;

final readonly class DbalReservationRepository implements ReservationRepository
{
    public function __construct(private Connection $connection) {}

    public function save(Reservation $reservation): void
    {
        $this->connection->transactional(function () use ($reservation): void {
            $this->connection->executeStatement(
                <<<'SQL'
                    INSERT INTO reservation (
                        id, customer_id, vehicle_id, date_from, date_to, status,
                        price_amount, price_currency, created_at, updated_at
                    ) VALUES (
                        :id, :customerId, :vehicleId, :dateFrom, :dateTo, :status,
                        :priceAmount, :priceCurrency, NOW(), NOW()
                    )
                    ON CONFLICT (id) DO UPDATE SET
                        status = EXCLUDED.status,
                        updated_at = NOW()
                SQL,
                [
                    'id' => $reservation->id()->toString(),
                    'customerId' => $reservation->customerId()->toString(),
                    'vehicleId' => $reservation->vehicleId()->toString(),
                    'dateFrom' => $reservation->period()->from()->format(DATE_ATOM),
                    'dateTo' => $reservation->period()->to()->format(DATE_ATOM),
                    'status' => $reservation->status()->value,
                    'priceAmount' => $reservation->price()->amount(),
                    'priceCurrency' => $reservation->price()->currency()->code(),
                ],
            );

            foreach ($reservation->releaseDomainEvents() as $event) {
                $this->storeOutboxMessage($reservation->id(), $event);
            }
        });
    }

    public function get(ReservationId $id): Reservation
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM reservation WHERE id = :id',
            ['id' => $id->toString()],
        );

        if ($row === false) {
            throw ReservationNotFound::withId($id->toString());
        }

        return Reservation::reconstitute(
            id: ReservationId::fromString((string) $row['id']),
            customerId: CustomerId::fromString((string) $row['customer_id']),
            vehicleId: VehicleId::fromString((string) $row['vehicle_id']),
            period: new ReservationPeriod(
                new \DateTimeImmutable((string) $row['date_from']),
                new \DateTimeImmutable((string) $row['date_to']),
            ),
            status: ReservationStatus::from((string) $row['status']),
            price: new Money(
                (int) $row['price_amount'],
                Currency::fromCode((string) $row['price_currency']),
            ),
        );
    }

    private function storeOutboxMessage(ReservationId $aggregateId, DomainEvent $event): void
    {
        [$type, $payload] = match (true) {
            $event instanceof ReservationCreated => [
                'rentflow.reservation.created.v1',
                [
                    'reservationId' => $event->reservationId->toString(),
                    'customerId' => $event->customerId->toString(),
                    'vehicleId' => $event->vehicleId->toString(),
                    'from' => $event->period->from()->format(DATE_ATOM),
                    'to' => $event->period->to()->format(DATE_ATOM),
                ],
            ],
            $event instanceof ReservationConfirmed => [
                'rentflow.reservation.confirmed.v1',
                ['reservationId' => $event->reservationId->toString()],
            ],
            $event instanceof ReservationCancelled => [
                'rentflow.reservation.cancelled.v1',
                ['reservationId' => $event->reservationId->toString()],
            ],
            default => throw new \LogicException(sprintf('Unsupported domain event "%s".', $event::class)),
        };

        $this->connection->insert('outbox_message', [
            'id' => self::uuidV4(),
            'aggregate_id' => $aggregateId->toString(),
            'event_type' => $type,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'occurred_at' => $event->occurredAt()->format(DATE_ATOM),
            'created_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
        ]);
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

