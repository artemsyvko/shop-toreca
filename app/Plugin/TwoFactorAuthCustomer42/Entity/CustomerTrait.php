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

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @var ?string
     *
     * @ORM\Column(name="device_auth_one_time_token", type="string", length=255, nullable=true)
     */
    private ?string $device_auth_one_time_token = null;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="device_auth_one_time_token_expire", type="datetimetz", nullable=true)
     */
    private $device_auth_one_time_token_expire;

    /**
     * @var boolean
     *
     * @ORM\Column(name="device_authed", type="boolean", nullable=false, options={"default":false})
     */
    private bool $device_authed = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="device_authed_phone_number", type="string", length=14, nullable=true)
     */
    private ?string $device_authed_phone_number = null;

    /**
     * 2段階認証機能の設定
     *
     * @var int|null
     *
     * @ORM\Column(name="two_factor_auth_type", type="integer", nullable=true)
     */
    private ?int $two_factor_auth_type = null;

    /**
     * @var TwoFactorAuthType
     *
     * @ORM\ManyToOne(targetEntity="\Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="two_factor_auth_type_id", referencedColumnName="id")
     * })
     */
    private $TwoFactorAuthType = null;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="\Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthCustomerCookie", mappedBy="Customer")
     */
    private $TwoFactorAuthCustomerCookies;

    /**
     * @return string
     */
    public function getDeviceAuthOneTimeToken(): ?string
    {
        return $this->device_auth_one_time_token;
    }

    /**
     * @param string|null $device_auth_one_time_token
     */
    public function setDeviceAuthOneTimeToken(?string $device_auth_one_time_token): void
    {
        $this->device_auth_one_time_token = $device_auth_one_time_token;
    }

    /**
     * Get resetExpire.
     *
     * @return \DateTime|null
     */
    public function getDeviceAuthOneTimeTokenExpire()
    {
        return $this->device_auth_one_time_token_expire;
    }

    /**
     * Set oneTimeTokenExpire.
     *
     * @param \DateTime|null $resetExpire
     *
     * @return Customer
     */
    public function setDeviceAuthOneTimeTokenExpire($deviceAuthOneTimeTokenExpire = null)
    {
        $this->device_auth_one_time_token_expire = $deviceAuthOneTimeTokenExpire;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeviceAuthed(): bool
    {
        return $this->device_authed;
    }

    /**
     * @param bool $device_authed
     */
    public function setDeviceAuthed(bool $device_authed): void
    {
        $this->device_authed = $device_authed;
    }

    /**
     * @return string
     */
    public function getDeviceAuthedPhoneNumber(): ?string
    {
        return $this->device_authed_phone_number;
    }

    /**
     * @param string|null $device_authed_phone_number
     */
    public function setDeviceAuthedPhoneNumber(?string $device_authed_phone_number): void
    {
        $this->device_authed_phone_number = $device_authed_phone_number;
    }

    /**
     * Get sex.
     *
     * @return TwoFactorAuthType|null
     */
    public function getTwoFactorAuthType()
    {
        return $this->TwoFactorAuthType;
    }

    /**
     * Set two-factor auth type.
     *
     * @param TwoFactorAuthType|null $twoFactorAuthType
     */
    public function setTwoFactorAuthType(TwoFactorAuthType $twoFactorAuthType = null)
    {
        $this->TwoFactorAuthType = $twoFactorAuthType;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getTwoFactorAuthCustomerCookies(): Collection
    {
        return $this->TwoFactorAuthCustomerCookies;
    }

    /**
     * @param Collection $TwoFactorAuthCustomerCookies
     */
    public function setTwoFactorAuthCustomerCookies(Collection $TwoFactorAuthCustomerCookies): void
    {
        $this->TwoFactorAuthCustomerCookies = $TwoFactorAuthCustomerCookies;
    }
}
