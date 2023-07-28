<?php

namespace Plugin\AmazonPay4\Repository;

use Doctrine\ORM\EntityRepository;

class AmazonOrderRepository extends EntityRepository
{
    public $config;
    protected $app;

    public function setApplication($app)
    {
        $this->app = $app;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function getAmazonOrderByOrderDataForAdmin($Orders)
    {
        $AmazonOrders = [];
        foreach ($Orders as $Order) {
            $AmazonOrder = $this->findby(['Order' => $Order]);
            if (!empty($AmazonOrder)) {
                $AmazonOrders[] = $AmazonOrder[0];
            }
        }
        return $AmazonOrders;
    }
}
?>