<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MailMagazine42\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\MailMagazine42\Entity\MailMagazineSendHistory;
use Eccube\Doctrine\Query\Queries;

/**
 * SendHistoryRepository.
 */
class MailMagazineSendHistoryRepository extends AbstractRepository
{
    /**
     * @var Queries
     */
    protected $queries;

    /**
     * MailMagazineSendHistoryRepository constructor.
     *
     * @param Queries $queries
     * @param ManagerRegistry $registry
     * @param string $entityClass
     */
    public function __construct(
        Queries $queries,
        ManagerRegistry $registry,
        $entityClass = MailMagazineSendHistory::class
    ) {
        parent::__construct($registry, $entityClass);
        $this->queries = $queries;
    }

    /**
     * @param array $searchData
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchData($searchData = [])
    {
        $qb = $this->createQueryBuilder('sh')
            ->select('sh');

        // Order By
        $qb->addOrderBy('sh.start_date', 'DESC');

        return $this->queries->customize($this->getQueryKey(), $qb, $searchData);
    }

    /**
     * Get query key
     *
     * @return string
     */
    public function getQueryKey()
    {
        return 'MailMagazineSendHistory.getQueryBuilderBySearchData';
    }
}
