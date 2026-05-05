<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Post;
use App\Entity\UserProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * @return Conversation[]
     */
    public function findForUser(UserProfile $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->addSelect('m')
            ->andWhere('c.user_profile = :user OR c.interlocutor = :user OR c.interlocutor = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')
            ->addOrderBy('m.datetime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForPostAndUsers(Post $post, UserProfile $requester, UserProfile $owner): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.post = :post')
            ->andWhere('c.user_profile = :requester')
            ->andWhere('c.interlocutor = :owner OR c.interlocutor = :owner')
            ->setParameter('post', $post)
            ->setParameter('requester', $requester)
            ->setParameter('owner', $owner)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Conversation[] Returns an array of Conversation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Conversation
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
