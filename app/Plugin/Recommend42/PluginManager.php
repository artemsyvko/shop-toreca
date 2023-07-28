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

namespace Plugin\Recommend42;

use Eccube\Application;
use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Layout;
use Eccube\Entity\Master\DeviceType;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\BlockPositionRepository;
use Eccube\Repository\BlockRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * @var string コピー元ブロックファイル
     */
    private $originBlock;

    /**
     * @var string ブロック名
     */
    private $blockName = 'おすすめ商品';

    /**
     * @var string ブロックファイル名
     */
    private $blockFileName = 'recommend_product_block';

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        // コピー元ブロックファイル
        $this->originBlock = __DIR__.'/Resource/template/Block/'.$this->blockFileName.'.twig';
    }

    /**
     * @param null $meta
     * @param Application|null $app
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        // ブロックの削除
        $this->removeDataBlock($container);
        $this->removeBlock($container);
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function enable(array $meta = null, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $this->copyBlock($container);
        $Block = $entityManager->getRepository(Block::class)->findOneBy(['file_name' => $this->blockFileName]);
        if (is_null($Block)) {
            // pagelayoutの作成
            $this->createDataBlock($container);
        }
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta = null, ContainerInterface $container)
    {
        $this->removeDataBlock($container);
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta = null, ContainerInterface $container)
    {
        $this->copyBlock($container);
    }

    /**
     * ブロックを登録.
     *
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function createDataBlock(ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();
        $DeviceType = $em->getRepository(DeviceType::class)->find(DeviceType::DEVICE_TYPE_PC);

        try {
            /** @var Block $Block */
            $Block = $em->getRepository(Block::class)->newBlock($DeviceType);

            // Blockの登録
            $Block->setName($this->blockName)
                ->setFileName($this->blockFileName)
                ->setUseController(false)
                ->setDeletable(false);
            $em->persist($Block);
            $em->flush($Block);

            // check exists block position
            $blockPos = $em->getRepository(BlockPosition::class)->findOneBy(['Block' => $Block]);
            if ($blockPos) {
                return;
            }

            // BlockPositionの登録
            $blockPos = $em->getRepository(BlockPosition::class)->findOneBy(
                ['section' => Layout::TARGET_ID_MAIN_BOTTOM, 'layout_id' => Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE],
                ['block_row' => 'DESC']
            );

            $BlockPosition = new BlockPosition();

            // ブロックの順序を変更
            $BlockPosition->setBlockRow(1);
            if ($blockPos) {
                $blockRow = $blockPos->getBlockRow() + 1;
                $BlockPosition->setBlockRow($blockRow);
            }

            $LayoutDefault = $em->getRepository(Layout::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);

            $BlockPosition->setLayout($LayoutDefault)
                ->setLayoutId($LayoutDefault->getId())
                ->setSection(Layout::TARGET_ID_MAIN_BOTTOM)
                ->setBlock($Block)
                ->setBlockId($Block->getId());

            $em->persist($BlockPosition);
            $em->flush($BlockPosition);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * ブロックを削除.
     *
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function removeDataBlock(ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();
        // Blockの取得(file_nameはアプリケーションの仕組み上必ずユニーク)
        /** @var \Eccube\Entity\Block $Block */
        $Block = $em->getRepository(Block::class)->findOneBy(['file_name' => $this->blockFileName]);

        if (!$Block) {
            return;
        }

        try {
            // BlockPositionの削除
            $blockPositions = $Block->getBlockPositions();
            /** @var \Eccube\Entity\BlockPosition $BlockPosition */
            foreach ($blockPositions as $BlockPosition) {
                $Block->removeBlockPosition($BlockPosition);
                $em->remove($BlockPosition);
            }

            // Blockの削除
            $em->remove($Block);
            $em->flush();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Copy block template.
     *
     * @param ContainerInterface $container
     */
    private function copyBlock(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        // ファイルコピー
        $file = new Filesystem();

        if (!$file->exists($templateDir.'/Block/'.$this->blockFileName.'.twig')) {
            // ブロックファイルをコピー
            $file->copy($this->originBlock, $templateDir.'/Block/'.$this->blockFileName.'.twig');
        }
    }

    /**
     * Remove block template.
     *
     * @param ContainerInterface $container
     */
    private function removeBlock(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $file = new Filesystem();
        $file->remove($templateDir.'/Block/'.$this->blockFileName.'.twig');
    }
}
