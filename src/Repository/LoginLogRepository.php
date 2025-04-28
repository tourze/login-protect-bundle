<?php

namespace Tourze\LoginProtectBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\LoginProtectBundle\Entity\LoginLog;

/**
 * @method LoginLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginLog[]    findAll()
 * @method LoginLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginLog::class);
    }
}
