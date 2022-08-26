<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function add(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findForShoutbox(?User $user): array
    {
        $rsm = new ResultSetMapping();
        $sql = '
            select
              m.id,
              m.body,
              m.created,
              author.id as author_id,
              author.username as author_username,
              r.user_id as recipient
            from
              message m
            left join
              message_user r on r.message_id = m.id
            join
              "user" author on m.author_id = author.id
            where
              m.author_id = :userId
              or r.user_id = :userId
              or r.user_id is null
            order by
              m.created desc;';
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $user?->getId());
        $res = $stmt->executeQuery()->fetchAllAssociative();
        return array_reduce($res, function ($acc, $x) use ($res) {
            $accIdx = array_search($x['id'], array_column($acc, 'id'));
            if ($accIdx === false) {
                $accIdx = array_push($acc, $x) - 1;
                $acc[$accIdx]['recipients'] = 0;
            }
            if (!is_null($x['recipient'])) {
                $acc[$accIdx]['recipients'] += 1;
            }
            return $acc;
        }, []);
        return $res;
    }

//    /**
//     * @return Message[] Returns an array of Message objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Message
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
