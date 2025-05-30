<?php

namespace App\Repository;

use App\Entity\Ville;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

// src/Repository/VilleRepository.php

// Ajoutez ces imports en haut du fichier
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Utils\Paginator as PaginatorResult;

/**
 * @extends ServiceEntityRepository<Ville>
 */
class VilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ville::class);
    }

    //    /**
    //     * @return Ville[] Returns an array of Ville objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('v.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Ville
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

// Ajoutez cette méthode dans la classe VilleRepository
public function findPaginated(int $page = 1, int $limit = 10): PaginatorResult
{
    $query = $this->createQueryBuilder('v')
        ->orderBy('v.nom', 'ASC')
        ->getQuery();

    $paginator = new Paginator($query);
    $paginator
        ->getQuery()
        ->setFirstResult($limit * ($page - 1))
        ->setMaxResults($limit);

    return new PaginatorResult($paginator, $page, $limit);
}
}