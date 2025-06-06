<?php

namespace App\Enum;

enum EtatValidation: string
{
    case EN_ATTENTE = 'en_attente';
    case VALIDE = 'validé';
    case REJETE = 'rejeté';
    
    /**
     * Retourne le libellé formaté pour l'affichage
     */
    public function libelle(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDE => 'Validé',
            self::REJETE => 'Rejeté'
        };
    }
    
    /**
     * Retourne la classe CSS Bootstrap associée à l'état
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'bg-warning',
            self::VALIDE => 'bg-success',
            self::REJETE => 'bg-danger'
        };
    }
    
    /**
     * Retourne tous les états disponibles
     */
    public static function getChoices(): array
    {
        return [
            'En attente' => self::EN_ATTENTE->value,
            'Validé' => self::VALIDE->value,
            'Rejeté' => self::REJETE->value
        ];
    }
}