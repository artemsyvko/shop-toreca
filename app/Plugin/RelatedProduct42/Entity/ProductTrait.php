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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var RelatedProduct[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\RelatedProduct42\Entity\RelatedProduct", mappedBy="Product", cascade={"persist", "remove"})
     * @ORM\OrderBy({
     *     "id"="ASC"
     * })
     */
    private $RelatedProducts;

    /**
     * @return RelatedProduct[]|Collection
     */
    public function getRelatedProducts()
    {
        if (null === $this->RelatedProducts) {
            $this->RelatedProducts = new ArrayCollection();
        }

        return $this->RelatedProducts;
    }

    /**
     * @param RelatedProduct $RelatedProduct
     */
    public function addRelatedProduct(RelatedProduct $RelatedProduct)
    {
        if (null === $this->RelatedProducts) {
            $this->RelatedProducts = new ArrayCollection();
        }

        $this->RelatedProducts[] = $RelatedProduct;
    }

    /**
     * @param RelatedProduct $RelatedProduct
     *
     * @return bool
     */
    public function removeRelatedProduct(RelatedProduct $RelatedProduct)
    {
        if (null === $this->RelatedProducts) {
            $this->RelatedProducts = new ArrayCollection();
        }

        return $this->RelatedProducts->removeElement($RelatedProduct);
    }
}
