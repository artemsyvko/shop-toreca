<?php

namespace Plugin\SlnPayment42\Service;

use Eccube\Service\MailService;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\MailHistory;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Repository\MailTemplateRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Plugin\SlnPayment42\Service\Method\CvsMethod;
use Plugin\SlnPayment42\Service\Util;
use Plugin\SlnPayment42\Service\BasicItem;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Twig\Environment;

class SlnMailService
{
    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var MailTemplateRepository
     */
    protected $mailTemplateRepository;

    /**
     * @var MailHistoryRepository
     */
    private $mailHistoryRepository;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * @var Util
     */
    protected $util;
    
    /**
     * @var BasicItem
     */
    protected $basicItem;

    /**
     * @var PluginConfigRepository
     */
    protected $configRepository;
    

    public function __construct(
        MailService $mailService,
        MailerInterface $mailer,
        \Twig\Environment $twig,
        EventDispatcherInterface $eventDispatcher,
        MailTemplateRepository $mailTemplateRepository,
        MailHistoryRepository $mailHistoryRepository,
        BaseInfoRepository $baseInfoRepository,
        Util $util,
        BasicItem $basicItem,
        PluginConfigRepository $configRepository
    ) {
        $this->mailService = $mailService;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->eventDispatcher = $eventDispatcher;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailHistoryRepository = $mailHistoryRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->util = $util;
        $this->basicItem = $basicItem;
        $this->configRepository = $configRepository;
    }

    /**
     * Send order mail.
     *
     * @param \Eccube\Entity\Order $Order 受注情報
     * @return string
     */
    public function sendOrderMail(\Eccube\Entity\Order $Order, $PaymentLink)
    {
        log_info('受注メール送信開始');

        $config = $this->configRepository->getConfig();
        $MailTemplate = $this->mailTemplateRepository->find($config->getCvsOrderMailId());

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Order' => $Order,
            'PaymentLink' => $PaymentLink,
            'isCvsPay' => $Order->getPayment()->getMethodClass() == CvsMethod::class
        ]);

        $message = (new Email())
            ->subject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->from(new Address($this->BaseInfo->getEmail01(), $this->BaseInfo->getShopName()))
            ->to($Order->getEmail())
            ->bcc($this->BaseInfo->getEmail01())
            ->replyTo($this->BaseInfo->getEmail03())
            ->returnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Order' => $Order,
                'PaymentLink' => $PaymentLink,
                'isCvsPay' => $Order->getPayment()->getMethodClass() == CvsMethod::class
            ]);

            $message
                ->text($body)
                ->html($htmlBody);
        } else {
            $message->text($body);
        }

        $event = new EventArgs(
            [
                'message' => $message,
                'Order' => $Order,
                'MailTemplate' => $MailTemplate,
                'BaseInfo' => $this->BaseInfo,
            ],
            null
        );
        $this->eventDispatcher->dispatch($event, EccubeEvents::MAIL_ORDER);

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getTextBody())
            ->setOrder($Order)
            ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $htmlBody = $message->getHtmlBody();
        if (!empty($htmlBody)) {
            $MailHistory->setMailHtmlBody($htmlBody);
        }

        $this->mailHistoryRepository->save($MailHistory);

        log_info('受注メール送信完了', ['count' => $count]);

        return $message;
    }
    
    /**
     * コンビニ支払いの不一致データエラーメール
     * @param unknown $paramHash
     * @return Swift_Mime_MimePart
     */
    public function sendMailNoOrder($paramHash)
    {
        $config = $this->configRepository->getConfig();
        $isSendMail = $config->getIsSendMail();
        
        if (!$isSendMail) {
            return false;
        }
                        
        $logStr = "";
        foreach ($paramHash as $key => $value) {
            $logStr .= "{$key}={$value} ";
        }
        
        $this->util->addCvsNotice("recv error:" . $logStr);
        
        $body = $this->twig->render('SlnPayment42/Resource/template/mail_template/recv_no_order.twig', [
            'paramHash' => $paramHash,
        ]);
        
        $message = (new Email())
                        ->subject('['.$this->BaseInfo->getShopName() . '] 不一致データ検出')
                        ->from(new Address($this->BaseInfo->getEmail01(), $this->BaseInfo->getShopName()))
                        ->to($this->BaseInfo->getEmail04())
                        ->text($body);
        
        $this->mailer->send($message);
        
        return $message;
    }
    
    /**
     * 決済通信エラー通知メール
     * @param unknown $mess
     * @return Swift_Mime_MimePart
     */
    public function sendErrorMail($mess = '')
    {
        $config = $this->configRepository->getConfig();
        $isSendMail = $config->getIsSendMail();
        
        if (!$isSendMail) {
            return false;
        }
        
        $body = $mess . '詳しくは決済モジュールのエラーログをご確認ください。';
        
        $message = (new Email())
                        ->subject('決済通信エラーが発生しました。')
                        ->from(new Address($this->BaseInfo->getEmail01(), $this->BaseInfo->getShopName()))
                        ->to($this->BaseInfo->getEmail04())
                        ->text($body);
        
        $this->mailer->send($message);
        
        return $message;
    }
}