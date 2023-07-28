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

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Customer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * TwoFactorCustomerCookie
 *
 * @ORM\Table(name="plg_two_factor_customer_cookie")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\TwoFactorAuthCustomer42\Repository\TwoFactorAuthConfigRepository")
 * @UniqueEntity("id")
 */
class TwoFactorAuthCustomerCookie extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="TwoFactorCustomerCookie")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private Customer $Customer;
    /**
     * @var string
     *
     * @ORM\Column(name="cookie_name", type="string", nullable=false, length=512)
     */
    private string $cookie_name;
    /**
     * @var string
     *
     * @ORM\Column(name="cookie_value", type="string", nullable=false, length=512, unique=true)
     */
    private string $cookie_value;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cookie_expire_date", type="datetime", nullable=true)
     */
    private ?\DateTime $cookie_expire_date;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private \DateTime $createdAt;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private \DateTime $updatedAt;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        $this->setUpdatedAt(new \DateTime('now'));
        if (!isset($this->createdAt) || $this->getCreatedAt() === null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->Customer;
    }

    /**
     * @param Customer $Customer
     */
    public function setCustomer(Customer $Customer): void
    {
        $this->Customer = $Customer;
    }

    /**
     * @return string
     */
    public function getCookieName(): string
    {
        return $this->cookie_name;
    }

    /**
     * @param string $cookie_name
     */
    public function setCookieName(string $cookie_name): void
    {
        $this->cookie_name = $cookie_name;
    }

    /**
     * @return string
     */
    public function getCookieValue(): string
    {
        return $this->cookie_value;
    }

    /**
     * @param string $cookie_value
     */
    public function setCookieValue(string $cookie_value): void
    {
        $this->cookie_value = $cookie_value;
    }

    /**
     * @return \DateTime
     */
    public function getCookieExpireDate(): \DateTime
    {
        return $this->cookie_expire_date;
    }

    /**
     * @param \DateTime $cookie_expire_date
     */
    public function setCookieExpireDate(\DateTime $cookie_expire_date): void
    {
        $this->cookie_expire_date = $cookie_expire_date;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
