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
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\BaseInfo")
 */
trait BaseInfoTrait
{
    /**
     * 2段階認証機能の利用
     *
     * @var bool
     *
     * @ORM\Column(name="two_factor_auth_use", type="boolean", nullable=false, options={"default":false})
     */
    private bool $two_factor_auth_use;
    /**
     * SMS通知の設定
     *
     * @var bool
     *
     * @ORM\Column(name="option_activate_device", type="boolean", nullable=false, options={"default":false})
     */
    private bool $option_activate_device;

    /**
     * @return bool
     */
    public function isTwoFactorAuthUse(): bool
    {
        return $this->two_factor_auth_use;
    }

    /**
     * @param bool $two_factor_auth_use
     */
    public function setTwoFactorAuthUse(bool $two_factor_auth_use): void
    {
        $this->two_factor_auth_use = $two_factor_auth_use;
    }

    /**
     * @return bool
     */
    public function isOptionActivateDevice(): bool
    {
        return $this->option_activate_device;
    }

    /**
     * @param bool $option_activate_device
     */
    public function setOptionActivateDevice(bool $option_activate_device): void
    {
        $this->option_activate_device = $option_activate_device;
    }
}
