# RentFlow

Portfolio rental API demonstrating PHP 8.4, Symfony 7.4 LTS, tactical DDD,
CQRS, PostgreSQL consistency constraints and a transactional outbox prepared
for Kafka publishing.

## Architecture

RentFlow is a modular monolith. The `Rental` bounded context is split into:

- `Domain` — aggregate, value objects, domain events and ports;
- `Application` — commands, queries and use-case handlers;
- `Infrastructure` — PostgreSQL adapters, pricing and the read model;
- `UI` — HTTP controllers.

The domain and application command handlers do not depend on Symfony or
Doctrine. Symfony Messenger provides separate `command.bus` and `query.bus`
instances at the composition-root level.

```text
HTTP -> command.bus -> application handler -> aggregate
                                      |          |
                                      +-> reservation + outbox (one transaction)

HTTP -> query.bus -> DBAL read handler -> JSON view
```

The PostgreSQL exclusion constraint is the final consistency guard against two
active reservations for the same vehicle and overlapping time periods. The
availability query improves the user-facing error path, but correctness does not
depend on a race-prone check-before-write sequence.

## Run locally

Requirements:

- Docker Desktop with Linux containers;
- Docker Compose.

Start the API and run migrations:

```bash
cp .env.example .env
# Set APP_SECRET and POSTGRES_PASSWORD in .env before starting containers.
docker compose up --build -d
```

The API is available at `http://localhost:8080`; its health endpoint is
`GET /health`.

Run tests:

```bash
docker compose exec php composer test
```

Stop the containers without deleting PostgreSQL data:

```bash
docker compose down
```

## API example

Create a reservation:

```bash
curl -i http://localhost:8080/api/reservations \
  -H 'Content-Type: application/json' \
  -d '{
    "customerId": "customer-1",
    "vehicleId": "vehicle-1",
    "from": "2026-09-01T10:00:00+00:00",
    "to": "2026-09-05T10:00:00+00:00"
  }'
```

Use the returned `id` with:

```text
GET  /api/reservations/{id}
POST /api/reservations/{id}/confirm
POST /api/reservations/{id}/cancel
```

Money is represented in minor units, so `30000 PLN` means `300.00 PLN`.

## Transactional outbox

Every aggregate save and its domain-event serialization happen inside one
PostgreSQL transaction. Unpublished messages are stored in `outbox_message`.
The next milestone will add a Kafka publisher worker, idempotent consumers,
retry policy and a dead-letter topic without changing the domain model.

## Test strategy

- domain unit tests verify invariants and state transitions;
- application unit tests use in-memory ports and a frozen clock;
- persistence and HTTP integration tests are the next addition now that the
  executable Symfony boundary exists.
