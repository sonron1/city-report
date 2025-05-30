<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530040256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE arrondissement (id SERIAL NOT NULL, ville_id INT NOT NULL, nom VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_3A3B64C4989D9B62 ON arrondissement (slug)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3A3B64C4A73F0036 ON arrondissement (ville_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE departement (id SERIAL NOT NULL, nom VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C1765B63989D9B62 ON departement (slug)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE arrondissement ADD CONSTRAINT FK_3A3B64C4A73F0036 FOREIGN KEY (ville_id) REFERENCES ville (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD arrondissement_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114407DBC11 FOREIGN KEY (arrondissement_id) REFERENCES arrondissement (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F4B55114407DBC11 ON signalement (arrondissement_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ville ADD departement_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ville ADD CONSTRAINT FK_43C3D9C3CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_43C3D9C3CCF9E01E ON ville (departement_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement DROP CONSTRAINT FK_F4B55114407DBC11
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ville DROP CONSTRAINT FK_43C3D9C3CCF9E01E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE arrondissement DROP CONSTRAINT FK_3A3B64C4A73F0036
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE arrondissement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE departement
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F4B55114407DBC11
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement DROP arrondissement_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_43C3D9C3CCF9E01E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ville DROP departement_id
        SQL);
    }
}
