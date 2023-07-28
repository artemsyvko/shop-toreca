<?php
namespace Customize\Twig\Extension;
 
use Doctrine\Common\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductRepository;
 
class TwigExtension extends \Twig_Extension
{
    private $entityManager;
    protected $eccubeConfig;
    private $productRepository;
 
    /**
        TwigExtension constructor.
    **/
    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig, 
        ProductRepository $productRepository
    ) {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->productRepository = $productRepository;
    }
    /**
        Returns a list of functions to add to the existing list.
        @return array An array of functions
    **/
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('CustomizeNewProductYugi', array($this, 'getCustomizeNewYugi')),new \Twig_SimpleFunction('CustomizeNewProductPoke', array($this, 'getCustomizeNewPoke')),new \Twig_SimpleFunction('CustomizeNewProductDM', array($this, 'getCustomizeNewDM')),new \Twig_SimpleFunction('CustomizeNewProductDBH', array($this, 'getCustomizeNewDBH')),
        );
    }
 
    /**
        Name of this extension
        @return string
    **/
    public function getName()
    {
        return 'CustomizeTwigExtension';
    }
 
    /**
        新着商品を4件返す
        @return Products|null
    **/
    public function getCustomizeNewYugi()
    {
        try {
            $searchData = array();
            $qb = $this->entityManager->createQueryBuilder();
            $query = $qb->select("plob")
                ->from("Eccube\\Entity\\Master\\ProductListOrderBy", "plob")
                ->where('plob.id = :id')
                ->setParameter('id', $this->eccubeConfig['eccube_product_order_newer'])
                ->getQuery();
            $searchData['orderby'] = $query->getOneOrNullResult();
 
            // 特定のカテゴリーを取得
            $new_stock_id = 1;
            $qb = $this->entityManager->createQueryBuilder();
            $query = $qb->select("ctg")
                ->from("Eccube\\Entity\\Category", "ctg")
                ->where('ctg.id = :id')
                ->setParameter('id', $new_stock_id)
                ->getQuery();
            $searchData['category_id'] = $query->getOneOrNullResult();

            // 新着順の商品情報4件取得
            $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
            $query = $qb->setMaxResults(4)->getQuery();
            $products = $query->getResult();
            return $products;
 
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
    public function getCustomizeNewPoke()
    {
        try {
            $searchData = array();
            $qb = $this->entityManager->createQueryBuilder();
            $query = $qb->select("plob")
                ->from("Eccube\\Entity\\Master\\ProductListOrderBy", "plob")
                ->where('plob.id = :id')
                ->setParameter('id', $this->eccubeConfig['eccube_product_order_newer'])
                ->getQuery();
            $searchData['orderby'] = $query->getOneOrNullResult();
 
            // 特定のカテゴリーを取得
            $new_stock_id = 2;
            $qb = $this->entityManager->createQueryBuilder();
            $query = $qb->select("ctg")
                ->from("Eccube\\Entity\\Category", "ctg")
                ->where('ctg.id = :id')
                ->setParameter('id', $new_stock_id)
                ->getQuery();
            $searchData['category_id'] = $query->getOneOrNullResult();

            // 新着順の商品情報4件取得
            $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
            $query = $qb->setMaxResults(4)->getQuery();
            $products = $query->getResult();
            return $products;
 
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
    public function getCustomizeNewDM()
    {
        try {
            $searchData = array();
            $qb = $this->entityManager->createQueryBuilder();
            $query = $qb->select("plob")
                ->from("Eccube\\Entity\\Master\\ProductListOrderBy", "plob")
                ->where('plob.id = :id')
                ->setParameter('id', $this->eccubeConfig['eccube_product_order_newer'])
                ->getQuery();
            $searchData['orderby'] = $query->getOneOrNullResult();
 
            // 特定のカテゴリーを取得
            $new_stock_id = 5;
            $qb = $this->entityManager->createQueryBuilder();
            $query = $qb->select("ctg")
                ->from("Eccube\\Entity\\Category", "ctg")
                ->where('ctg.id = :id')
                ->setParameter('id', $new_stock_id)
                ->getQuery();
            $searchData['category_id'] = $query->getOneOrNullResult();

            // 新着順の商品情報4件取得
            $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
            $query = $qb->setMaxResults(4)->getQuery();
            $products = $query->getResult();
            return $products;
 
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
    public function getCustomizeNewDBH()
    {
        try {
            $searchData = array();
            $qb = $this->entityManager->createQueryBuilder();
            $query = $qb->select("plob")
                ->from("Eccube\\Entity\\Master\\ProductListOrderBy", "plob")
                ->where('plob.id = :id')
                ->setParameter('id', $this->eccubeConfig['eccube_product_order_newer'])
                ->getQuery();
            $searchData['orderby'] = $query->getOneOrNullResult();
 
            // 特定のカテゴリーを取得
            $new_stock_id = 7;
            $qb = $this->entityManager->createQueryBuilder();
            $query = $qb->select("ctg")
                ->from("Eccube\\Entity\\Category", "ctg")
                ->where('ctg.id = :id')
                ->setParameter('id', $new_stock_id)
                ->getQuery();
            $searchData['category_id'] = $query->getOneOrNullResult();

            // 新着順の商品情報4件取得
            $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
            $query = $qb->setMaxResults(4)->getQuery();
            $products = $query->getResult();
            return $products;
 
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
}
?>