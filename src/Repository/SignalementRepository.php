<?php

namespace App\Repository;

use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Enum\EtatValidation;
use App\Pagination\Paginator;  // Assurez-vous que cette classe existe
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;  // Ajouter cette importation
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;  // Ajouter cette importation
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

  /**
   * Récupère les signalements de l'utilisateur avec pagination
   */
  public function findUserSignalementsPaginated(
      Utilisateur $user,
      int $page = 1,
      int $limit = 10,
      array $filters = []
  ): Paginator {
    $qb = $this->createQueryBuilder('s')
        ->where('s.utilisateur = :user')
        ->setParameter('user', $user)
        ->orderBy('s.dateSignalement', 'DESC');

    // Appliquer les filtres
    $this->applyFilters($qb, $filters);

    $query = $qb->getQuery();

    // Configurer la pagination
    $query->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit);

    return new Paginator(new DoctrinePaginator($query), $page, $limit);
  }

  /**
   * Récupère tous les signalements publics avec pagination
   */
  public function findAllPublicSignalementsPaginated(
      int $page = 1,
      int $limit = 10,
      array $filters = []
  ): Paginator {
    $qb = $this->createQueryBuilder('s')
        ->where('s.etatValidation = :validated')
        ->setParameter('validated', 'validé')
        ->orderBy('s.dateSignalement', 'DESC');

    // Appliquer les filtres
    $this->applyFilters($qb, $filters);

    $query = $qb->getQuery();

    // Configurer la pagination
    $query->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit);
    return new Paginator(new DoctrinePaginator($query), $page, $limit);
  }

  /**
   * Récupère tous les signalements avec pagination (pour les modérateurs)
   * Cette méthode permet aux modérateurs de voir tous les signalements, y compris non-validés
   */
  public function findAllSignalementsPaginated(
      int $page = 1,
      int $limit = 10,
      array $filters = [],
      bool $includePending = false
  ): Paginator {
    $qb = $this->createQueryBuilder('s')
        ->orderBy('s.dateSignalement', 'DESC');

    // Si on n'inclut pas les signalements en attente, filtrer sur les validés uniquement
    if (!$includePending) {
      $qb->where('s.etatValidation = :validated')
          ->setParameter('validated', 'validé');
    }

    // Appliquer les filtres
    $this->applyFilters($qb, $filters);

    $query = $qb->getQuery();

    // Configurer la pagination
    $query->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit);

    return new Paginator(new DoctrinePaginator($query), $page, $limit);
  }

  /**
   * Compte les signalements d'un utilisateur par statut
   */
  public function countUserSignalementsByStatus(Utilisateur $user, string $status): int
  {
    return $this->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->where('s.utilisateur = :user')
        ->andWhere('s.statut = :status')
        ->setParameter('user', $user)
        ->setParameter('status', $status)
        ->getQuery()
        ->getSingleScalarResult();
  }

  /**
   * Applique les filtres à une requête
   */
  private function applyFilters(QueryBuilder $qb, array $filters): void
  {
    // Filtre par statut
    if (!empty($filters['statut'])) {
      $qb->andWhere('s.statut = :statut')
          ->setParameter('statut', $filters['statut']);
    }

    // Filtre par catégorie
    if (!empty($filters['categorie'])) {
      // Si la catégorie est déjà un entier, on le laisse tel quel
      $categorieId = is_numeric($filters['categorie']) ? $filters['categorie'] : null;

      if ($categorieId) {
        $qb->andWhere('s.categorie = :categorieId')
            ->setParameter('categorieId', $categorieId);
      }
    }

    // Filtre par ville
    if (!empty($filters['ville'])) {
      // Si la ville est déjà un entier, on le laisse tel quel
      $villeId = is_numeric($filters['ville']) ? $filters['ville'] : null;

      if ($villeId) {
        $qb->andWhere('s.ville = :villeId')
            ->setParameter('villeId', $villeId);
      }
    }

    // Filtre par date
    if (!empty($filters['date'])) {
      $now = new \DateTime();
      switch ($filters['date']) {
        case 'today':
          $start = new \DateTime($now->format('Y-m-d') . ' 00:00:00');
          $qb->andWhere('s.dateSignalement >= :start')
              ->setParameter('start', $start);
          break;
        case 'week':
          $start = new \DateTime($now->format('Y-m-d') . ' 00:00:00');
          $start->modify('-' . $start->format('w') . ' days');
          $qb->andWhere('s.dateSignalement >= :start')
              ->setParameter('start', $start);
          break;
        case 'month':
          $start = new \DateTime($now->format('Y-m-01') . ' 00:00:00');
          $qb->andWhere('s.dateSignalement >= :start')
              ->setParameter('start', $start);
          break;
      }
    }
  }

  /**
   * Récupère les signalements d'une ville spécifique
   */
  public function findByVille(Ville $ville, int $limit = null): array
  {
    $qb = $this->createQueryBuilder('s')
        ->where('s.ville = :ville')
        ->andWhere('s.etatValidation = :validated')
        ->setParameter('ville', $ville)
        ->setParameter('validated', EtatValidation::VALIDE->value)
        ->orderBy('s.dateSignalement', 'DESC');

    if ($limit) {
      $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
  }

}