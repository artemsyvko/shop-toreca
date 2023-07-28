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

namespace Plugin\TwoFactorAuthCustomer42\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * TwoFactorConfig
 *
 * @ORM\Table(name="plg_two_factor_auth_type")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\TwoFactorAuthCustomer42\Repository\TwoFactorAuthTypeRepository")
 * @UniqueEntity("id")
 */
class TwoFactorAuthType extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false, length=200, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="route", type="string", nullable=false, length=200, unique=true)
     */
    private $route = null;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_disabled", type="boolean", nullable=false)
     */
    private $isDisabled = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return TwoFactorAuthType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get route.
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Set route.
     *
     * @param string $route
     *
     * @return TwoFactorAuthType
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }

    /**
     * @param bool $isDisabled
     */
    public function setIsDisabled(bool $isDisabled): void
    {
        $this->isDisabled = $isDisabled;
    }
}
