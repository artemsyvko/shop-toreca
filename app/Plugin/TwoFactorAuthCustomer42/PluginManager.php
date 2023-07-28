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

namespace Plugin\TwoFactorAuthCustomer42;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    // 設定対象ページ情報
    private $pages = [
        ['plg_customer_2fa_device_auth_send_onetime', 'デバイス認証送信先入力', 'TwoFactorAuthCustomer42/Resource/template/default/device_auth/send'],
        ['plg_customer_2fa_device_auth_input_onetime', 'デバイス認証トークン入力', 'TwoFactorAuthCustomer42/Resource/template/default/device_auth/input'],
        ['plg_customer_2fa_auth_type_select', '多要素認証方式選択', 'TwoFactorAuthCustomer42/Resource/template/default/tfa/select_type'],
    ];

    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();

        $this->createConfig($em);

        // twigファイルを追加
        $this->copyTwigFiles($container);

        // ページ登録
        $this->createPages($em);
    }

    /**
     * 設定の登録.
     *
     * @param EntityManagerInterface $em
     */
    protected function createConfig(EntityManagerInterface $em)
    {
        $TwoFactorAuthConfig = $em->find(TwoFactorAuthConfig::class, 1);
        if ($TwoFactorAuthConfig) {
            return;
        }

        // 初期値を保存
        $TwoFactorAuthConfig = new TwoFactorAuthConfig();
        $em->persist($TwoFactorAuthConfig);
        $em->flush();
    }

    /**
     * Twigファイルの登録
     *
     * @param ContainerInterface $container
     */
    protected function copyTwigFiles(ContainerInterface $container)
    {
        // テンプレートファイルコピー
        $templatePath = $container->getParameter('eccube_theme_front_dir')
            . '/TwoFactorAuthCustomer42/Resource/template/default';
        $fs = new Filesystem();
        if ($fs->exists($templatePath)) {
            return;
        }
        $fs->mkdir($templatePath);
        $fs->mirror(__DIR__ . '/Resource/template/default', $templatePath);
    }

    /**
     * ページ情報の登録
     *
     * @param EntityManagerInterface $em
     */
    protected function createPages(EntityManagerInterface $em)
    {
        foreach ($this->pages as $p) {
            $hasPage = $em->getRepository(Page::class)->count(['url' => $p[0]]) > 0;
            if (!$hasPage) {
                /** @var Page $Page */
                $Page = $em->getRepository(Page::class)->newPage();
                $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
                $Page->setUrl($p[0]);
                $Page->setName($p[1]);
                $Page->setFileName($p[2]);
                $Page->setMetaRobots('noindex');

                $em->persist($Page);
                $em->flush();

                $Layout = $em->getRepository(Layout::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
                $PageLayout = new PageLayout();
                $PageLayout->setPage($Page)
                    ->setPageId($Page->getId())
                    ->setLayout($Layout)
                    ->setLayoutId($Layout->getId())
                    ->setSortNo(0);
                $em->persist($PageLayout);
                $em->flush();
            }
        }
    }

    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();

        // twigファイルを削除
        $this->removeTwigFiles($container);

        // ページ削除
        $this->removePages($em);
    }

    /**
     * Twigファイルの削除
     *
     * @param ContainerInterface $container
     */
    protected function removeTwigFiles(ContainerInterface $container)
    {
        $templatePath = $container->getParameter('eccube_theme_front_dir')
            . '/TwoFactorAuthCustomer42';
        $fs = new Filesystem();
        $fs->remove($templatePath);
    }

    /**
     * ページ情報の削除
     *
     * @param EntityManagerInterface $em
     */
    protected function removePages(EntityManagerInterface $em)
    {
        foreach ($this->pages as $p) {
            $Page = $em->getRepository(Page::class)->findOneBy(['url' => $p[0]]);
            if ($Page !== null) {
                $Layout = $em->getRepository(Layout::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
                $PageLayout = $em->getRepository(PageLayout::class)->findOneBy(['Page' => $Page, 'Layout' => $Layout]);

                $em->remove($PageLayout);
                $em->remove($Page);
                $em->flush();
            }
        }
    }

    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();

        // twigファイルを削除
        $this->removeTwigFiles($container);

        // ページ削除
        $this->removePages($em);
    }
}
