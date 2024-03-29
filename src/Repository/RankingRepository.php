<?php

namespace App\Repository;

use App\Entity\Ranking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\ResultSetMapping;
use App\Entity\Season;
use App\Entity\User;
use App\Thing\MinMaxNorm;

/**
 * @method Ranking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ranking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ranking[]    findAll()
 * @method Ranking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RankingRepository extends ServiceEntityRepository
{
    private const MAX_LADDER_FILL = 20;

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

    public function findForLadder(Season $season): array
    {
        $rsm = new ResultSetMapping();
        // TODO games not shown when either user is not joined into the ranking
        $sql = '
            select * from (
              select
                sub.owner_id as owner_id,
                sub.username as "user",
                case
                  when g.home_id=owner_id then g.score_home
                  when g.away_id=owner_id then g.score_away
                end as user_score,
                case
                  when g.home_id=owner_id then g.score_away
                  when g.away_id=owner_id then g.score_home
                end as opp_score,
                g.ranked,
                opp.id as opp_id,
                opp.username as opp,
                sub.game_id,
                round(sub.points) as points_rounded,
                sub.points,
                sub.rounds_played,
                sub.rounds_played_ratio,
                sub.rounds_won,
                sub.rounds_won_ratio,
                sub.rounds_lost,
                sub.games_played,
                sub.games_played_ratio,
                sub.games_won,
                sub.games_won_ratio,
                sub.games_lost,
                sub.streak,
                \'(\' || sub.streak_best || \')\' as streak_best,
                sub.activity,
                sub.last_active,
                sub.game_created
              from (
                select
                  g.id as game_id,
                  g.created as game_created,
                  u.username,
                  u.last_active,
                  r.*,
                  case
                    when g.home_id=g.user_id then g.away_id
                    when g.away_id=g.user_id then g.home_id
                  end as opp_id,
                  row_number() over (partition by g.user_id order by g.created desc)
                from (select home_id as user_id, * from game g union all select away_id as user_id, * from game g) g
                join ranking r on r.owner_id=g.user_id and r.season_id=:seasonId
                join "user" u on u.id = g.user_id
                where g.season_id=:seasonId and g.playoff_id is null order by g.user_id) sub
              join game g on sub.game_id=g.id
              join "user" opp on opp.id = sub.opp_id
              where sub.row_number < 7 and g.playoff_id is null
              union
              (select
                id as owner_id,
                username as "user",
                null as user_score,
                null as opp_score,
                null as ranked,
                null as opp_id,
                null as opp,
                null as game_id,
                0.0 as points_rounded,
                0.0 as points,
                0 as rounds_played,
                0.0 as rounds_played_ratio,
                0 as rounds_won,
                0.0 as rounds_won_ratio,
                0 as rounds_lost,
                0 as games_played,
                0.0 as games_played_ratio,
                0 as games_won,
                0.0 as games_won_ratio,
                0 as games_lost,
                0 as streak,
                \'(0)\' as streak_best,
                0.00 as activity,
                last_active,
                null
              from "user"
              order by last_active desc nulls last
              limit :maxLadderFill)) as ladder
            order by
              ladder.points desc,
              ladder.rounds_played desc,
              ladder.game_created asc,
              ladder."user" asc';
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('seasonId', $season->getId());
        $stmt->bindValue('maxLadderFill', self::MAX_LADDER_FILL);
        $res = $stmt->executeQuery()->fetchAllAssociative();
        $b = max(array_column($res, 'rounds_won'));
        $norm = new MinMaxNorm(array_column($res, 'points'), 0, $b);
        return array_reduce($res, function ($acc, $x) use ($res, $norm) {
            $isWithoutGames = is_null($x['game_id']);
            if ($isWithoutGames && count($acc) >= self::MAX_LADDER_FILL) {
                return $acc;
            }
            $accIdx = array_search($x['owner_id'], array_column($acc, 'owner_id'));
            if ($accIdx === false) {
                $accIdx = array_push($acc, $x) - 1;
                $acc[$accIdx]['games'] = [];
            }
            if (!$isWithoutGames) {
                $acc[$accIdx]['games'][] = [
                    'id' => $x['game_id'],
                    'opp' => ['id' => $x['opp_id'], 'username' => $x['opp']],
                    'score' => ['owner' => $x['user_score'], 'opp' => $x['opp_score']],
                    'won' => $x['user_score'] > $x['opp_score'],
                    'label' => $x['user_score'] === $x['opp_score']
                        ? 'draw' : ($x['user_score'] > $x['opp_score'] ? 'won' : 'lost'),
                    'ranked' => $x['ranked'],
                ];
            }
            $acc[$accIdx]['points_norm'] = round(floatval(strval($norm->step($acc[$accIdx]['points']))));
            return $acc;
        }, []);
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
