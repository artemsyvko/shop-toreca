<?php

namespace Plugin\SlnPayment42\Controller;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Plugin\SlnPayment42\Form\Type\CardType;
use Plugin\SlnPayment42\Exception\SlnShoppingException;
use Plugin\SlnPayment42\Repository\PluginConfigRepository;
use Plugin\SlnPayment42\Repository\OrderPaymentStatusRepsotory;
use Plugin\SlnPayment42\Service\Util;
use Plugin\SlnPayment42\Service\SlnMailService;
use Plugin\SlnPayment42\Service\SlnAction\Mem;


class MypageController extends AbstractController
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * PluginConfigRepository
     */
    protected $configRepository;

    /**
     * @var Util
     */
    protected $util;

    /**
     * @var SlnMailService
     */
    protected $mail;

    /**
     * @var Mem;
     */
    protected $mem;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        PluginConfigRepository $configRepository,
        Util $util,
        SlnMailService $mail,
        Mem $mem
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->configRepository = $configRepository;
        $this->util = $util;
        $this->mail = $mail;
        $this->mem = $mem;
    }

    /**
     * @Route("/mypage/sln_edit_card", name="sln_edit_card")
     * @Template("@SlnPayment42/sln_edit_card.twig")
     */
    public function editCard(Request $request)
    {
        $isError = false;
        
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('mypage');
        }
        
        /* @var $Customer \Eccube\Entity\Customer */
        $Customer = $this->getUser();
        
        $config = $this->configRepository->getConfig();

        $form = $this->createForm(CardType::class);
        $form->remove('AddMem')->remove('PayType');

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            
            $isDel = $form->get('Token')->getData() == "del";
            
            try {
                if ($isDel) {
                    // 登録カード情報削除処理
                    $this->mem->MemInval($Customer, $config);
                    $this->addWarning('カード情報を更新しました', 'sln_mypage_card');
                    return $this->redirectToRoute('sln_edit_card');
                }

                if ($form->isSubmitted() && $form->isValid()) {
                    if ($form->get('Token')->getData()) {
                        //トークンによりカード登録
                        $member = new \Plugin\SlnPayment42\Service\SlnContent\Credit\Member();
                        $member->setToken($form->get('Token')->getData());
                        $this->util->changeMemCard($this->mem, $this->configRepository, $Customer, $member);
                    }
                    $this->addWarning('カード情報を更新しました', 'sln_mypage_card');
                    return $this->redirectToRoute('sln_edit_card');
                }
                else {
                    $this->addWarning('入力項目をご確認ください。', 'sln_mypage_card');
                }
            } catch (SlnShoppingException $e) {
                log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());

                $this->addWarning($e->getMessage(), 'sln_mypage_card');
            
                $log = sprintf("card shopping error:%s mypage", $e->getMessage() . " " . $e->getFile() . $e->getLine());
                $this->util->addCardNotice($log);
            
                if ($e->checkSystemError()) {
                    $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail() . 'order_id:' . ' mypage');
                    $this->mail->sendErrorMail($e->getSlnErrorName() . $e->getSlnErrorDetail());
                }
            
                return $this->redirectToRoute('sln_edit_card');
            } catch (\Exception $e) {
                log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());

                $this->addWarning('通信エラーが発生しました、後ほどお試しください。', 'sln_mypage_card');
                return $this->redirectToRoute('sln_edit_card');
            }
        }
       
        //登録済クレジットカード情報
        $OldCard = null;
        
        try {
            //カード登録判断
            //登録済クレジットカード存在判断
            $ReMemRef = $this->mem->MemRef($Customer, $config);
            
            if ($ReMemRef->getContent()->getKaiinStatus() == 0) {
                //カード登録済
                $OldCard = $ReMemRef->getContent();
            }
        } catch (\Exception $e) {
            log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
        }
        
        return [
            'TokenJsUrl' => $config->getCreditConnectionPlace6(),
            'TokenNinsyoCode' => $config->getTokenNinsyoCode(),
            'form' => $form->createView(),
            'config' => $config,
            'OldCard' => $OldCard,
            'isError' => $isError,
            'OldYear' => $OldCard ? substr($OldCard->getCardExp(), 0, 2) : '',
            'OldMon' => $OldCard ? substr($OldCard->getCardExp(), -2) : '',
            'Is3DPay' => $config->getThreedPay() == 1,
        ];
    }
    
    public function threeCard(Request $request) 
    {
        /* @var $Customer \Eccube\Entity\Customer */
        $Customer = $request->get('Order')->getCustomer();
        
        $EncryptValue = $_POST['EncryptValue'];
        if (!strlen($EncryptValue)) {
            $this->addWarning("クレジットカード登録失敗しました。", "sln_mypage_card");
            return $this->redirectToRoute('sln_edit_card');
        }
        
        try {
            $this->mem->DeCodeThreeDResponse($EncryptValue, "MpageThreeCard");
        } catch (SlnShoppingException $e) {
            $this->addWarning($e->getMessage(), 'sln_mypage_card');
            
            if ($Customer->getId()) {
                try {
                    $this->util->delMemCard($Customer);
                } catch (\Exception $e) {
                    log_error(__FILE__ . '(' . __LINE__ . ') ' . $e->getMessage());
                }
            }
        
            $log = sprintf("card shopping error:%s mypage", $e->getMessage() . " " . $e->getFile() . $e->getLine());
            $this->util->addCardNotice($log);
        
            if ($e->checkSystemError()) {
                $this->util->addErrorLog($e->getSlnErrorName() . $e->getSlnErrorDetail());
                $this->mail->sendErrorMail($e->getSlnErrorName() . $e->getSlnErrorDetail());
            }
        
            return $this->redirectToRoute('sln_edit_card');
        }
        
        $this->addWarning('カード情報を更新しました', 'sln_mypage_card');
        return $this->redirectToRoute('sln_edit_card');
    }
}