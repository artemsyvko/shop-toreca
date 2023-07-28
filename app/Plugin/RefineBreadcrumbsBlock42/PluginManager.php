<?php

namespace Plugin\RefineBreadcrumbsBlock42;

use Eccube\Entity\Block;
use Eccube\Entity\Master\DeviceType;
use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{
    private $blockFileName = 'refine_breadcrumbs_block42';

    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();
        $DeviceType = $em->getRepository(DeviceType::class)->find(DeviceType::DEVICE_TYPE_PC);

        // block 作成
        $Block = new Block();
        $Block
            ->setName('パンくず')
            ->setFileName($this->blockFileName)
            ->setDeviceType($DeviceType)
            ->setUseController(true)
            ->setDeletable(true);

        $em->persist($Block);
        $em->flush($Block);

        $file = new Filesystem();

        $dir = sprintf(
            '%s/app/template/%s/Block',
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('eccube.theme')
        );

        $file->copy(__DIR__ . '/Resource/template/' . $this->blockFileName . '.twig', $dir . '/' . $this->blockFileName . '.twig');
    }


    public function disable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();

        $Block = $em->getRepository(Block::class)->findOneBy(array('file_name' => $this->blockFileName));
        $em->remove($Block);
        $em->flush($Block);
    }
}
