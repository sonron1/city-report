<?php

namespace App\Repository;

use App\Entity\Ville;
use App\Entity\Arrondissement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VilleRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Ville::class);
  }

  /**
   * Retourne une liste de villes sans doublons basée sur le nom et le département
   *
   * @return array<int, Ville>
   */
  public function findVillesUniques(): array
  {
    $entityManager = $this->getEntityManager();

    // Sous-requête pour obtenir les IDs uniques
    $query = $entityManager->createQuery('
            SELECT MIN(v.id) as id
            FROM App\Entity\Ville v
            GROUP BY v.nom, d.id
        ');

    $ids = array_column($query->getScalarResult(), 'id');

    // Requête principale pour récupérer les entités complètes
    if (empty($ids)) {
      return [];
    }

    $queryBuilder = $this->createQueryBuilder('v')
        ->where('v.id IN (:ids)')
        ->setParameter('ids', $ids)
        ->leftJoin('v.departement', 'd')
        ->addSelect('d') // Fetch join pour éviter les requêtes N+1
        ->orderBy('v.nom', 'ASC')
        ->addOrderBy('d.nom', 'ASC');

    return $queryBuilder->getQuery()->getResult();
  }

  /**
   * Retourne les villes du Bénin
   *
   * @return array<int, Ville>
   */
  public function findVillesDuBenin(): array
  {
    // Liste des principales villes du Bénin
    $villesBeninNomsPartiels = [
        'Cotonou', 'Porto-Novo', 'Parakou', 'Abomey', 'Djougou',
        'Natitingou', 'Ouidah', 'Bohicon', 'Lokossa', 'Kandi',
        'Abomey-Calavi', 'Dassa-Zoumé', 'Savè', 'Nikki', 'Pobè',
        'Bembèrèkè', 'Savalou', 'Malanville', 'Comè', 'Dogbo'
    ];

    // Construction d'une requête LIKE pour trouver les villes correspondant à ces noms
    $qb = $this->createQueryBuilder('v');

    $orX = $qb->expr()->orX();
    foreach ($villesBeninNomsPartiels as $index => $villeNom) {
      $orX->add($qb->expr()->like('v.nom', ':ville' . $index));
      $qb->setParameter('ville' . $index, '%' . $villeNom . '%');
    }

    $qb->where($orX)
        ->orderBy('v.nom', 'ASC');

    return $qb->getQuery()->getResult();
  }

  /**
   * Retourne les arrondissements d'une ville
   *
   * @param int $villeId
   * @return array<int, Arrondissement>
   */
  public function findArrondissementsByVilleId(int $villeId): array
  {
    // Optimisation : requête directe pour les arrondissements
    $queryBuilder = $this->getEntityManager()->createQueryBuilder()
        ->select('a')
        ->from(Arrondissement::class, 'a')
        ->where('a.ville = :villeId')
        ->setParameter('villeId', $villeId)
        ->orderBy('a.nom', 'ASC');

    return $queryBuilder->getQuery()->getResult();
  }
}