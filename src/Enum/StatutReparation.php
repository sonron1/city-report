<?php

namespace App\Enum;

enum StatutReparation: string
{
    case PLANIFIEE = 'planifiee';
    case EN_COURS = 'en_cours';
    case TERMINEE = 'terminee';
    
    /**
     * Retourne le libellé formaté pour l'affichage
     */
    public function libelle(): string
    {
        return match($this) {
            self::PLANIFIEE => 'Planifiée',
            self::EN_COURS => 'En cours',
            self::TERMINEE => 'Terminée'
        };
    }
    
    /**
     * Retourne la classe CSS Bootstrap associée au statut
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::PLANIFIEE => 'bg-info',
            self::EN_COURS => 'bg-warning',
            self::TERMINEE => 'bg-success'
        };
    }
    
    /**
     * Retourne tous les statuts disponibles
     */
    public static function getChoices(): array
    {
        return [
            'Planifiée' => self::PLANIFIEE->value,
            'En cours' => self::EN_COURS->value,
            'Terminée' => self::TERMINEE->value
        ];
    }
}