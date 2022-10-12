<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Playoff;
use App\Entity\Season;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Playoff>
 *
 * @method Playoff|null find($id, $lockMode = null, $lockVersion = null)
 * @method Playoff|null findOneBy(array $criteria, array $orderBy = null)
 * @method Playoff[]    findAll()
 * @method Playoff[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlayoffRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Playoff::class);
    }

    public function add(Playoff $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Playoff $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Game[] Playoff games of that season.
     */
    public function findForPlayoffs(Season $season): array
    {
        return $this->createQueryBuilder('p')
            ->from('App\Entity\Game', 'g')
            ->select(['g', 'home', 'away', 'po'])
            ->leftJoin('g.home', 'home')
            ->leftJoin('g.away', 'away')
            ->leftJoin('g.playoff', 'po')
            ->where('g.season = :season and g.playoff is not null')
            ->setParameter('season', $season)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Game[] Playoff games.
     */
    public function findPlayoffGame(Season $season, Playoff $playoff): ?Game
    {
        return $this->createQueryBuilder('p')
            ->from('App\Entity\Game', 'g')
            ->select(['g', 'po'])
            ->leftJoin('g.playoff', 'po')
            ->where('g.season = :season and po.step = :step and po.spot = :spot')
            ->setParameter('season', $season)
            ->setParameter('step', $playoff->getStep())
            ->setParameter('spot', $playoff->getSpot())
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Playoff[] Returns an array of Playoff objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Playoff
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
