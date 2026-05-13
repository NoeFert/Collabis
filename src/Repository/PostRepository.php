<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return Post[]
     */
    public function findForCategory(?Category $category = null, ?string $search = null): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->distinct()
            ->leftJoin('p.media', 'm')
            ->addSelect('m')
            ->leftJoin('p.categories', 'c')
            ->addSelect('c')
            ->orderBy('p.id', 'DESC');

        if ($category !== null) {
            $queryBuilder
                ->andWhere(':category MEMBER OF p.categories')
                ->setParameter('category', $category);
        }

        if ($search !== null && trim($search) !== '') {
            $queryBuilder
                ->andWhere('(LOWER(p.title) LIKE :search OR LOWER(c.label) LIKE :search OR LOWER(c.category_key) LIKE :search)')
                ->setParameter('search', '%'.strtolower(trim($search)).'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    //    /**
    //     * @return Post[] Returns an array of Post objects
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

    //    public function findOneBySomeField($value): ?Post
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
