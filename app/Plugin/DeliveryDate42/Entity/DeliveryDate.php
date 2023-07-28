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
 * DeliveryDate
 *
 * @ORM\Table(name="plg_deliverydate_dtb_delivery_date")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\DeliveryDate42\Repository\DeliveryDateRepository")
 */
class DeliveryDate extends \Eccube\Entity\AbstractEntity
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
     * @var int|null
     *
     * @ORM\Column(name="dates", type="integer", nullable=true)
     */
    private $dates;

    /**
     * @var \Eccube\Entity\Delivery
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Delivery", inversedBy="DeliveryDates")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="delivery_id", referencedColumnName="id")
     * })
     */
    private $Delivery;

    /**
     * @var \Eccube\Entity\Master\Pref
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Pref")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pref_id", referencedColumnName="id")
     * })
     */
    private $Pref;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dates
     *
     * @param  integer   $dates
     * @return DeliveryDates
     */
    public function setDates($dates)
    {
        $this->dates = $dates;

        return $this;
    }

    /**
     * Get dates
     *
     * @return integer
     */
    public function getDates()
    {
        return $this->dates;
    }

    /**
     * Set Delivery
     *
     * @param  \Eccube\Entity\Delivery $Delivery
     * @return DeliveryDate
     */
    public function setDelivery(\Eccube\Entity\Delivery $Delivery = null)
    {
        $this->Delivery = $Delivery;

        return $this;
    }

    /**
     * Get Delivery
     *
     * @return \Eccube\Entity\Delivery
     */
    public function getDelivery()
    {
        return $this->Delivery;
    }

    /**
     * Set Pref
     *
     * @param  \Eccube\Entity\Master\Pref $pref
     * @return DeliveryDate
     */
    public function setPref(\Eccube\Entity\Master\Pref $pref)
    {
        $this->Pref = $pref;

        return $this;
    }

    /**
     * Get Pref
     *
     * @return \Eccube\Entity\Master\Pref
     */
    public function getPref()
    {
        return $this->Pref;
    }
}
