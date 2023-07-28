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
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Delivery")
 */
trait DeliveryTrait
{
    /**
     * @var boolean|null
     *
     * @ORM\Column(name="deliverydate_date_flg", type="boolean", nullable=true)
     */
    private $delivery_date_flg;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\DeliveryDate42\Entity\DeliveryDate", mappedBy="Delivery", cascade={"persist","remove"})
     */
    private $DeliveryDates;

    public function __construct()
    {
        $this->DeliveryDates = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setDeliveryDateFlg($flg)
    {
        $this->delivery_date_flg = $flg;

        return $this;
    }

    public function getDeliveryDateFlg()
    {
        return $this->delivery_date_flg;
    }

    public function addDeliveryDate(\Plugin\DeliveryDate42\Entity\DeliveryDate $deliveryDate)
    {
        $this->DeliveryDates[] = $deliveryDate;

        return $this;
    }

    public function removeDeliveryDate(\Plugin\DeliveryDate42\Entity\DeliveryDate $deliveryDate)
    {
        return $this->DeliveryDates->removeElement($deliveryDate);
    }


    public function getDeliveryDates()
    {
        return $this->DeliveryDates;
    }
}
