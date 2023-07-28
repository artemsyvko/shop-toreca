<?php

namespace Plugin\SlnPayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PluginConfig
 *
 * @ORM\Table(name="plg_sln_plugin_config")
 * @ORM\Entity(repositoryClass="Plugin\SlnPayment42\Repository\PluginConfigRepository")
 */
class PluginConfig
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
     * @var string
     * 
     * @ORM\Column(name="sub_data", type="text", length=null, nullable=true)
     */
    private $subData;

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
     * Set subData
     *
     * @param string $subData
     * @return PluginConfig
     */
    public function setSubData($subData)
    {
        $this->subData = $subData;

        return $this;
    }

    /**
     * Get subData
     *
     * @return string 
     */
    public function getSubData()
    {
        return $this->subData;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return PluginConfig
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
     * @return PluginConfig
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
