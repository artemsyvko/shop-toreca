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

namespace Plugin\SiteKit42;


use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class PluginManager extends AbstractPluginManager
{
    public function install(array $meta, ContainerInterface $container)
    {
        $fs = new Filesystem();
        $routeYaml = $container->getParameter('plugin_data_realdir').'/SiteKit42/routes.yaml';
        if (!$fs->exists($routeYaml)) {
            $fs->dumpFile($routeYaml, '');
        }
    }

    public function update(array $meta, ContainerInterface $container)
    {
        $fs = new Filesystem();
        $routeYaml = $container->getParameter('plugin_data_realdir').'/SiteKit42/routes.yaml';
        if (!$fs->exists($routeYaml)) {
            $fs->dumpFile($routeYaml, '');
        }
    }
}
