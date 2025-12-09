<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template TEntity of object
 * @extends ServiceEntityRepository<TEntity>
 */
abstract class AbstractCrudRepository extends ServiceEntityRepository
{
    /**
     * @param TEntity $entity
     */
    public function save(object $entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @param TEntity $entity
     */
    public function remove(object $entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($entity);
        if ($flush) {
            $em->flush();
        }
    }
}
