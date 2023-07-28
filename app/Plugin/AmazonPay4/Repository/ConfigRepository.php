<?php

namespace Plugin\AmazonPay4\Repository;

use Eccube\Common\EccubeConfig;
use Eccube\Repository\AbstractRepository;
use Plugin\AmazonPay4\Entity\Config;
// use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Persistence\ManagerRegistry; 

class ConfigRepository extends AbstractRepository
{
    public function __construct(
        EccubeConfig $eccubeConfig, ManagerRegistry $registry
    ){
        parent::__construct($registry, Config::class);
        $this->eccubeConfig = $eccubeConfig;
    }

    public function get($setting = false)
    {
        $Config = $this->find(1);
        if ($setting === false && $Config->getAmazonAccountMode() == $this->eccubeConfig['amazon_pay4']['account_mode']['shared']) {
            $Config->setSellerId($this->eccubeConfig['amazon_pay4']['test_account']['seller_id']);
            $Config->setPublicKeyId($this->eccubeConfig['amazon_pay4']['test_account']['public_key_id']);
            $Config->setPrivateKeyPath($this->eccubeConfig['amazon_pay4']['test_account']['private_key_path']);
            $Config->setClientId($Config->getTestClientId());
        }
        return $Config;
    }
}
?>