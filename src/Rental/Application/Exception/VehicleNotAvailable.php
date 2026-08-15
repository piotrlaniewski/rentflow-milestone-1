<?php

declare(strict_types=1);

namespace App\Rental\Application\Exception;

final class VehicleNotAvailable extends \RuntimeException
{
    public static function create(string $vehicleId): self
    {
        return new self(
            sprintf(
                'Vehicle "%s" is not available for requested period.',
                $vehicleId,
            )
        );
    }
}
