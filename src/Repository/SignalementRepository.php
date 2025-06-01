<?php

namespace App\Repository;

use App\Entity\Signalement;
use App\Enum\EtatValidation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SignalementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Signalement::class);
    }

    /**
     * Recherche les signalements selon différents critères de filtrage
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.ville', 'v')
            ->leftJoin('s.categorie', 'c')
            ->addSelect('v', 'c');

        // Filtrer par état de validation (utilise validé par défaut si non spécifié)
        if (!empty($filters['etat'])) {
            $qb->andWhere('s.etatValidation = :etat')
                ->setParameter('etat', $filters['etat']);
        } else {
            // Par défaut, on filtre sur les signalements validés
            $qb->andWhere('s.etatValidation = :etat')
                ->setParameter('etat', EtatValidation::VALIDE->value);
        }

        // Filtrer par ville seulement si spécifié
        if (!empty($filters['ville'])) {
            $qb->andWhere('v.id = :villeId')
                ->setParameter('villeId', $filters['ville']);
        }

        // Filtrer par catégorie seulement si spécifié
        if (!empty($filters['categorie'])) {
            $qb->andWhere('c.id = :categorieId')
                ->setParameter('categorieId', $filters['categorie']);
        }

        // Filtrer par date si spécifié
        if (!empty($filters['date_du'])) {
            $qb->andWhere('s.dateSignalement >= :dateDu')
                ->setParameter('dateDu', $filters['date_du']);
        }

        if (!empty($filters['date_au'])) {
            $qb->andWhere('s.dateSignalement <= :dateAu')
                ->setParameter('dateAu', $filters['date_au']);
        }

        // Ordonner par date (les plus récents d'abord)
        $qb->orderBy('s.dateSignalement', 'DESC');

        return $qb->getQuery()->getResult();
    }
    
    /**
     * Récupère les signalements validés pour la page d'accueil
     */
    public function findValidated(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.etatValidation = :etat')
            ->setParameter('etat', EtatValidation::VALIDE->value)
            ->orderBy('s.dateSignalement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    // ... autres méthodes ...
}