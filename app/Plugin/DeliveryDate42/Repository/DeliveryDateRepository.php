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
use Plugin\DeliveryDate42\Entity\DeliveryDate;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;

class DeliveryDateRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry, $entityClass = DeliveryDate::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function findOrCreate(array $conditions)
    {
        $DeliveryDate = $this->findOneBy($conditions);

        if ($DeliveryDate instanceof DeliveryDate) {
            return $DeliveryDate;
        }

        $DeliveryDate = new DeliveryDate();
        $DeliveryDate
            ->setPref($conditions['Pref'])
            ->setDelivery($conditions['Delivery'])
        ;

        return $DeliveryDate;
    }
}