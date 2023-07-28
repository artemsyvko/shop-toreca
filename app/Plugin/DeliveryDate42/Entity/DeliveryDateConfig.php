<?php
/*
* Plugin Name : DeliveryDate4
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\DeliveryDate42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DeliveryDateConfig
 *
 * @ORM\Table(name="plg_deliverydate_dtb_config")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\DeliveryDate42\Repository\ConfigRepository")
 */
class DeliveryDateConfig extends \Eccube\Entity\AbstractEntity
{
    const ENABLED = 1;
    const DISABLED = 0;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false, length=255)
     * @ORM\Id
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", nullable=true, length=255)
     */
    private $value;

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }
}