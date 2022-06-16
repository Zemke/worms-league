<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(User $entity, bool $flush = true): void
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
    public function remove(User $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findOneByUsernameIgnoreCase(string $username): ?User
    {
        return $this->_em->createQuery(
            'select u from App\Entity\User u where lower(u.username) = lower(:username)'
        )
            ->setParameter('username', $username)
            ->getOneOrNullResult();
    }

    public function findOneByEmailIgnoreCase(string $email): ?User
    {
        return $this->_em->createQuery(
            'select u from App\Entity\User u where lower(u.email) = lower(:email)'
        )
            ->setParameter('email', $email)
            ->getOneOrNullResult();
    }

    public function register(User &$user): void
    {
        $user->setActive(false);
        $user->setActivationKey($this->genKey());
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Set a random activation key to the user that owns the given email address.
     *
     * @param $email string The email address whose user's activation key is to be updated.
     * @return The updated user or null if there's no user with that email.
     */
    public function updateActivationKey(string $email): ?User
    {
        $user = $this->findOneByEmailIgnoreCase($email);
        if (is_null($user)) {
            return $user;
        }
        $user->setActivationKey($this->genKey());
        $this->_em->persist($user);
        $this->_em->flush();
        return $user;
    }

    private function genKey(): string
    {
        return md5(random_bytes(32));
    }

    public function fulfillActivationKey(string $key, bool $setActive): ?User
    {
        $user = $this->findOneByActivationKey($key);
        if (is_null($user)) {
            return null;
        }
        $user->setActivationKey(null);
        if ($setActive) {
            $user->setActive(true);
        }
        $this->_em->persist($user);
        $this->_em->flush();
        return $user;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->_em->createQuery(
                'SELECT u
                FROM App\Entity\User u
                WHERE (lower(u.username) = lower(:query)
                OR lower(u.email) = lower(:query))
                AND u.active = true'
            )
            ->setParameter('query', strtolower($identifier))
            ->getOneOrNullResult();
    }

    public function updateLastActive(User &$user, \DateTime $lastActive = new \DateTime('now')): void
    {
        $user->setLastActive($lastActive);
        $this->_em->flush();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
