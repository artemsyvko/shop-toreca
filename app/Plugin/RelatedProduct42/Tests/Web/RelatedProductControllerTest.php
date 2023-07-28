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

namespace Plugin\RelatedProduct\Tests\Web;

use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\RelatedProduct42\Entity\RelatedProduct;
use Eccube\Repository\ProductRepository;
use Eccube\Entity\Product;

/**
 * Class RelatedProductControllerTest.
 */
class RelatedProductControllerTest extends AbstractWebTestCase
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Product
     */
    protected $Product;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->productRepository = $this->entityManager->getRepository(Product::class);
        $this->Product = $this->createProduct('ディナーフォーク');
    }

    /**
     * Test display related product in product detail page.
     */
    public function testShowRelatedProduct()
    {
        $this->initRelatedProduct($this->Product->getId());
        $crawler = $this->client->request('GET', $this->generateUrl('product_detail', ['id' => $this->Product->getId()]));

        $this->assertStringContainsString('RelatedProduct-product_area', $crawler->html());
    }

    /**
     * insert related product in DB.
     *
     * @param $id
     *
     * @return RelatedProduct
     */
    private function initRelatedProduct($id)
    {
        $fake = $this->getFaker();
        $Product = $this->productRepository->find($id);
        $RelatedProduct = new RelatedProduct();
        $RelatedProduct->setContent($fake->word);
        $RelatedProduct->setProduct($Product);
        $RelatedProduct->setChildProduct($Product);
        $this->entityManager->persist($RelatedProduct);
        $this->entityManager->flush();

        return $RelatedProduct;
    }
}
