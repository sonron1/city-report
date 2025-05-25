<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250524223505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE categorie (id SERIAL NOT NULL, nom VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE cluster (id SERIAL NOT NULL, ville_id INT NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, rayon DOUBLE PRECISION NOT NULL, nombre_signalements INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E5C56994A73F0036 ON cluster (ville_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE commentaire (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, signalement_id INT NOT NULL, contenu TEXT NOT NULL, date_commentaire TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, etat_validation VARCHAR(50) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_67F068BCFB88E14F ON commentaire (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_67F068BC65C5E57E ON commentaire (signalement_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE journal_validation (id SERIAL NOT NULL, signalement_id INT NOT NULL, moderateur_id INT NOT NULL, date_validation TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, action VARCHAR(50) NOT NULL, commentaire TEXT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8BCC417A65C5E57E ON journal_validation (signalement_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8BCC417A20A01F78 ON journal_validation (moderateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id SERIAL NOT NULL, destinataire_id INT NOT NULL, signalement_id INT DEFAULT NULL, message TEXT NOT NULL, date_envoi TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CAA4F84F6E ON notification (destinataire_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CA65C5E57E ON notification (signalement_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reparation (id SERIAL NOT NULL, signalement_id INT NOT NULL, utilisateur_id INT NOT NULL, description TEXT NOT NULL, date_debut TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_fin TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8FDF219D65C5E57E ON reparation (signalement_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8FDF219DFB88E14F ON reparation (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE route (id SERIAL NOT NULL, nom VARCHAR(255) NOT NULL, longueur DOUBLE PRECISION DEFAULT NULL, voies INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE signalement (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, categorie_id INT NOT NULL, ville_id INT NOT NULL, cluster_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, description TEXT NOT NULL, photo_url VARCHAR(255) NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, date_signalement TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, statut VARCHAR(50) NOT NULL, priorite INT NOT NULL, etat_validation VARCHAR(50) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F4B55114FB88E14F ON signalement (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F4B55114BCF5E72D ON signalement (categorie_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F4B55114A73F0036 ON signalement (ville_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F4B55114C36A3328 ON signalement (cluster_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE utilisateur (id SERIAL NOT NULL, ville_residence_id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, date_inscription TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, est_valide BOOLEAN NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1D1C63B3FD871CC9 ON utilisateur (ville_residence_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON utilisateur (email)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE ville (id SERIAL NOT NULL, nom VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, latitude_centre DOUBLE PRECISION NOT NULL, longitude_centre DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_43C3D9C3989D9B62 ON ville (slug)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.available_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.delivered_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                BEGIN
                    PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                    RETURN NEW;
                END;
            $$ LANGUAGE plpgsql;
        SQL);
        $this->addSql(<<<'SQL'
            DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cluster ADD CONSTRAINT FK_E5C56994A73F0036 FOREIGN KEY (ville_id) REFERENCES ville (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE journal_validation ADD CONSTRAINT FK_8BCC417A65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE journal_validation ADD CONSTRAINT FK_8BCC417A20A01F78 FOREIGN KEY (moderateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reparation ADD CONSTRAINT FK_8FDF219D65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reparation ADD CONSTRAINT FK_8FDF219DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114A73F0036 FOREIGN KEY (ville_id) REFERENCES ville (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114C36A3328 FOREIGN KEY (cluster_id) REFERENCES cluster (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3FD871CC9 FOREIGN KEY (ville_residence_id) REFERENCES ville (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cluster DROP CONSTRAINT FK_E5C56994A73F0036
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire DROP CONSTRAINT FK_67F068BCFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire DROP CONSTRAINT FK_67F068BC65C5E57E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE journal_validation DROP CONSTRAINT FK_8BCC417A65C5E57E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE journal_validation DROP CONSTRAINT FK_8BCC417A20A01F78
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP CONSTRAINT FK_BF5476CAA4F84F6E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP CONSTRAINT FK_BF5476CA65C5E57E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reparation DROP CONSTRAINT FK_8FDF219D65C5E57E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reparation DROP CONSTRAINT FK_8FDF219DFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement DROP CONSTRAINT FK_F4B55114FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement DROP CONSTRAINT FK_F4B55114BCF5E72D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement DROP CONSTRAINT FK_F4B55114A73F0036
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE signalement DROP CONSTRAINT FK_F4B55114C36A3328
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE utilisateur DROP CONSTRAINT FK_1D1C63B3FD871CC9
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE categorie
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE cluster
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commentaire
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE journal_validation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reparation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE route
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE signalement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ville
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
