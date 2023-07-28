<?php

namespace Plugin\SlnPayment42\Service\SlnAction\Content;

use Plugin\SlnPayment42\Service\SlnContent\Basic as contentBasic;

abstract class Basic implements \ArrayAccess
{
    
    /**
     * @var contentBasic
     */
    protected $content;
    
    abstract function getOperatePrefix();
    
    abstract function setContent(contentBasic $content);
    
    abstract function getContent();
    
    abstract function getDataKey();
    
    public function __construct($content) 
    {
        $this->setContent($content);
    }
    
    public function offsetExists($offset)
    {
        if(!array_key_exists($offset, $this->getDataKey())) {
            return false;
        }
        $method = $offset;
        return method_exists($this->content, "get$method") || method_exists($this->content, "is$method");
    }
    
    public function offsetSet($offset, $value)
    {
        if(!array_key_exists($offset, $this->getDataKey())) {
            return ;
        }
        
        $method = $offset;
    
        if (method_exists($this->content, "set$method")) {
            $this->content->{"set$method"}($value);
        }
    }
    
    protected function changeData($key, $value)
    {
        switch ($key) {
            case 'NameKanji':
                // ユーザ漢字氏名
                return mb_substr(mb_convert_kana($value, 'KVSA', 'UTF-8'),0,20);
            case 'NameKana':
                // ユーザカナ氏名
                return mb_substr(mb_convert_kana($value, 'KVSA', 'UTF-8'),0,20);;
            case 'PayLimit':	// 支払期限
                return date("YmdHi", strtotime("+7 day"));
            case 'ShouhinName':	// 商品名
                return mb_substr(mb_convert_kana($value, 'KVSA', 'UTF-8'),0,16);
            case 'KanaSei':
            case 'KanaMei':
                return mb_convert_kana($value, 'kha', 'UTF-8');
            case 'Free1':
            case 'Free2':
            case 'Free3':
            case 'Free4':
            case 'Free5':
            case 'Free6':
            case 'Free7':
            case 'Comment':
            case 'Free8':
            case 'Free9':
            case 'Free10':
            case 'Free11':
            case 'Free12':
            case 'Free13':
            case 'Free14':
            case 'Free15':
            case 'Free16':
                return mb_convert_kana($value, 'ASK', 'UTF-8');
                // あとは変数名中にあればそのまま使う
            default:
                return $value;
        }
    }
    
    public function offsetGet($offset)
    {
    
        if(!array_key_exists($offset, $this->getDataKey())) {
            throw new \Exception("not get data");
        }
    
        $method = $offset;
    
        if (method_exists($this->content, "get$method")) {
            return $this->content->{"get$method"}();
        }
    }
    
    public function offsetUnset($offset)
    {
    }
    
    public function newOperateId()
    {
        $lastName = explode('\\', get_class($this));
        return $this->getOperatePrefix() . array_pop($lastName);
    }
    
    public function getPostData()
    {
        $reData = array();
        if(!$this->content->getOperateId()) {
            $this->content->setOperateId($this->newOperateId());
        }

        foreach ($this->getDataKey() as $key => $value) {
            $value = $this->changeData($key, $this->content[$key]);
            if (!is_null($value) && strlen($value)) {
                $reData[$key] = $value;
            }
        }
        
        return $reData;
    }
}