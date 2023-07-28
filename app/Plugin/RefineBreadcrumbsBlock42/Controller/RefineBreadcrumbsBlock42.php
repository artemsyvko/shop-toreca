<?php

namespace Plugin\RefineBreadcrumbsBlock42\Controller;

use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class RefineBreadcrumbsBlock42 extends AbstractController
{
    /**
     * @Route("/block/refine_breadcrumbs_block42", name="block_refine_breadcrumbs_block42")
     * @Template("Block/refine_breadcrumbs_block42.twig")
     */
    public function index(Request $request)
    {
        $uri = $_SERVER['REQUEST_URI'];
        $parse_uri= parse_url($uri, PHP_URL_PATH);

        // 商品一覧画面のカテゴリIDを取得
        $is_category_id = isset($_GET['category_id']);
        if ($is_category_id) {
            $category_id = $_GET['category_id'];
        } else {
            $category_id = false;
        };

        // 商品詳細ページ
        $products_detail_page = false;
        $products_detail_id = false;
        if (strpos($uri, '/products/detail/') !== false) {
            $products_detail_page = true;
            $products_detail_id = str_replace('/products/detail/', '', $parse_uri);
        };

        $RefineBreadcrumbsBlock = [
            'categoryId' => $category_id,
            'ProductsDetailPage' => $products_detail_page,
            'ProductsDetailId' => $products_detail_id,
            'uri' => $uri,
        ];
        

        return [
            'RefineBreadcrumbsBlock' => $RefineBreadcrumbsBlock,
        ];
    }
}
