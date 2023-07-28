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

namespace Plugin\RelatedProduct42\Entity;

use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Product;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class RelatedProduct.
 *
 * @ORM\Table(name="plg_related_product")
 * @ORM\Entity(repositoryClass="Plugin\RelatedProduct42\Repository\RelatedProductRepository")
 */
class RelatedProduct extends AbstractEntity
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
     * @ORM\Column(name="content", type="string", nullable=true, length=4000)
     */
    private $content;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product", inversedBy="RelatedProducts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="child_product_id", referencedColumnName="id")
     * })
     */
    private $ChildProduct;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * getContent.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * set related product content.
     *
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * get related product content.
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * set related product product.
     *
     * @param Product $Product
     *
     * @return $this
     */
    public function setProduct(Product $Product)
    {
        $this->Product = $Product;

        return $this;
    }

    /**
     * getChildProduct.
     *
     * @return Product
     */
    public function getChildProduct()
    {
        return $this->ChildProduct;
    }

    /**
     * setChildProduct.
     *
     * @param Product $Product
     *
     * @return $this
     */
    public function setChildProduct(Product $Product = null)
    {
        $this->ChildProduct = $Product;

        return $this;
    }
}
