<?php

namespace App\Repository;

use App\Entity\PortfolioCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PortfolioCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method PortfolioCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method PortfolioCategory[]    findAll()
 * @method PortfolioCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PortfolioCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioCategory::class);
    }

    // /**
    //  * @return PortfolioCategory[] Returns an array of PortfolioCategory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PortfolioCategory
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
