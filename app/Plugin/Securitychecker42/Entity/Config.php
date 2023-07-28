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

namespace Plugin\Securitychecker42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_Securitychecker4_config")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\Securitychecker42\Repository\ConfigRepository")
 */
class Config extends \Eccube\Entity\AbstractEntity
{
    /** @var int */
    const DEFAULT_ID = 1;

    /**
     * @var integer
     * @ORM\Column(name="id", type="smallint", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id = self::DEFAULT_ID;

    /**
     * @var string
     * @ORM\Column(name="check_result", type="text", nullable=true)
     */
    private $check_result;

    /**
     * @var \DateTime
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set check_result
     *
     * @param string $check_result
     *
     * @return Config
     */
    public function setCheckResult($check_result)
    {
        $this->check_result = $check_result;

        return $this;
    }

    /**
     * Get check_result
     *
     * @return string
     */
    public function getCheckResult()
    {
        return $this->check_result;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $createDate
     *
     * @return Config
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date.
     *
     * @param \DateTime $updateDate
     *
     * @return Config
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
