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

namespace Plugin\EccubeUpdater420to421\Controller\Admin;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Eccube\Common\Constant;
use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Plugin;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Service\Composer\ComposerApiService;
use Eccube\Service\PluginApiService;
use Eccube\Service\SystemService;
use Eccube\Util\CacheUtil;
use Plugin\EccubeUpdater420to421\Common\Constant as UpdaterConstant;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigController extends AbstractController
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var BaseInfoRepository
     */
    protected $baseInfoRepository;

    /**
     * @var PluginRepository
     */
    protected $pluginRepository;

    /**
     * @var ComposerApiService
     */
    protected $composerApiService;

    /**
     * @var SystemService
     */
    protected $systemService;

    /**
     * @var bool
     */
    protected $supported;

    /**
     * @var string
     */
    protected $dataDir;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $updateFile;

    /**
     * ConfigController constructor.
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        BaseInfoRepository $baseInfoRepository,
        PluginRepository $pluginRepository,
        PluginApiService $pluginApiService,
        ComposerApiService $composerApiService,
        SystemService $systemService
    ) {
        $this->baseInfoRepository = $baseInfoRepository;
        $this->pluginRepository = $pluginRepository;
        $this->pluginApiService = $pluginApiService;
        $this->composerApiService = $composerApiService;
        $this->systemService = $systemService;
        $this->eccubeConfig = $eccubeConfig;

        $this->projectDir = realpath($eccubeConfig->get('kernel.project_dir'));
        $this->dataDir = $this->projectDir.'/app/PluginData/eccube_update_plugin';
        $this->updateFile = realpath(__DIR__.'/../../Resource/update_file.tar.gz');
    }

    /**
     * @Route("/%eccube_admin_route%/eccube_updater_420_to_421/config", name="eccube_updater420to421_admin_config")
     * @Template("@EccubeUpdater420to421/admin/config.twig")
     */
    public function index(Request $request)
    {
        $this->supported = version_compare(Constant::VERSION, UpdaterConstant::FROM_VERSION, '=');
        if (!$this->supported) {
            $message = sprintf('このプラグインは%s〜%sへのアップデートプラグインです。', UpdaterConstant::FROM_VERSION,
                UpdaterConstant::TO_VERSION);
            $this->addError($message, 'admin');
        }

        if (function_exists('xdebug_is_enabled()') && xdebug_is_enabled()) {
            $this->supported = false;
            $this->addError('xdebugが有効になっています。無効にしてください。', 'admin');
        }

        if (PHP_VERSION_ID < 70300) {
            $this->supported = false;
            $this->addError('EC-CUBE 4.1.x は PHP 7.3 以上で動作します。', 'admin');
        }

        $phpPath = $this->getPhpPath();
        if (!$phpPath) {
            $this->supported = false;
            $this->addError('phpの実行パスを取得できませんでした。', 'admin');
        }

        $Plugin = $this->pluginRepository->findOneBy(['code' => 'AdminSecurity4']);
        if ($Plugin && $Plugin->isEnabled()) {
            $this->supported = false;
            $this->addError($Plugin->getName().'が有効になっています。プラグインを無効化してください。', 'admin');
        }

        $Plugin = $this->pluginRepository->findOneBy(['code' => 'Taba2FA']);
        if ($Plugin) {
            $this->supported = false;
            $this->addError($Plugin->getName().'がインストールされています。プラグインを削除してください。', 'admin');
        }

        return [
            'supported' => $this->supported,
            'php_path' => $phpPath,
            'app_template' => $this->checkAppTemplate()
        ];
    }

    // 4.2.0-4.2.1
    private function checkAppTemplate()
    {
        $files = [
            'app/template/admin/Store/authentication_setting.twig',
            'app/template/admin/Store/plugin_confirm.twig',
            'app/template/default/Product/detail.twig',
            'app/template/default/Product/list.twig',
            'app/template/admin/Content/file.twig',
        ];

        $exists = [];
        foreach ($files as $file) {
            $path = $this->projectDir.'/'.$file;
            if (file_exists($path)) {
                $exists[] = $path;
            }
        }

        return $exists;
    }

    /**
     * プラグインのEC-CUBE対応バージョンのチェックを行う.
     *
     * @Route("/%eccube_admin_route%/eccube_updater_420_to_421/check_plugin_version", name="eccube_updater420to421_admin_check_plugin_version")
     * @Template("@EccubeUpdater420to421/admin/check_plugin_vesrion.twig")
     */
    public function checkPluginVersion(Request $request)
    {
        $this->isTokenValid();

        $Plugins = $this->getPlugins();
        $unSupportedPlugins = [];

        foreach ($Plugins as $Plugin) {
            $packageNames[] = 'ec-cube/'.$Plugin->getCode().':'.$Plugin->getVersion();
            if ($Plugin->getCode() === UpdaterConstant::PLUGIN_CODE) {
                continue;
            }
            $data = $this->pluginApiService->getPlugin($Plugin->getCode());
            if (!in_array(UpdaterConstant::TO_VERSION, $data['supported_versions'])) {
                $unSupportedPlugins[] = $Plugin;
            }
        }

        return [
            'unSupportedPlugins' => $unSupportedPlugins,
        ];
    }

    /**
     * ファイルの書き込み権限チェックを行う.
     *
     * @Route("/%eccube_admin_route%/eccube_updater_420_to_421/check_permission", name="eccube_updater420to421_admin_check_permission", methods={"POST"})
     * @Template("@EccubeUpdater420to421/admin/check_permission.twig")
     */
    public function checkPermission(Request $request, Filesystem $fs)
    {
        $this->isTokenValid();

        if (file_exists($this->dataDir)) {
            $fs->remove($this->dataDir);
        }

        $fs->mkdir($this->dataDir);
        $this->dataDir = realpath($this->dataDir);

        $phar = new \PharData($this->updateFile);
        $phar->extractTo($this->dataDir, null, true);

        $noWritePermissions = [];

        // ディレクトリの書き込み権限をチェック
        $dirs = Finder::create()
            ->in($this->dataDir)
            ->directories();

        /** @var \SplFileInfo $dir */
        foreach ($dirs as $dir) {
            $path = $this->projectDir.str_replace($this->dataDir, '', $dir->getRealPath());
            if (file_exists($path) && !is_writable($path)) {
                $noWritePermissions[] = $path;
            }
        }

        // ファイルの書き込み権限をチェック
        $files = Finder::create()
            ->in($this->dataDir)
            ->files();

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $path = $this->projectDir.str_replace($this->dataDir, '', $file->getRealPath());
            if (file_exists($path) && !is_writable($path)) {
                $noWritePermissions[] = $path;
            }
        }

        return [
            'noWritePermissions' => $noWritePermissions,
        ];
    }

    /**
     * 更新ファイルの競合を確認する.
     *
     * @Route("/%eccube_admin_route%/eccube_updater_420_to_421/check_source", name="eccube_updater420to421_admin_check_source", methods={"POST"})
     * @Template("@EccubeUpdater420to421/admin/check_source.twig")
     */
    public function checkSource(Request $request)
    {
        $this->isTokenValid();

        $fileHash = Yaml::parseFile(
            $this->eccubeConfig->get('plugin_realdir').'/'.UpdaterConstant::PLUGIN_CODE.'/Resource/file_hash/file_hash.yaml'
        );
        $fileHashCrlf = Yaml::parseFile(
            $this->eccubeConfig->get('plugin_realdir').'/'.UpdaterConstant::PLUGIN_CODE.'/Resource/file_hash/file_hash_crlf.yaml'
        );

        $changes = [];
        foreach ($fileHash as $file => $hash) {
            $filePath = $this->eccubeConfig->get('kernel.project_dir').'/'.$file;
            if (file_exists($filePath)) {
                $hash = hash_file('md5', $filePath);
                if ($fileHash[$file] != $hash && $fileHashCrlf[$file] != $hash) {
                    $changes[] = $file;
                }
            }
        }

        $current = \json_decode(file_get_contents($this->projectDir.'/composer.json'), true);
        $origin = \json_decode(file_get_contents(
            $this->eccubeConfig->get('plugin_realdir').'/'.UpdaterConstant::PLUGIN_CODE.'/Resource/file_hash/composer.json'
        ), true);

        $overwriteRequires = [];
        foreach (array_keys($current['require']) as $currentRequire) {
            if (\strpos($currentRequire, 'ec-cube') === 0) {
                continue;
            }
            $match = false;
            foreach (array_keys($origin['require']) as $originRequire) {
                if ($currentRequire === $originRequire) {
                    $match = true;
                    break;
                }
            }

            if (!$match) {
                $overwriteRequires[] = $currentRequire;
            }
        }

        return [
            'changes' => $changes,
            'overwriteRequires' => $overwriteRequires,
        ];
    }

    /**
     * ファイルを上書きする.
     *
     * @Route("/%eccube_admin_route%/eccube_updater_420_to_421/update_files", name="eccube_updater420to421_admin_update_files", methods={"POST"})
     */
    public function updateFiles(Request $request, CacheUtil $cacheUtil)
    {
        $this->isTokenValid();

        set_time_limit(0);

        $this->systemService->switchMaintenance(true);
        $phpPath = $this->getPhpPath();
        $completeUrl = $this->generateUrl('eccube_updater420to421_admin_complete', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->clearComposerCache();
        $this->clearProxies();
        $this->clearSessions();

        while (@ob_end_flush());
        echo 'アップデートを実行しています...<br>';
        flush();
        ob_start();

        // 更新ファイルで上書き
        $fs = new Filesystem();
        $fs->remove($this->dataDir.'/vendor/composer/installed.json');
        $fs->remove($this->dataDir.'/composer.json');
        $fs->mirror($this->dataDir, $this->projectDir);

        $this->composerApiService->runCommand([
            'command' => 'dump-autoload',
            '--no-dev' => env('APP_ENV') === 'prod',
        ], null, true);

        // XXX bin/console cache:clear コマンドが失敗するため、このメソッドで強制的にキャッシュを削除する
        $this->forceClearCaches();

        $commands = [
            ['cache:clear', '--no-warmup'],
            ['cache:warmup', '--no-optional-warmers'],
            ['eccube:update420to421:plugin-already-installed'],
            ['eccube:generate:proxies'],
            ['doctrine:schema:update', '--dump-sql', '-f'],
            ['doctrine:migrations:migrate', '--no-interaction'],
            ['cache:clear', '--no-warmup'],
            ['cache:warmup', '--no-optional-warmers'],
            ['eccube:update420to421:dump-autoload'],
        ];

        log_info('Start update commands');
        foreach ($commands as $command) {
            while (@ob_end_flush());
            echo implode(' ', $command).'...<br>';
            flush();
            ob_start();
            log_info('Execute '.implode(' ', $command));
            $process = new Process(array_merge([$phpPath, 'bin/console'], $command));
            $process->setTimeout(600);
            $process->setWorkingDirectory($this->projectDir);
            $process->run();

            if (!$process->isSuccessful()) {
                $process->getOutput();
                log_error('Fail '.implode(' ', $command));
                log_error($process->getOutput());
                break;
            }
            log_info('Done '.implode(' ', $command));
        }
        log_info('End update commands');

        // ファイル上書き後、return Responseでシステムエラーとなるため、直接処理を記述
        echo "<html>
<head>
<script>
location.href = '$completeUrl'
</script>
</head>
</html>";
        exit;
    }

    /**
     * 完了画面を表示.
     *
     * @Route("/%eccube_admin_route%/eccube_updater_420_to_421/complete", name="eccube_updater420to421_admin_complete")
     * @Template("@EccubeUpdater420to421/admin/complete.twig")
     */
    public function complete(CacheUtil $cacheUtil)
    {
        $fs = new Filesystem();
        if (file_exists($this->dataDir)) {
            $fs->remove($this->dataDir);
        }

        $this->addSuccess('バージョンアップが完了しました。', 'admin');

        $this->systemService->disableMaintenance();

        return [];
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

    private function clearComposerCache()
    {
        $fs = new Filesystem();
        $fs->remove($this->projectDir.'/app/Plugin/.composer');
    }

    private function clearSessions()
    {
        $fs = new Filesystem();
        $fs->remove($this->projectDir.'/var/sessions');
    }

    private function clearProxies()
    {
        $fs = new Filesystem();
        $finder = new Finder();
        $finder->in($this->projectDir.'/app/proxy/entity')
            ->name('*.php');

        foreach ($finder->files() as $file) {
            $fs->remove($file->getRealPath());
        }
    }

    /**
     * bin/console cache:clear コマンドが失敗する場合は、このメソッドで強制的にキャッシュを削除する
     */
    private function forceClearCaches()
    {
        $fs = new Filesystem();
        $fs->remove($this->projectDir.'/var/cache/'.env('APP_ENV', 'prod'));
    }


    /**
     * phpの実行パスを返す
     *
     * 実行パスはPhpExecutableFinderで自動探索を行います。
     * PluginDir/Resource/config/services.yamlでeccube_update_plugin_420_421_php_pathを定義した場合、こちらが優先されます。
     *
     * @return false|string
     */
    private function getPhpPath()
    {
        $phpPath = $this->eccubeConfig->get('eccube_update_plugin_420_421_php_path');
        if ($phpPath && @is_executable($phpPath)) {
            return $phpPath;
        }
        $phpPath = (new PhpExecutableFinder())->find();
        if ($phpPath !== false) {
            return $phpPath;
        }

        return false;
    }
}
