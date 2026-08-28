<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reservation write/read table, overlap constraint and transactional outbox.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS btree_gist');
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation (
                id UUID NOT NULL,
                customer_id VARCHAR(255) NOT NULL,
                vehicle_id VARCHAR(255) NOT NULL,
                date_from TIMESTAMPTZ NOT NULL,
                date_to TIMESTAMPTZ NOT NULL,
                status VARCHAR(32) NOT NULL,
                price_amount BIGINT NOT NULL,
                price_currency CHAR(3) NOT NULL,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL,
                PRIMARY KEY(id),
                CONSTRAINT reservation_period_valid CHECK (date_from < date_to),
                CONSTRAINT reservation_price_non_negative CHECK (price_amount >= 0)
            )
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation
            ADD CONSTRAINT reservation_vehicle_period_excl
            EXCLUDE USING GIST (
                vehicle_id WITH =,
                tstzrange(date_from, date_to, '[)') WITH &&
            ) WHERE (status IN ('pending', 'confirmed'))
        SQL);
        $this->addSql('CREATE INDEX reservation_customer_idx ON reservation (customer_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE outbox_message (
                id UUID NOT NULL,
                aggregate_id UUID NOT NULL,
                event_type VARCHAR(255) NOT NULL,
                payload JSONB NOT NULL,
                occurred_at TIMESTAMPTZ NOT NULL,
                created_at TIMESTAMPTZ NOT NULL,
                published_at TIMESTAMPTZ DEFAULT NULL,
                attempts INT NOT NULL DEFAULT 0,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE INDEX outbox_unpublished_idx ON outbox_message (created_at) WHERE published_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE outbox_message');
        $this->addSql('DROP TABLE reservation');
    }
}

