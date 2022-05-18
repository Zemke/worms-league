<?php

namespace App\Repository;

use App\Entity\Ranking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;
use App\Entity\Season;
use App\Entity\User;

/**
 * @method Ranking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ranking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ranking[]    findAll()
 * @method Ranking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RankingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ranking::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Ranking $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Ranking $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findOneOrCreate(User $owner, Season $season): Ranking
    {
        $ranking = $this->findOneByOwner($owner);
        if (is_null($ranking)) {
            $ranking = (new Ranking())
                ->setOwner($owner)
                ->setSeason($season);
            $this->_em->persist($ranking);
        }
        return $ranking;
    }

    public function findForLadder(Season $season): mixed
    {
        return $this->createQueryBuilder('r')
            ->addSelect('u')
            ->addSelect('g')
            ->join('r.owner', 'u')
            ->join('\App\Entity\Game', 'g', 'WITH', '(g.home = u or g.away = u)')
            //->where('(g.home = :user or g.away = :user) and g.season = :season')
            ->where('r.season = :season')
            ->setParameter('season', $season)
            ->orderBy('r.points', 'DESC')
            ->getQuery()
            ->getResult();

    }

    // /**
    //  * @return Ranking[] Returns an array of Ranking objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Ranking
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
