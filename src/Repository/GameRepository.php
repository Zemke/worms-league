<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\Season;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Game $entity, bool $flush = true): void
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
    public function remove(Game $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Find games a user played.
     *
     * @return Game[] Returns an array of Game where user is home or away.
     */
    public function findOfUser(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.home = :user or g.away = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find games a user played.
     *
     * @return Game[] Returns an array of Game where user is home or away.
     */
    public function findOfUserAndSeason(User $user, Season $season): array
    {
        return $this->createQueryBuilder('g')
            ->select(['g', 'r', 'rd', 'home', 'away'])
            ->leftJoin('g.home', 'home')
            ->leftJoin('g.away', 'away')
            ->leftJoin('g.replays', 'r')
            ->leftJoin('r.replayData', 'rd')
            ->where('(g.home = :user or g.away = :user) and g.season = :season and g.playoff is null')
            ->setParameter('user', $user)
            ->setParameter('season', $season)
            ->getQuery()
            ->getResult();
    }

    /**
     * Just a games finder with some eager relations.
     *
     * @return Game[]
     */
    public function findBySeasonEager(Season $season, bool $playoffs = false): array
    {
        $qb = $this->createQueryBuilder('g');
        return $qb
            ->select(['g', 'r', 'rd', 'c', 'home', 'away'])
            ->join('g.home', 'home')
            ->join('g.away', 'away')
            ->leftJoin('g.comments', 'c')
            ->leftJoin('g.replays', 'r')
            ->leftJoin('r.replayData', 'rd')
            ->where('g.season = :season')
            ->andWhere($playoffs ? $qb->expr()->isNotNull('g.playoff') : $qb->expr()->isNull('g.playoff'))
            ->setParameter('season', $season)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Game[] Persisted playoff games.
     */
    public function savePlayoff(array $games, bool $flush = false): array
    {
        foreach ($games as &$g) {
            if (is_null($g->getPlayoff())) {
                throw new \RuntimeException("{$g->asText()} is not a playoff game");
            }
            $this->_em->persist($g);
        }
        if ($flush) {
            $this->_em->flush();
        }
        return $games;
    }

    // /**
    //  * @return Game[] Returns an array of Game objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Game
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
