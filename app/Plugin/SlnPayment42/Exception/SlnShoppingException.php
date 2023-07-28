<?php

namespace Plugin\SlnPayment42\Exception;

use Eccube\Exception\ShoppingException;

class SlnShoppingException extends ShoppingException
{
    
    protected $allErrorMess = array();
    protected $orderId;
    
    public function __construct($message = null, $code = null, $previous = null, $allErrorMess, $orderId = null)
    {
        parent::__construct($message, $code, $previous);
        $this->allErrorMess = $allErrorMess;
        $this->orderId = $orderId;
    }
    
    public function getSlnErrorCode()
    {
        return $this->allErrorMess[0];
    }
    
    public function getSlnErrorName()
    {
        return $this->allErrorMess[1];
    }
    
    public function getSlnErrorUser()
    {
        return $this->allErrorMess[2];
    }
    
    public function getSlnErrorDetail()
    {
        return $this->allErrorMess[3];
    }

    public function getSlnErrorOrderId()
    {
        return $this->orderId;
    }
    
    public function checkSystemError()
    {
        $errorCode = array('K01','K02','K03','K04','K05','K06','K07','K08','K09','K10',
                            'K11','K12','K13','K14','K22','K23',
                            'K30','K31','K32','K33','K34','K35','K36',
                            'K37','K39','K45','K46','K47','K48','K96','K97','K98','K99',
                            'KG8','C01','C02','C03','C70','C71','C98',
                            'K28','K50','K53','K54','K55','K56','K57','K58',
                            'K59','K61','K96','K98','K99','W01','W02','W03','W04',
                            'W05','W06','W99'
        );
        return in_array($this->getSlnErrorCode(), $errorCode);
    }
}