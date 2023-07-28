<?php
namespace Plugin\SlnPayment42\Service;

use Monolog\Logger;
use Eccube\Common\Constant;
use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Plugin\SlnPayment42\Exception\SlnShoppingException;
use Plugin\SlnPayment42\Repository\MemCardIdRepository;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Plugin\SlnPayment42\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment42\Service\CryptAES;
use Plugin\SlnPayment42\Service\SlnAction\Mem;

class Util
{
    /**
     * @var Logger
     */
    public $ErrorLogger;

    /**
     * @var Logger
     */
    public $CardLogger;

    /**
     * @var Logger
     */
    public $CvsLogger;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        Logger $ErrorLogger,
        Logger $CardLogger,
        Logger $CvsLogger,
        UrlGeneratorInterface $urlGenerator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->ErrorLogger = $ErrorLogger;
        $this->CardLogger = $CardLogger;
        $this->CvsLogger = $CvsLogger;
        $this->urlGenerator = $urlGenerator;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function GetLogInfo()
    {
        // 暫定
        // $Customer = $this->app['user'];
        
        $msg = '[{' . $_SERVER['SCRIPT_NAME'] . '}';
        $msg .= 'from {' . $_SERVER['REMOTE_ADDR'] . "}\n";
        
        // 暫定
        // if ($Customer instanceof \Eccube\Entity\Customer) {
        //     $msg .= 'customer_id = ' . ($Customer ? $Customer->getId() : '') . "]\n";
        // } else if($Customer instanceof \Eccube\Entity\Member) {
        //     $msg .= 'login_id = ' . $Customer->getId() . '[' . session_id() . ']' . "\n";
        // }   
        
        $msg .= "[";
        $data = debug_backtrace(false);
        array_shift($data);
        array_shift($data);
        foreach ($data as $item) {
            if (array_key_exists('file', $item)) {
                $msg .= sprintf("file: {$item['file']} line: {$item['line']} function: {$item['function']} \n");
            } else {
                continue;
            }
        }
        $msg .= "]";
        
        return $msg;
    }
    
    /**
     * @return \Monolog\Logger
     */
    public function getCardLogger()
    {
        return $this->CardLogger;
    }

    /**
     * @return \Monolog\Logger
     */
    public function getCvsLogger()
    {
        return $this->CvsLogger;
    }
    
    public function addCardNotice($mess)
    {
        $this->getCardLogger()->notice($this->GetLogInfo() . "[{$mess}]");
    }
    
    public function addCvsNotice($mess)
    {
        $this->getCvsLogger()->notice($this->GetLogInfo() . "[{$mess}]");
    }
    
    public function addErrorLog($mess)
    {
        $this->ErrorLogger->error($this->GetLogInfo() . "[{$mess}]");
    }
    
    /**
     * @param unknown $codeStr
     * @return string[]
     */
    public function reErrorDecode($codeStr)
    {
        $errorMess = array();
        
        $self = Yaml::parse(file_get_contents(__DIR__ . '/../errors.yml'));
        
        $codes = explode("|", $codeStr);
        foreach ($codes as $code) {
            $error = $self['arrErrors'][$code];
            
            if (!array_key_exists(0, $errorMess)) {
                $errorMess[0] = "";
            }
            
            if (!array_key_exists(1, $errorMess)) {
                $errorMess[1] = "";
            }
            
            if (!array_key_exists(2, $errorMess)) {
                $errorMess[2] = "";
            }
            
            if ($error) {
                $arrErr = explode("|", $error);
                $errorMess[0] .= "\n" . $arrErr[0];
                $errorMess[1] .= "\n" . $arrErr[1];
                $errorMess[2] .= "\n" . $arrErr[2];
            } else {
                $errorMess[0] .= "";
                $errorMess[1] .= "";
                $errorMess[2] .= "";
            }
        }
        
        return $errorMess;
    }
    
    public function setActionBasic(Basic $Basic, \Plugin\SlnPayment42\Entity\ConfigSubData $config)
    {
        $Basic['MerchantId'] = $config->getMerchantId();
        $Basic['MerchantPass'] = $config->getMerchantPass();
        $Basic['TransactionDate'] = date("Ymd");
    }
    
