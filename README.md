# RentFlow

Portfolio rental API demonstrating PHP 8.4, Symfony 7.4 LTS, tactical DDD,
CQRS, PostgreSQL consistency constraints and transactional outbox publishing
to Apache Kafka.

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
                                                      |
                                      publisher -> Kafka topic

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

Start the API, database, Kafka broker, migrations and outbox publisher:

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

Inspect the application and publisher logs:

```bash
docker compose logs -f php outbox-publisher
```

Publish one batch manually instead of running the long-lived publisher:

```bash
docker compose run --rm outbox-publisher php bin/console app:outbox:publish --limit=50
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
A dedicated worker claims rows in batches using `FOR UPDATE SKIP LOCKED`, then
publishes JSON event envelopes to `rentflow.reservation-events.v1`. Aggregate
IDs are Kafka message keys, which preserves per-reservation ordering.

Claims expire after five minutes, so an interrupted worker does not strand a
message. Failed deliveries are retried up to ten times; terminal failures keep
their diagnostic details in the outbox for inspection. Delivery is at least
once, therefore future consumers must process event IDs idempotently.

## Test strategy

- domain unit tests verify invariants and state transitions;
- application unit tests use in-memory ports and a frozen clock;
- outbox publisher unit tests cover success, retry and terminal failure paths;
- persistence and HTTP integration tests are the next addition now that the
  executable Symfony boundary exists.
