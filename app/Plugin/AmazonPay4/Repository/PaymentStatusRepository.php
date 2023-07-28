<?php

namespace Plugin\AmazonPay4\Repository;
use Eccube\Repository\AbstractRepository;
use Plugin\AmazonPay4\Entity\PaymentStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;
// use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Persistence\ManagerRegistry; 
class PaymentStatusRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentStatus::class);
    }
}

?>