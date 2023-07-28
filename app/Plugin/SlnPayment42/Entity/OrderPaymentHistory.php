<?php

namespace Plugin\SlnPayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderPaymentHistory
 * 
 * @ORM\Table(name="plg_sln_order_payment_history")
 * @ORM\Entity(repositoryClass="Plugin\SlnPayment42\Repository\OrderPaymentHistoryRepository")
 */
class OrderPaymentHistory
{
   /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="merchant_id", type="text", nullable=true)
     */
    private $merchantId;

    /**
     * @var string
     * 
     * @ORM\Column(name="transaction_id", type="text", nullable=true)
     */
    private $transactionId;

    /**
     * @var string
     * 
     * @ORM\Column(name="operate_id", type="text", nullable=true)
     */
    private $operateId;

    /**
     * @var string
     * 
     * @ORM\Column(name="process_id", type="text", nullable=true)
     */
    private $processId;

    /**
     * @var string
     * 
     * @ORM\Column(name="response_cd", type="text", nullable=true)
     */
    private $responseCd;

    /**
     * @var integer
     * 
     * @ORM\Column(name="send_flg", type="integer", nullable=false)
     */
    private $sendFlg;

    /**
     * @var integer
     * 
     * @ORM\Column(name="request_flg", type="integer", nullable=false)
     */
    private $requestFlg;

    /**
     * @var string
     * 
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    private $body;

    /**
     * @var integer
     * 
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    private $orderId;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="create_date", type="datetime", nullable=false)
     */
    private $createDate;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="update_date", type="datetime", nullable=false)
     */
    private $updateDate;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     * @return OrderPaymentHistory
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer 
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set merchantId
     *
     * @param string $merchantId
     * @return OrderPaymentHistory
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * Get merchantId
     *
     * @return string 
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * Set transactionId
     *
     * @param string $transactionId
     * @return OrderPaymentHistory
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    /**
     * Get transactionId
     *
     * @return string 
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Set operateId
     *
     * @param string $operateId
     * @return OrderPaymentHistory
     */
    public function setOperateId($operateId)
    {
        $this->operateId = $operateId;

        return $this;
    }

    /**
     * Get operateId
     *
     * @return string 
     */
    public function getOperateId()
    {
        return $this->operateId;
    }

    /**
     * Set processId
     *
     * @param string $processId
     * @return OrderPaymentHistory
     */
    public function setProcessId($processId)
    {
        $this->processId = $processId;

        return $this;
    }

    /**
     * Get processId
     *
     * @return string 
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * Set responseCd
     *
     * @param string $responseCd
     * @return OrderPaymentHistory
     */
    public function setResponseCd($responseCd)
    {
        $this->responseCd = $responseCd;

        return $this;
    }

    /**
     * Get responseCd
     *
     * @return string 
     */
    public function getResponseCd()
    {
        return $this->responseCd;
    }

    /**
     * Set sendFlg
     *
     * @param integer $sendFlg
     * @return OrderPaymentHistory
     */
    public function setSendFlg($sendFlg)
    {
        $this->sendFlg = $sendFlg;

        return $this;
    }

    /**
     * Get sendFlg
     *
     * @return integer 
     */
    public function getSendFlg()
    {
        return $this->sendFlg;
    }

    /**
     * Set requestFlg
     *
     * @param integer $requestFlg
     * @return OrderPaymentHistory
     */
    public function setRequestFlg($requestFlg)
    {
        $this->requestFlg = $requestFlg;

        return $this;
    }

    /**
     * Get requestFlg
     *
     * @return integer 
     */
    public function getRequestFlg()
    {
        return $this->requestFlg;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return OrderPaymentHistory
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string 
     */
    public function getBody()
    {
        return $this->body;
    }
    
    protected $notLogItem = array('MerchantPass', 'ShouhinName', 'Free1', 'Free2', 'Free3', 'Free4', 'Free5',
        'Free6', 'Free7', 'Comment', 'Free8', 'Free9', 'Free10', 'Free11', 'Free12', 'Free13', 'Free14', 'Free15',
        'Free16', 'Free17', 'Free18', 'Free19', 'ReturnURL', 'Title', 'code', 'rkbn', 'ProcessId',
        'ProcessPass', 'KaiinPass'
    );
    
    /**
     * @return string
     */
    public function getBodyView()
    {
        $reStr = "";
        $data = json_decode($this->getBody(), 1);
        if (count($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->notLogItem)) {
                    continue;
                }
                $reStr .= "{$key}={$value} ";
            }
        }
        
        return $reStr;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return OrderPaymentHistory
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Get createDate
     *
     * @return \DateTime 
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set updateDate
     *
     * @param \DateTime $updateDate
     * @return OrderPaymentHistory
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate
     *
     * @return \DateTime 
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }
}
