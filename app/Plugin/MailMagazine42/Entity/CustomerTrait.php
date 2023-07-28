<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MailMagazine42\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * @Eccube\EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @ORM\Column(name="plg_mailmagazine_flg", type="smallint", length=1, nullable=true, options={"default":0, "unsigned": true})
     *
     * @var int
     */
    protected $mailmaga_flg;

    /**
     * Set mailmaga_flg
     *
     * @param $mailmagaFlg
     *
     * @return $this
     */
    public function setMailmagaFlg($mailmagaFlg)
    {
        $this->mailmaga_flg = $mailmagaFlg;

        return $this;
    }

    /**
     * Get mailmaga_flg
     *
     * @return int
     */
    public function getMailmagaFlg()
    {
        return $this->mailmaga_flg;
    }
}
