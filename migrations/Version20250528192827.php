<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250528192827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD demande_suppression_statut VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD date_demande_suppression_statut TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP demande_suppression_statut');
        $this->addSql('ALTER TABLE signalement DROP date_demande_suppression_statut');
    }
}