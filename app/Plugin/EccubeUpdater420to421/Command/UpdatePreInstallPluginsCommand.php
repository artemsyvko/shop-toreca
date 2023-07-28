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

use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Service\Composer\ComposerApiService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdatePreInstallPluginsCommand extends Command
{
    protected static $defaultName = 'eccube:update420to421:update-pre-install-plugins';

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
        BaseInfoRepository $baseInfoRepository
    ) {
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
        $packageNames = [
            'ec-cube/recommend4',
            'ec-cube/coupon4',
            'ec-cube/mailmagazine4',
            'ec-cube/salesreport4',
            'ec-cube/relatedproduct4',
            'ec-cube/securitychecker4',
            'ec-cube/productreview4',
            'ec-cube/api',
            'ec-cube/sitekit',
            'ec-cube/gmc',
        ];

        $this->composerApiService->execRequire(implode(' ', $packageNames));

        $this->io->success('EC-CUBE update-pre-install-plugins successful.');

        return 0;
    }
}
