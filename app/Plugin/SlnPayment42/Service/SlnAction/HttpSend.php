<?php

namespace Plugin\SlnPayment42\Service\SlnAction;

use Plugin\SlnPayment42\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment42\Service\Util;
use Plugin\SlnPayment42\Repository\OrderPaymentHistoryRepository;
use GuzzleHttp\Client;

class HttpSend
{
    /**
     * @var Util
     */
    protected $util;

    /**
     * @var OrderPaymentHistoryRepository
     */
    protected $orderPaymentHistoryRepository;

    public function __construct(
        Util $util,
        OrderPaymentHistoryRepository $orderPaymentHistoryRepository
    ) {
        $this->util = $util;
        $this->orderPaymentHistoryRepository = $orderPaymentHistoryRepository;
    }
    
    protected $error;
    
    public function setError($error)
    {
        $this->error = $error;
    }
    
    public function getError()
    {
        return $this->error;
    }
    
    public function sendData($url, Basic $basic, $orderId, $isCard = true)
    { 
        $util = $this->util;
        $client = new Client([
            'curl.options' => [
                'CURLOPT_SSLVERSION' => 6, 'CURLOPT_TIMEOUT' => 10
            ]
        ]);
        
        $logData = $basic->getPostData();
        
        if (array_key_exists('KaiinPass', $logData) && $logData['KaiinPass']) {
            $logData['KaiinPass'] = '****';
        }
        
        if (array_key_exists('MerchantPass', $logData) && $logData['MerchantPass']) {
            $logData['MerchantPass'] = '****';
        }
        
        if (array_key_exists('CardNo', $logData) && $logData['CardNo']) {
            $logData['CardNo'] = "****";
        }
        
        if (array_key_exists('CardExp', $logData) && $logData['CardExp']) {
            $logData['CardExp'] = "****";
        }
        
        if (array_key_exists('SecCd', $logData) && $logData['SecCd']) {
            $logData['SecCd'] = "****";
        }
        
        if (array_key_exists('KanaSei', $logData) && $logData['KanaSei']) {
            $logData['KanaSei'] = "****";
        }
        
        if (array_key_exists('KanaMei', $logData) && $logData['KanaMei']) {
            $logData['KanaMei'] = "****";
        }
        
        if (array_key_exists('BirthDay', $logData) && $logData['BirthDay']) {
            $logData['BirthDay'] = "****";
        }
        
        if (array_key_exists('TelNo', $logData) && $logData['TelNo']) {
            $logData['TelNo'] = "****";
        }
        
        if (array_key_exists('SecCd', $logData) && $logData['SecCd']) {
            $logData['SecCd'] = "****";
        }
        
        if ($isCard) {
            $util->addCardNotice("url: {$url} send_data:" . json_encode($logData));
        } else {
            $util->addCvsNotice("url: {$url} send_data:" . json_encode($logData));
        }
        
        if ($orderId) {
            $this->orderPaymentHistoryRepository->addSendRequestLog($orderId, $basic);
        }
        
        try {
            // デバッグ時以外コメントアウトすること
            // $trace = debug_backtrace();
            // foreach($trace as $line) {
            //     if (array_key_exists('file', $line)) {
            //         log_debug($line["file"] . ': ' . $line["line"]);
            //     }
            //     else {
            //         log_debug('Unknown: ' . print_r(array_keys($line), true));
            //     }
            // }
            // log_debug('SLN HttpSend : ' . $url);
            $postData = $basic->getPostData();
            // log_debug('POST : ' . print_r($postData, true));
            $response = $client->request('POST', $url, [
                'form_params' => $postData
            ]);
            // log_debug('Response HTTP Status code : ' . $response->getStatusCode());
            // log_debug('Response Body : ' . $response->getBody(true));
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            $util->addErrorLog($e->getMessage() . " " . $e->getFile() . $e->getLine());
            return false;
        }
        
        $r_code = $response->getStatusCode();
        
        if ($isCard) {
            $util->addCardNotice("url: {$url} re_code:" . $r_code);
        } else {
            $util->addCvsNotice("url: {$url} re_code:" . $r_code);
        }
        
        switch ($r_code) {
            case 200:
                break;
            default:
                $msg = '決済エラー http code:' . $r_code;
                $util->addErrorLog($msg);
                return false;
        }
        
        $response_body = $response->getBody(true);
        
        if (!$response_body) {
            $msg = 'レスポンスデータエラー: レスポンスがありません。';
            $this->setError($msg);
            $util->addErrorLog($msg);
            return false;
        }
        
        $logData = preg_replace('/(CardExp=\d*)/', 'CardExp=****', $response_body);

        if ($isCard) {
            $util->addCardNotice("url: {$url} re_body:" . $logData);
        } else {
            $util->addCvsNotice("url: {$url} re_body:" . $logData);
        }
        
        return $response_body;
    }
    
    
    
    
}
