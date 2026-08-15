# RentFlow — Milestone 1

## Included

- Reservation Aggregate Root
- Value Objects
- Domain Events
- Domain-specific exceptions
- Repository abstraction
- Domain services / ports
- CreateReservation Command + Handler
- Unit tests without Symfony or Doctrine

## Requirements

- PHP 8.4+
- Composer

## Install

```bash
composer install
```

## Run tests

```bash
composer test
```

## Structure

```text
src/
├── Rental/
│   ├── Domain/
│   └── Application/
└── Shared/
    └── Domain/

tests/
└── Rental/
    ├── Domain/
    └── Application/
```

## Next milestone

- Clock abstraction
- ConfirmReservation command
- CancelReservation command
- Symfony integration
- Doctrine/PostgreSQL infrastructure
- CQRS read model via Doctrine DBAL
