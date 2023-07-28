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

namespace Plugin\MailMagazine42\Entity;

use Eccube\Doctrine\Query\WhereClause;
use Eccube\Doctrine\Query\WhereCustomizer;
use Eccube\Repository\QueryKey;

class AdminCustomerQueryCustomizer extends WhereCustomizer
{
    /**
     * {@inheritdoc}
     *
     * @param array $params
     * @param $queryKey
     *
     * @return WhereClause[]
     */
    protected function createStatements($params, $queryKey)
    {
        if (!isset($params['plg_mailmagazine_flg'])) {
            return [];
        }

        return [WhereClause::eq('c.mailmaga_flg', ':mailmaga_flg', [
            'mailmaga_flg' => $params['plg_mailmagazine_flg'],
        ])];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getQueryKey()
    {
        return QueryKey::CUSTOMER_SEARCH;
    }
}
