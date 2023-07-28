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

namespace Plugin\SiteKit42\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Member;

/**
 * Class IdToken
 *
 * @ORM\Table(name="plg_site_kit_id_token")
 * @ORM\Entity(repositoryClass="Plugin\SiteKit42\Repository\IdTokenRepository")
 */
class IdToken
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
     * @var Member
     *
     * @ORM\OneToOne(targetEntity="Eccube\Entity\Member", inversedBy="IdToken")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="member_id", referencedColumnName="id")
     * })
     */
    private $Member;

    /**
     * @var string
     *
     * @ORM\Column(name="id_token", type="text")
     */
    private $id_token;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return Member
     */
    public function getMember()
    {
        return $this->Member;
    }

    /**
     * @param Member $Member
     */
    public function setMember(Member $Member)
    {
        $this->Member = $Member;
    }

    /**
     * @return string
     */
    public function getIdToken()
    {
        return $this->id_token;
    }

    /**
     * @param string $id_token
     */
    public function setIdToken(string $id_token)
    {
        $this->id_token = $id_token;
    }


}
