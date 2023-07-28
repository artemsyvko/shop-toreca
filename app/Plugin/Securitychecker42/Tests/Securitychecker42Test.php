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

namespace Plugin\Securitychecker42\Tests;

use Eccube\Common\Constant;
use Eccube\Tests\EccubeTestCase;
use Plugin\Securitychecker42\Controller\ConfigController;
use Plugin\Securitychecker42\Entity\Config;
use Plugin\Securitychecker42\Repository\ConfigRepository;
use Plugin\Securitychecker42\Service\Securitychecker42Service;
use Symfony\Component\Filesystem\Filesystem;

class Securitychecker42Test extends EccubeTestCase
{
    /** @var ConfigController */
    protected $ConfigController;

    /** @var ConfigRepository */
    protected $ConfigRepository;

    /** @var Securitychecker42Service */
    protected $Securitychecker42Service;

    public function setUp(): void
    {
        parent::setUp();
        $this->ConfigController = self::$container->get(ConfigController::class);
        $this->ConfigRepository = $this->entityManager->getRepository(Config::class);
        $this->Securitychecker42Service = self::$container->get(Securitychecker42Service::class);
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf('\Plugin\Securitychecker42\Repository\ConfigRepository', $this->ConfigRepository);
        $this->assertInstanceOf('\Plugin\Securitychecker42\Service\Securitychecker42Service', $this->Securitychecker42Service);
    }

    public function testSaveCheckResult()
    {
        $CheckResult = json_encode(['eccube_version' => Constant::VERSION]);
        $this->ConfigRepository->saveCheckResult($CheckResult);
    }

    public function testGetCheckResult()
    {
        $expected = ['eccube_version' => Constant::VERSION];
        $CheckResult = json_encode($expected);
        $this->ConfigRepository->saveCheckResult($CheckResult);

        $actual = $this->ConfigRepository->getCheckResult();

        $this->assertEquals($expected, $actual);
    }

    public function testGetSiteUrl()
    {
        $expected = 'http://localhost/';
        $actual = $this->Securitychecker42Service->getSiteUrl();

        $this->assertEquals($expected, $actual);
    }

    public function testCheckResources()
    {
        // 外部から閲覧可能な robots.txt を指定する
        $file = realpath(self::$container->getParameter('kernel.project_dir').'/robots.txt');

        $expected = '/robots.txt';
        $actual = $this->Securitychecker42Service->checkResources($file);

        $this->assertStringContainsString($expected, $actual);
    }

    public function testSearchResources()
    {
        $dir = self::$container->getParameter('kernel.project_dir').'/html/dummy';
        $fs = new Filesystem();
        $fs->mkdir($dir);
        $fs->touch($dir.'/dummy.txt');

        // 外部から閲覧可能なパスを指定する
        $path = 'html/dummy';
        $expected = ['/html/dummy/dummy.txt'];
        $actual = $this->Securitychecker42Service->searchResources($path);

        $this->assertEquals($expected, $actual);
 
        $fs->remove($dir);
    }

    public function testSearchResourcesWithFile()
    {
        // 外部から閲覧可能なファイルを指定する
        $path = '/html/template/admin/assets/js/file_manager.js';
        $expected = ['/html/template/admin/assets/js/file_manager.js'];
        $actual = $this->Securitychecker42Service->searchResources($path);

        $this->assertEquals($expected, $actual);
    }

    public function testPluginConfigs()
    {
        $actual = $this->Securitychecker42Service->parsePluginConfigs();
        $this->assertNotEmpty($actual);
    }

    /**
     * @dataProvider trustedHostProvider
     */
    public function testIsExactMatchTrustedHost($trustedHostRegex, $host, $expected)
    {
        $actual = $this->Securitychecker42Service->isExactMatchTrustedHost($trustedHostRegex, $host);
        $this->assertSame($expected, $actual);
    }

    public function trustedHostProvider()
    {
        return [
            ['/localhost/i', 'localhost', false],
            ['/^localhost/i', 'localhost', false],
            ['/localhost$/i', 'localhost', false],
            ['/^localhost$/i', 'localhost', true],
            ['/localhost/i', 'www.ec-cube.net', false],
            ['/www.ec-cube.net/i', 'www.ec-cube.net', false],
            ['/^www.ec-cube.net/i', 'www.ec-cube.net', false],
            ['/www.ec-cube.net$/i', 'www.ec-cube.net', false],
            ['/^www.ec-cube.net$/i', 'www.ec-cube.net', false],
            ['/^www\.ec-cube\.net$/i', 'www.ec-cube.net', true],
        ];
    }
}
