<?php

namespace App\Repository;

use App\Entity\OrderMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrderMap|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderMap|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderMap[]    findAll()
 * @method OrderMap[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderMapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderMap::class);
    }

    // /**
    //  * @return OrderMap[] Returns an array of OrderMap objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OrderMap
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
