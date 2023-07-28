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

namespace Plugin\EccubeUpdater420to421\Command;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Eccube\Entity\Plugin;
use Eccube\Exception\PluginException;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Service\Composer\ComposerApiService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginAlreadyInstalledCommand extends Command
{
    protected static $defaultName = 'eccube:update420to421:plugin-already-installed';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ComposerApiService
     */
    protected $composerApiService;

    /**
     * @var BaseInfoRepository
     */
    protected $baseInfoRepository;

    /**
     * @var PluginRepository
     */
    protected $pluginRepository;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    public function __construct(
        ContainerInterface $container,
        ComposerApiService $composerApiService,
        PluginRepository $pluginRepository,
        BaseInfoRepository $baseInfoRepository)
    {
        parent::__construct();

        $this->container = $container;
        $this->composerApiService = $composerApiService;
        $this->pluginRepository = $pluginRepository;
        $this->baseInfoRepository = $baseInfoRepository;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $BaseInfo = $this->baseInfoRepository->get();
        if ($BaseInfo->getAuthenticationKey()) {
            // プラグインのrequireを復元する.
            $this->execRequirePlugins();
        }

        $this->io->success('EC-CUBE update plugin-already-installed successful.');

        return 0;
    }

    protected function execRequirePlugins()
    {
        $packageNames = [];

        $Plugins = $this->getPlugins();

        foreach ($Plugins as $Plugin) {
            $packageNames[] = 'ec-cube/'.strtolower($Plugin->getCode()).':'.$Plugin->getVersion();
        }

        // 4.2.0-4.2.1
        $packageNames[] = "symfony/password-hasher:^5.4";
        $packageNames[] = "softcreatr/jsonpath:0.7.5";

        if ($packageNames) {
            try {
                $this->composerApiService->execRequire(implode(' ', $packageNames));
            } catch (PluginException $e) {
                log_error($e->getMessage());
            }
        }
    }

    /**
     * @return Plugin[]
     */
    protected function getPlugins()
    {
        $qb = $this->pluginRepository->createQueryBuilder('p');

        $Plugins = [];
        try {
            $Plugins = $qb->select('p')
                ->where("p.source IS NOT NULL AND p.source <> '0' AND p.source <> ''")
                ->orderBy('p.code', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            log_error($e->getMessage());
        }

        return $Plugins;
    }
}
