<?php

declare(strict_types=1);

namespace App\Rental\Application\Query\GetReservation;

final readonly class ReservationView
{
    public function __construct(
        public string $id,
        public string $customerId,
        public string $vehicleId,
        public string $from,
        public string $to,
        public string $status,
        public int $priceAmount,
        public string $priceCurrency,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customerId,
            'vehicleId' => $this->vehicleId,
            'from' => $this->from,
            'to' => $this->to,
            'status' => $this->status,
            'price' => [
                'amount' => $this->priceAmount,
                'currency' => $this->priceCurrency,
            ],
        ];
    }
}
