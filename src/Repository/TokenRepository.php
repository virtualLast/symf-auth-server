<?php

namespace App\Repository;

use App\Entity\Token;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractCrudRepository<Token>
 */
class TokenRepository extends AbstractCrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }
}
