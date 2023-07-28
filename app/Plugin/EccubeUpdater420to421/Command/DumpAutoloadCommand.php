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

use Eccube\Service\Composer\ComposerApiService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DumpAutoloadCommand extends Command
{
    protected static $defaultName = 'eccube:update420to421:dump-autoload';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ComposerApiService
     */
    protected $composerApiService;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    public function __construct(ContainerInterface $container, ComposerApiService $composerApiService)
    {
        parent::__construct();

        $this->container = $container;
        $this->composerApiService = $composerApiService;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->composerApiService->runCommand([
            'command' => 'dump-autoload',
        ]);

        $this->io->success('EC-CUBE update dump-autoload successful.');

        return 0;
    }
}
