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

namespace Plugin\SiteKit42\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("\Eccube\Entity\BaseInfo");
 */
trait BaseInfoTrait
{
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $siteKitSiteId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $siteKitSiteSecret;

    /**
     * @return mixed
     */
    public function getSiteKitSiteId()
    {
        return $this->siteKitSiteId;
    }

    /**
     * @param mixed $siteKitSiteId
     */
    public function setSiteKitSiteId($siteKitSiteId)
    {
        $this->siteKitSiteId = $siteKitSiteId;
    }

    /**
     * @return mixed
     */
    public function getSiteKitSiteSecret()
    {
        return $this->siteKitSiteSecret;
    }

    /**
     * @param mixed $siteKitSiteSecret
     */
    public function setSiteKitSiteSecret($siteKitSiteSecret)
    {
        $this->siteKitSiteSecret = $siteKitSiteSecret;
    }
}
