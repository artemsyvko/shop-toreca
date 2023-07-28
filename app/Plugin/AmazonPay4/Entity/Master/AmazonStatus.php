<?php

namespace Plugin\AmazonPay4\Entity\Master;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * AmazonStatus
 *
 * @ORM\Table(name="plg_amazon_pay4_status")
 * @ORM\Entity(repositoryClass="Plugin\AmazonPay4\Repository\Master\AmazonStatusRepository")
 */

class AmazonStatus extends AbstractMasterEntity
{
    const AUTHORI = 1;
    const CAPTURE = 2;
    const CANCEL = 3;
}
?>