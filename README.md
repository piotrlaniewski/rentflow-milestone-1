# RentFlow — Milestone 2

Portfolio project demonstrating Domain-Driven Design, CQRS and framework-independent application logic in PHP 8.4.

## Added in Milestone 2

- `Clock` abstraction and `SystemClock`
- deterministic timestamps in domain events
- `Currency` Value Object
- `Money` using `Currency` instead of a raw string
- `ConfirmReservation` command + handler
- `CancelReservation` command + handler
- reusable test doubles: `FrozenClock`, in-memory repository, pricing and availability fakes
- `ReservationMother`
- expanded domain and application tests

## Domain-Driven Design

- `Reservation` Aggregate Root
- Value Objects: `ReservationId`, `ReservationPeriod`, `Money`, `Currency`, `CustomerId`, `VehicleId`
- domain invariants and domain-specific exceptions
- Domain Events
- repository abstraction
- domain ports: `VehicleAvailability`, `ReservationPricing`

## CQRS write side

Commands:

- `CreateReservation`
- `ConfirmReservation`
- `CancelReservation`

The write side is independent from Symfony, Doctrine and the database.

## Deterministic time

The aggregate no longer creates the current time by itself. Application handlers use `Clock` and pass the concrete timestamp into domain operations.

Production:

```text
SystemClock -> Clock
```

Tests:

```text
FrozenClock -> Clock
```

This makes domain-event timestamps deterministic and easy to test.

## Currency

`Money` no longer stores a raw currency string:

```php
new Money(
    12_345,
    Currency::fromCode('EUR'),
);
```

`Currency` validates an ISO-4217-style code format: exactly three uppercase ASCII letters. It does not claim that every three-letter combination is an officially assigned ISO 4217 currency.

## Requirements

- PHP 8.4+
- Composer

## Install

```bash
composer install
```

## Tests

```bash
composer test
```

## Dependency direction

```text
Infrastructure
      ↓
Application
      ↓
Domain
```

The Domain layer does not depend on Symfony, Doctrine, RabbitMQ or other infrastructure.

## Next milestone

Milestone 3 will add Symfony and persistence:

- Symfony 7
- Dependency Injection
- Doctrine ORM
- PostgreSQL
- `DoctrineReservationRepository`
- migrations
- HTTP API endpoints
- integration tests
- CQRS read side via Doctrine DBAL
