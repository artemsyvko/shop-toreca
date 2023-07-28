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
use Plugin\DeliveryDate42\Entity\Holiday;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HolidayRepository extends AbstractRepository
{
    protected $container;

    public function __construct(RegistryInterface $registry, ContainerInterface $container)
    {
        parent::__construct($registry, Holiday::class);
        $this->container = $container;
    }

    public function getList()
    {
        $qb = $this->createQueryBuilder('h')
            ->orderBy('h.date', 'ASC');
        $results = $qb->getQuery()
            ->getResult();

        return $results;
    }

    public function getHoliday(\DateTime $date)
    {
        $date->setTimeZone(new \DateTimeZone($this->container->getParameter('timezone')));
        $start = $date->format('Y-m-d 00:00:00P');
        $end = $date->format('Y-m-d 23:59:59P');

        $qb = $this->createQueryBuilder('h')
            ->andWhere('h.date >= :start')
            ->andWhere('h.date < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setMaxResults(1);
        $result = $qb->getQuery()
            ->getResult();

        if(count($result) > 0){
            return $result[0];
        }else{
            return null;
        }
    }

    public function checkHoliday(\DateTime $date)
    {
        if(!is_null($this->getHoliday($date))){
            return true;
        }else{
            return false;
        }

    }
}