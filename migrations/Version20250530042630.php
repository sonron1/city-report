<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530042630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des départements et arrondissements avec mise à jour des données existantes';
    }

    public function up(Schema $schema): void
    {
        // Étape 1: Créer la table departement
        $this->addSql('CREATE TABLE departement (
            id SERIAL PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE
        )');
        
        // Étape 2: Insérer les départements
        $this->addSql("INSERT INTO departement (nom, description, slug) VALUES 
            ('Littoral', 'Département du sud du Bénin, comprenant Cotonou', 'littoral')");
            
        // Étape 3: Ajouter la colonne departement_id à ville (initialement nullable)
        $this->addSql('ALTER TABLE ville ADD departement_id INT DEFAULT NULL');
        
        // Étape 4: Assigner un département par défaut à toutes les villes existantes
        $this->addSql('UPDATE ville SET departement_id = (SELECT id FROM departement WHERE nom = \'Littoral\')');
        
        // Étape 5: Ajouter la contrainte de clé étrangère
        $this->addSql('ALTER TABLE ville ADD CONSTRAINT FK_43C3D9C3CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('CREATE INDEX IDX_43C3D9C3CCF9E01E ON ville (departement_id)');
        
        // Étape 6: Modifier la contrainte pour rendre departement_id NOT NULL
        $this->addSql('ALTER TABLE ville ALTER COLUMN departement_id SET NOT NULL');
        
        // Étape 7: Créer la table arrondissement
        $this->addSql('CREATE TABLE arrondissement (
            id SERIAL PRIMARY KEY,
            ville_id INT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            CONSTRAINT FK_3A2C01B3A73F0036 FOREIGN KEY (ville_id) REFERENCES ville (id)
        )');
        $this->addSql('CREATE INDEX IDX_3A2C01B3A73F0036 ON arrondissement (ville_id)');
        
        // Étape 8: Ajouter arrondissement_id à la table signalement
        $this->addSql('ALTER TABLE signalement ADD arrondissement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114B5140B3D FOREIGN KEY (arrondissement_id) REFERENCES arrondissement (id)');
        $this->addSql('CREATE INDEX IDX_F4B55114B5140B3D ON signalement (arrondissement_id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer dans l'ordre inverse
        $this->addSql('ALTER TABLE signalement DROP CONSTRAINT FK_F4B55114B5140B3D');
        $this->addSql('DROP INDEX IDX_F4B55114B5140B3D ON signalement');
        $this->addSql('ALTER TABLE signalement DROP arrondissement_id');
        
        $this->addSql('ALTER TABLE ville DROP CONSTRAINT FK_43C3D9C3CCF9E01E');
        $this->addSql('DROP INDEX IDX_43C3D9C3CCF9E01E ON ville');
        $this->addSql('ALTER TABLE ville DROP departement_id');
        
        $this->addSql('DROP TABLE arrondissement');
        $this->addSql('DROP TABLE departement');
    }
}