    public function setActionMerchantFree(Basic $Basic, \Eccube\Entity\Order $Order)
    {
        $Basic['MerchantFree1'] = $Order->getId();
        $Basic['MerchantFree2'] = sprintf("%s|%s|%s", "0", Constant::VERSION, $Order->getCustomer() ? $Order->getCustomer()->getId() : null);
        $Basic['MerchantFree3'] = $Order->getCustomer() ? $Order->getCustomer()->getId() : null;
        
        $event = new EventArgs(
                array(
                    'Order' => $Order,
                    'Basic' => $Basic
                )
            );
        $this->eventDispatcher->dispatch($event, 'sln.payment.util.merchant_free');
    }
    
    public function setActionNotOrderMerchantFree(Basic $Basic, \Eccube\Entity\Customer $Customer)
    {
        $Basic['MerchantFree2'] = sprintf("%s|%s", "0", Constant::VERSION);
        $Basic['MerchantFree3'] = $Customer ? $Customer->getId() : null;
    }
    
    public function setActionNewKaiin(MemCardIdRepository $memCardIdRepository, Basic $Basic, \Eccube\Entity\Customer $Customer, $authMagic)
    {
        list($Basic['KaiinId'], $Basic['KaiinPass']) = $this->getNewKaiin($memCardIdRepository, $Customer, $authMagic);
    }
    
    public function delKaiin(MemCardIdRepository $memCardIdRepository, \Eccube\Entity\Customer $Customer) 
    {
        $memCardIdRepository->nextMemId($Customer);
    }
    
    public function getNewKaiin(MemCardIdRepository $memCardIdRepository, \Eccube\Entity\Customer $Customer, $authMagic)
    {
        $memId = 500 + $memCardIdRepository->getMemId($Customer);
        
        $KaiinId = sprintf("%03d", $memId) . sprintf("%010d", $Customer->getId());
        $KaiinPass = substr(preg_replace("[^0-9a-zA-Z]", "", md5($Customer->getId() . $authMagic)),0,12);
        return array($KaiinId, $KaiinPass);
    }
    
    public function deCodeRespData(Basic $Basic, $response_body)
    {
        $arrPTmp = explode("&", $response_body);
        foreach ($arrPTmp as $pTmp) {
            $arr = explode("=", $pTmp);
            $Basic[$arr[0]] = $arr[1];
        }
    }
    
    public function changeMemCard(Mem $mem, PluginConfigRepository $configRepository, \Eccube\Entity\Customer $Customer, \Plugin\SlnPayment42\Service\SlnContent\Credit\Member $member)
    {
        try {
            //登録済クレジットカード存在判断
            $ReMemRef = $mem->MemRef($Customer, $configRepository->getConfig());
        } catch (\Exception $e) {
            $ReMemRef = null;
        }
        
        if ($ReMemRef) {//会員
            switch ($ReMemRef->getContent()->getKaiinStatus()) {
                case 0://有効
                case 1://カード無効
                    $mem->MemChg($Customer, $configRepository->getConfig(), $member);
                    break;
                case 2://ログイン回数無効
                case 3://会員無効
                    $mem->MemUnInval($Customer, $configRepository->getConfig());
                    sleep(5);// 決済サーバーからの通知待ち　旧仕様
                    try {
                        $mem->MemChg($Customer, $configRepository->getConfig(), $member);
                    } catch (SlnShoppingException $e) {
                        $mem->MemInval($Customer, $configRepository->getConfig());
                        throw new SlnShoppingException($e->getMessage());
                    } catch (\Exception $e) {
                        $mem->MemInval($Customer, $configRepository->getConfig());
                        throw new \Exception($e->getMessage());
                    }
        
                    break;
                case 4://会員削除
                    $mem->MemAdd($Customer, $configRepository->getConfig(), $member);
                    break;
                default:
                    break;
            }
        } else {//会員存在していない場合
            //会員情報とカードを登録する
            $mem->MemAdd($Customer, $configRepository->getConfig(), $member);
        }
    }
    
