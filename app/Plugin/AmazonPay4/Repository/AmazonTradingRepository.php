<?php

namespace Plugin\AmazonPay4\Repository;

use Doctrine\ORM\EntityRepository;

class AmazonTradingRepository extends EntityRepository
{
    public $config;

    public function setConfig(array $config)
    {
        $this->config = $config;
    }
}
?>