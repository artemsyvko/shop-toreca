<?php

namespace Customize\Repository;

use Eccube\Doctrine\Query\QueryCustomizer;
use Eccube\Repository\QueryKey;
use Doctrine\ORM\QueryBuilder;


class ProductOrderByUpdate implements QueryCustomizer {

    public function customize(QueryBuilder $builder, $params, $queryKey)
    {
        if(!empty($params["orderby"]) && $params["orderby"]->getId() == 4) {
            $builder->orderBy('p.update_date', 'DESC');
        }
        
    }

    public function getQueryKey(): string
    {
        return QueryKey::PRODUCT_SEARCH;
    }

}
?>