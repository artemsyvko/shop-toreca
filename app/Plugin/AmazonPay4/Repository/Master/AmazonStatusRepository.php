<?php

namespace Plugin\AmazonPay4\Repository\Master;

use Eccube\Repository\AbstractRepository;
use Plugin\AmazonPay4\Entity\Master\AmazonStatus;
// use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Persistence\ManagerRegistry; 

class AmazonStatusRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AmazonStatus::class);
    }
}
?>