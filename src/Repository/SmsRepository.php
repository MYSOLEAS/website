<?php

namespace App\Repository;

use App\Entity\Sms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sms|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sms|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sms[]    findAll()
 * @method Sms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sms::class);
    }

    // /**
    //  * @return Sms[] Returns an array of Sms objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Sms
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function pendingByOperator($operator){
        return $this->createQueryBuilder('s')
            ->where('s.operator = :operator')
            ->andWhere('s.status = :status AND s.sendable != :status')
            ->leftJoin('s.user', 'u')
            ->addSelect('u')
            ->andWhere('u.smsDeadline >= :today')
            ->setParameter('operator', $operator)
            ->setParameter('status', false)
            ->setParameter('today', new \DateTime('now'))
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(25)
            ->getQuery()
            ->getResult()
        ;
    }

    public function pending(){
        return $this->createQueryBuilder('s')
            ->where('s.status = :status AND s.sendable != :status')
            ->leftJoin('s.user', 'u')
            ->addSelect('u')
            ->andWhere('u.smsDeadline >= :today')
            ->setParameter('today', new \DateTime('now'))
            ->setParameter('status', false)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(25)
            ->getQuery()
            ->getResult()
        ;
    }
}
