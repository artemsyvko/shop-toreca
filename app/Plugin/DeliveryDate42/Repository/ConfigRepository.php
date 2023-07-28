<?php
/*
* Plugin Name : DeliveryDate4
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\DeliveryDate42\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\DeliveryDate42\Entity\DeliveryDateConfig;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;

class ConfigRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry, $entityClass = DeliveryDateConfig::class)
    {
        parent::__construct($registry, $entityClass);
    }
}