<?php

declare(strict_types=1);

namespace App\Rental\UI\Http;

use App\Rental\Application\Command\CancelReservation\CancelReservation;
use App\Rental\Application\Command\ConfirmReservation\ConfirmReservation;
use App\Rental\Application\Command\CreateReservation\CreateReservation;
use App\Rental\Application\Exception\VehicleNotAvailable;
use App\Rental\Application\Query\GetReservation\GetReservation;
use App\Rental\Application\Query\GetReservation\ReservationView;
use App\Rental\Domain\Reservation\Exception\ReservationNotFound;
use App\Rental\Domain\Reservation\ReservationId;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Exception\JsonException as HttpJsonException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reservations')]
final readonly class ReservationController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {
    }

    #[Route('', name: 'reservation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
            $reservationId = ReservationId::generate()->toString();

            $this->dispatchCommand(new CreateReservation(
                reservationId: $reservationId,
                customerId: self::requiredString($payload, 'customerId'),
                vehicleId: self::requiredString($payload, 'vehicleId'),
                from: new \DateTimeImmutable(self::requiredString($payload, 'from')),
                to: new \DateTimeImmutable(self::requiredString($payload, 'to')),
            ));

            return new JsonResponse(
                ['id' => $reservationId, 'status' => 'pending'],
                Response::HTTP_CREATED,
                ['Location' => '/api/reservations/' . $reservationId],
            );
        } catch (VehicleNotAvailable|ConstraintViolationException $exception) {
            return self::error(
                Response::HTTP_CONFLICT,
                'reservation_conflict',
                'The vehicle is not available for the requested period.',
            );
        } catch (\DomainException $exception) {
            return self::error(Response::HTTP_UNPROCESSABLE_ENTITY, 'invalid_reservation', $exception->getMessage());
        } catch (\InvalidArgumentException|\DateMalformedStringException|HttpJsonException $exception) {
            return self::error(Response::HTTP_BAD_REQUEST, 'invalid_request', $exception->getMessage());
        }
    }

    #[Route('/{reservationId}', name: 'reservation_get', methods: ['GET'])]
    public function get(string $reservationId): JsonResponse
    {
        try {
            $envelope = $this->dispatchQuery(new GetReservation($reservationId));
            $view = $envelope->last(HandledStamp::class)?->getResult();

            if (!$view instanceof ReservationView) {
                throw new \LogicException('The reservation query did not return a reservation view.');
            }

            return new JsonResponse($view->toArray());
        } catch (ReservationNotFound $exception) {
            return self::error(Response::HTTP_NOT_FOUND, 'reservation_not_found', $exception->getMessage());
        }
    }

    #[Route('/{reservationId}/confirm', name: 'reservation_confirm', methods: ['POST'])]
    public function confirm(string $reservationId): JsonResponse
    {
        return $this->changeStatus(
            fn () => $this->dispatchCommand(new ConfirmReservation($reservationId)),
            'confirmed',
        );
    }

    #[Route('/{reservationId}/cancel', name: 'reservation_cancel', methods: ['POST'])]
    public function cancel(string $reservationId): JsonResponse
    {
        return $this->changeStatus(
            fn () => $this->dispatchCommand(new CancelReservation($reservationId)),
            'cancelled',
        );
    }

    /** @param callable(): mixed $operation */
    private function changeStatus(callable $operation, string $status): JsonResponse
    {
        try {
            $operation();

            return new JsonResponse(['status' => $status]);
        } catch (ReservationNotFound $exception) {
            return self::error(Response::HTTP_NOT_FOUND, 'reservation_not_found', $exception->getMessage());
        } catch (\DomainException $exception) {
            return self::error(Response::HTTP_CONFLICT, 'invalid_reservation_state', $exception->getMessage());
        }
    }

    /** @param array<string, mixed> $payload */
    private static function requiredString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException(sprintf('Field "%s" must be a non-empty string.', $key));
        }

        return $value;
    }

    private static function error(int $status, string $code, string $message): JsonResponse
    {
        return new JsonResponse(
            ['error' => ['code' => $code, 'message' => $message]],
            $status,
        );
    }

    private function dispatchCommand(object $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $exception) {
            throw $exception->getPrevious() ?? $exception;
        }
    }

    private function dispatchQuery(object $query): \Symfony\Component\Messenger\Envelope
    {
        try {
            return $this->queryBus->dispatch($query);
        } catch (HandlerFailedException $exception) {
            throw $exception->getPrevious() ?? $exception;
        }
    }
}
