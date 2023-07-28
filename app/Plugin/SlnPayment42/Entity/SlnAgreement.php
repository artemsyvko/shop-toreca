<?php

namespace Plugin\SlnPayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SlnAgreement
 * 
 * @ORM\Table(name="plg_sln_agreement")
 * @ORM\Entity(repositoryClass="Plugin\SlnPayment42\Repository\SlnAgreementRepository")
 */
class SlnAgreement
{
    /**
     * @var int
     *
     * @ORM\Column(name="order_id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="agree_flg", type="smallint", options={"unsigned":true, "default":0})
     */
    private $agree_flg;
    
    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="create_date", type="datetime", nullable=false)
     */
    private $createDate;

    
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Get id.
     *
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set agreeFlg.
     * 
     * @param int|null $agreeFlg
     * 
     * @return SlnAgreement
     */
    public function setAgreeFlg($agreeFlg = null)
    {
        $this->agree_flg = $agreeFlg;

        return $this;
    }

    /**
     * Get agreeFlg.
     * 
     * @return int|null
     */
    public function getAgreeFlg()
    {
        return $this->agree_flg;
    }
    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return SlnAgreement
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

}