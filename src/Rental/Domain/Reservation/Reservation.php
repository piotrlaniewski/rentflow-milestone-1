<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

use App\Rental\Domain\Reservation\Event\DomainEvent;
use App\Rental\Domain\Reservation\Event\ReservationCancelled;
use App\Rental\Domain\Reservation\Event\ReservationConfirmed;
use App\Rental\Domain\Reservation\Event\ReservationCreated;
use App\Rental\Domain\Reservation\Exception\ReservationCannotBeCancelled;
use App\Rental\Domain\Reservation\Exception\ReservationCannotBeConfirmed;
use App\Shared\Domain\CustomerId;
use App\Shared\Domain\VehicleId;

final class Reservation
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        private ReservationId $id,
        private CustomerId $customerId,
        private VehicleId $vehicleId,
        private ReservationPeriod $period,
        private ReservationStatus $status,
        private Money $price,
    ) {
    }

    public static function create(
        ReservationId $id,
        CustomerId $customerId,
        VehicleId $vehicleId,
        ReservationPeriod $period,
        Money $price,
        \DateTimeImmutable $occurredAt,
    ): self {
        $reservation = new self(
            id: $id,
            customerId: $customerId,
            vehicleId: $vehicleId,
            period: $period,
            status: ReservationStatus::Pending,
            price: $price,
        );

        $reservation->record(new ReservationCreated(
            reservationId: $id,
            customerId: $customerId,
            vehicleId: $vehicleId,
            period: $period,
            occurredAt: $occurredAt,
        ));

        return $reservation;
    }

    public function confirm(\DateTimeImmutable $occurredAt): void
    {
        if ($this->status !== ReservationStatus::Pending) {
            throw ReservationCannotBeConfirmed::becauseOfStatus($this->status);
        }

        $this->status = ReservationStatus::Confirmed;
        $this->record(new ReservationConfirmed($this->id, $occurredAt));
    }

    public function cancel(\DateTimeImmutable $occurredAt): void
    {
        if ($this->status === ReservationStatus::Cancelled || $this->status === ReservationStatus::Completed) {
            throw ReservationCannotBeCancelled::becauseOfStatus($this->status);
        }

        $this->status = ReservationStatus::Cancelled;
        $this->record(new ReservationCancelled($this->id, $occurredAt));
    }

    public function id(): ReservationId { return $this->id; }
    public function customerId(): CustomerId { return $this->customerId; }
    public function vehicleId(): VehicleId { return $this->vehicleId; }
    public function period(): ReservationPeriod { return $this->period; }
    public function status(): ReservationStatus { return $this->status; }
    public function price(): Money { return $this->price; }

    /** @return list<DomainEvent> */
    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
