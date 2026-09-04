<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add leased outbox claiming, retry diagnostics and permanent failure state.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE outbox_message ADD claimed_at TIMESTAMPTZ DEFAULT NULL');
        $this->addSql('ALTER TABLE outbox_message ADD claim_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE outbox_message ADD last_error VARCHAR(2000) DEFAULT NULL');
        $this->addSql('ALTER TABLE outbox_message ADD failed_at TIMESTAMPTZ DEFAULT NULL');
        $this->addSql('DROP INDEX outbox_unpublished_idx');
        $this->addSql(<<<'SQL'
            CREATE INDEX outbox_claimable_idx
            ON outbox_message (created_at)
            WHERE published_at IS NULL AND failed_at IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX outbox_claimable_idx');
        $this->addSql('CREATE INDEX outbox_unpublished_idx ON outbox_message (created_at) WHERE published_at IS NULL');
        $this->addSql('ALTER TABLE outbox_message DROP claimed_at');
        $this->addSql('ALTER TABLE outbox_message DROP claim_id');
        $this->addSql('ALTER TABLE outbox_message DROP last_error');
        $this->addSql('ALTER TABLE outbox_message DROP failed_at');
    }
}
