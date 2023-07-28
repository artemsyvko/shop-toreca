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

namespace Plugin\SiteKit42\Repository;


use Eccube\Entity\Member;
use Eccube\Repository\AbstractRepository;
use Plugin\SiteKit42\Entity\IdToken;
use Doctrine\Persistence\ManagerRegistry;

class IdTokenRepository extends AbstractRepository
{
    /**
     * IdTokenRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IdToken::class);
    }

    public function findByMember(Member $Member)
    {
        return $this->findOneBy(['Member' => $Member]);
    }
}
