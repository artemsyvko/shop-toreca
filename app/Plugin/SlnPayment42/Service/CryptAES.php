<?php

namespace Plugin\SlnPayment42\Service;

class CryptAES
{
    protected $cipher = 'AES-128-CBC';
    protected $secret_key = '';
    protected $iv         = '';    

    public function setCipher($cipher)
    {
        $this->cipher = $cipher;
    }

    public function setIv($iv)
    {
        $this->iv = $iv;
    }

    public function setKey($key)
    {
        $this->secret_key = $key;
    }

    public function encrypt($str) {
        $ivsize = openssl_cipher_iv_length($this->cipher);
        if (empty($this->iv)) {
            $iv = random_bytes($ivsize);
        }
        else {
            $iv = $this->iv;
        }

        $rt = openssl_encrypt($str, $this->cipher, $this->secret_key, OPENSSL_RAW_DATA, $iv);

        return $rt;
    }

    public function decrypt($str) {
        $ivsize = openssl_cipher_iv_length($this->cipher);
        if (empty($this->iv)) {
            $iv = random_bytes($ivsize);
        }
        else {
            $iv = $this->iv;
        }
        
        $rt = openssl_decrypt($str, $this->cipher, $this->secret_key, OPENSSL_RAW_DATA, $iv);
        
        return $rt;
    }
}