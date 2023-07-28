<?php

namespace Plugin\SlnPayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MemCardId
 * 
 * @ORM\Table(name="plg_sln_mem_card_history")
 * @ORM\Entity(repositoryClass="Plugin\SlnPayment42\Repository\MemCardIdRepository")
 */
class MemCardId
{
   /**
     * @var int
     *
     * @ORM\Column(name="customer_id", type="integer", nullable=false)
     * @ORM\Id
     */
    private $customerId;

    /**
     * @var integer
     * 
     * @ORM\Column(name="mem_id", type="integer", nullable=false)
     */
    private $memId;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="update_date", type="datetime", nullable=false)
     */
    private $updateDate;


    /**
     * Get customerId
     *
     * @return integer 
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Set memId
     *
     * @param integer $memId
     * @return MemCardId
     */
    public function setMemId($memId)
    {
        $this->memId = $memId;

        return $this;
    }

    /**
     * Get memId
     *
     * @return integer 
     */
    public function getMemId()
    {
        return $this->memId;
    }

    /**
     * Set updateDate
     *
     * @param \DateTime $updateDate
     * @return MemCardId
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

    /**
     * Set customerId
     *
     * @param integer $customerId
     * @return MemCardId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;

        return $this;
    }
}
