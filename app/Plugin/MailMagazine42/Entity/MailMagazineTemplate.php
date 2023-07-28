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

use Eccube\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class MailMagazineTemplate
 *
 *
 * @ORM\Table(name="plg_mailmaga_template")
 * @ORM\Entity(repositoryClass="Plugin\MailMagazine42\Repository\MailMagazineTemplateRepository")
 */
class MailMagazineTemplate extends AbstractEntity
{
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getSubject();
    }

    /**
     * @ORM\Id()
     * @ORM\Column(name="template_id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(name="subject", type="string", nullable=false, length=255)
     *
     * @var string
     */
    private $subject;

    /**
     * @ORM\Column(name="body", type="text", nullable=false)
     *
     * @var string
     */
    private $body;

    /**
     * @ORM\Column(name="html_body", type="text", nullable=true)
     *
     * @var string
     */
    private $html_body;

    /**
     * @ORM\Column(name="create_date", type="datetimetz")
     *
     * @var \DateTime
     */
    private $create_date;

    /**
     * @ORM\Column(name="update_date", type="datetimetz")
     *
     * @var \DateTime
     */
    private $update_date;

    /**
     * Set template id.
     *
     * @param int $id
     *
     * @return MailMagazineTemplate
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get template_id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set subject.
     *
     * @param string $subject
     *
     * @return MailMagazineTemplate
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $createDate
     *
     * @return MailMagazineTemplate
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date.
     *
     * @param \DateTime $updateDate
     *
     * @return MailMagazineTemplate
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * Set body.
     *
     * @param string $body
     *
     * @return MailMagazineTemplate
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getHtmlBody()
    {
        return $this->html_body;
    }

    /**
     * @param string $html_body
     *
     * @return MailMagazineTemplate
     */
    public function setHtmlBody($html_body)
    {
        $this->html_body = $html_body;

        return $this;
    }
}
