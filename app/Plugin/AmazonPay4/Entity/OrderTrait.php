<?php

namespace Plugin\AmazonPay4\Entity;
use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */

trait OrderTrait
{
    public function getAmazonPay4SumAuthoriAmount()
    {
        $sumAuthoriAmount = 0;
        foreach ($this->AmazonPay4AmazonTradings as $AmazonTrading)
        {
            $sumAuthoriAmount += $AmazonTrading->getAuthoriAmount();
        }
        return $sumAuthoriAmount;
    }
    public function getAmazonPay4SumCaptureAmount()
    {
        $sumCaptureAmount = 0;
        foreach ($this->AmazonPay4AmazonTradings as $AmazonTrading)
        {
            $sumCaptureAmount += $AmazonTrading->getCaptureAmount();
        }
        return $sumCaptureAmount;
    }
    /**
     * @var string
     *
     * @ORM\Column(name="amazonpay4_charge_permission_id", type="string", length=255, nullable=true)
     */
    private $amazonpay4_charge_permission_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="amazonpay4_billable_amount", type="integer", nullable=true)
     */
    private $amazonpay4_billable_amount;

    /**
     * @var AmazonStatus
     * @ORM\ManyToOne(targetEntity="Plugin\AmazonPay4\Entity\Master\AmazonStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="amazonpay4_amazon_status_id", referencedColumnName="id")
     * })
     */
    private $AmazonPay4AmazonStatus;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\AmazonPay4\Entity\AmazonTrading", mappedBy="Order", cascade={"persist", "remove"})
     */
    private $AmazonPay4AmazonTradings;

    /**
     * @var string
     * @ORM\Column(name="amazonpay4_session_temp", type="text", length=36777215, nullable=true)
     */
    private $amazonpay4_session_temp;

    public function setAmazonPay4ChargePermissionId($AmazonPay4ChargePermissionId)
    {
        $this->amazonpay4_charge_permission_id = $AmazonPay4ChargePermissionId;
        return $this;
    }
    public function getAmazonPay4ChargePermissionId()
    {
        return $this->amazonpay4_charge_permission_id;
    }
    public function setAmazonPay4BillableAmount($amazonpay4BillableAmount)
    {
        $this->amazonpay4_billable_amount = $amazonpay4BillableAmount;
        return $this;
    }
    public function getAmazonPay4BillableAmount(){
        return $this->amazonpay4_billable_amount;
    }
    public function setAmazonPay4AmazonStatus(\Plugin\AmazonPay4\Entity\Master\AmazonStatus $AmazonPay4AmazonStatus)
    {
        $this->AmazonPay4AmazonStatus = $AmazonPay4AmazonStatus;
        return $this;
    }
    public function getAmazonPay4AmazonStatus()
    {
        return $this->AmazonPay4AmazonStatus;
    }
    public function addAmazonPay4AmazonTrading(\Plugin\AmazonPay4\Entity\AmazonTrading $AmazonPay4AmazonTrading)
    {
        $this->AmazonPay4AmazonTradings[] = $AmazonPay4AmazonTrading;
        return $this;
    }
    public function clearAmazonPay4AmazonTradings()
    {
        $this->AmazonPay4AmazonTradings->clear();
        return $this;
    }
    public function getAmazonPay4AmazonTradings()
    {
        return $this->AmazonPay4AmazonTradings;
    }
    public function setAmazonPay4SessionTemp($AmazonPay4SessionTemp)
    {
        $this->amazonpay4_session_temp = $AmazonPay4SessionTemp;
        return $this;
    }
    public function getAmazonPay4SessionTemp()
    {
        return $this->amazonpay4_session_temp;
    }
}