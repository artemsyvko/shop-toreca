<?php

namespace Plugin\SlnPayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderPaymentStatus
 * 
 * @ORM\Table(name="plg_sln_order_payment_status")
 * @ORM\Entity(repositoryClass="Plugin\SlnPayment42\Repository\OrderPaymentStatusRepository")
 */
class OrderPaymentStatus
{
   /**
     * @var int
     *
     * @ORM\Column(name="order_id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var integer
     * 
     * @ORM\Column(name="payment_status", type="integer", nullable=false)
     */
    private $paymentStatus;

    /**
     * @var string
     * 
     * @ORM\Column(name="payee", type="text", nullable=true)
     */
    private $payee;

    /**
     * @var string
     * 
     * @ORM\Column(name="amount", type="text", nullable=true)
     */
    private $amount;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="create_date", type="datetime", nullable=false)
     */
    private $createDate;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="update_date", type="datetime", nullable=false)
     */
    private $updateDate;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Get orderId
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set paymentStatus
     *
     * @param integer $paymentStatus
     * @return OrderPaymentStatus
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    /**
     * Get paymentStatus
     *
     * @return integer 
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * Set payee
     *
     * @param string $payee
     * @return OrderPaymentStatus
     */
    public function setPayee($payee)
    {
        $this->payee = $payee;

        return $this;
    }

    /**
     * Get payee
     *
     * @return string 
     */
    public function getPayee()
    {
        return $this->payee;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return OrderPaymentStatus
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return OrderPaymentStatus
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Get createDate
     *
     * @return \DateTime 
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set updateDate
     *
     * @param \DateTime $updateDate
     * @return OrderPaymentStatus
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate
     *
     * @return \DateTime 
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }
}