    public function delMemCard(Mem $mem, PluginConfigRepositoy $configRepository, \Eccube\Entity\Customer $Customer)
    {        
        try {
            //登録済クレジットカード存在判断
            $ReMemRef = $mem->MemRef($Customer, $configRepository->getConfig());
        } catch (\Exception $e) {
            $ReMemRef = null;
        }
        
        if ($ReMemRef) {
            switch ($ReMemRef->getContent()->getKaiinStatus()) {
                case 0://有効
                case 1://カード無効
                    //会員無効処理を行う
                    $mem->MemInval($Customer, $configRepository->getConfig());
                    break;
                default:
                    break;
            }
        }
    }
    
    public function aesEnCode(\Plugin\SlnPayment42\Service\SlnAction\Content\Basic $requestParamHash, CryptAES $CryptAES) 
    {
        $arrEnCodeData = array();
        foreach ($requestParamHash->getPostData() as $key => $value) {
            
            if (strlen($value) == 0) {
                continue;
            }
            
            if ($key == "MerchantId") {
                continue;
            } else {
                $arrEnCodeData[] = sprintf("%s=%s", $key, $this->url_encode($value));
            }
        }
    
        $str = $CryptAES->encrypt(join('&',$arrEnCodeData));
        return base64_encode($str);
    }
    
    public function aesDeCode($body, CryptAES $CryptAES) 
    {
    
        //$body = $this->url_decode($body);
        $body = base64_decode($body);
        $body = $CryptAES->decrypt($body);
    
        $data = explode('&', $body);
    
        $reData = array();
    
        foreach ($data as $str) {
            list($key, $value) = explode('=', $str);
            $reData[$key] = $this->url_decode($value);
        }
    
        return $reData;
    }
    
    public function url_encode($string)
    {
        return urlencode($string);
    }
    
    public function url_decode($string)
    {
        return urldecode($string);
    }
    
    public function logDataReset($data)
    {
        if (array_key_exists('KaiinPass', $data) && $data['KaiinPass']) {
            $data['KaiinPass'] = '****';
        }
        
        if (array_key_exists('MerchantPass', $data) && $data['MerchantPass']) {
            $data['MerchantPass'] = '****';
        }
        
        if (array_key_exists('CardNo', $data) && $data['CardNo']) {
            $data['CardNo'] = '****' . substr($data['CardNo'], -4);
        }
        
        if (array_key_exists('CardExp', $data) && $data['CardExp']) {
            $data['CardExp'] = '****';
        }
        
        if (array_key_exists('KanaSei', $data) && $data['KanaSei']) {
            $data['KanaSei'] = '****';
        }
        
        if (array_key_exists('KanaMei', $data) && $data['KanaMei']) {
            $data['KanaMei'] = '****';
        }
        
        if (array_key_exists('BirthDay', $data) && $data['BirthDay']) {
            $data['BirthDay'] = '****';
        }
        
        if (array_key_exists('TelNo', $data) && $data['TelNo']) {
            $data['TelNo'] = '****';
        }
        
        if (array_key_exists('SecCd', $data) && $data['SecCd']) {
            $data['SecCd'] = '****';
        }
        
        return $data;
    }

    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }

    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    public function redirectToRoute($route, array $parameters = array(), $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * EC-CUBE4.0系Flashへのメッセージ設定系メソッドをコピー
     */

    public function addSuccess($request, $message, $namespace = 'front') {
        $session = $request->getSession();
        $session->getFlashBag()->add('eccube.'.$namespace.'.success', $message);
    }

    public function addError($request, $message, $namespace = 'front')
    {
        $session = $request->getSession();
        $session->getFlashBag()->add('eccube.'.$namespace.'.error', $message);
    }

    public function addDanger($request, $message, $namespace = 'front')
    {
        $session = $request->getSession();
        $session->getFlashBag()->add('eccube.'.$namespace.'.danger', $message);
    }

    public function addWarning($request, $message, $namespace = 'front')
    {
        $session = $request->getSession();
        $session->getFlashBag()->add('eccube.'.$namespace.'.warning', $message);
    }

    public function addInfo($request, $message, $namespace = 'front')
    {
        $session = $request->getSession();
        $session->getFlashBag()->add('eccube.'.$namespace.'.info', $message);
    }
}