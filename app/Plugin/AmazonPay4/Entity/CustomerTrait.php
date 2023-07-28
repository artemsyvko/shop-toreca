<?php

namespace Plugin\AmazonPay4\Entity;
use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait{
    /**
     * @var string
     *
     * @ORM\Column(name="amazon_user_id", type="string", length=255, nullable=true)
     */
    private $amazon_user_id;

    public function setAmazonUserId($amazon_user_id)
    {
        $this->amazon_user_id = $amazon_user_id;
        return $this;
    }
    public function getAmazonUserId()
    {
        return $this->amazon_user_id;
    }
}
?>