<?php
namespace common\components;

class CommonAes
{

    private $_secret_key = '';

    public function __construct($key = '4af2cfa4e4d111e59e0600163e0219bc')
    {
        $this->_secret_key = $key;
    }

    public function encode($data)
    {
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $this->_secret_key, $iv);
        $encrypted = mcrypt_generic($td, $data);
        mcrypt_generic_deinit($td);
        
        return $iv . $encrypted;
    }

    public function sign($data)
    {
        $signkey = "jksladfxoibnsadf123sdfhxc3";
        ksort($data);
        $string = "";
        foreach ($data as $key => $value) {
            if ($key != "sign") {
                $string .= $key . "=" . $value . "&";
            }
        }
        $string = $string . $signkey;
        return strtoupper(md5($string));
    }

    public function decode($data)
    {
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
        $iv = mb_substr($data, 0, 32, 'ASCII');
        mcrypt_generic_init($td, $this->_secret_key, $iv);
        $data = mb_substr($data, 32, mb_strlen($data, 'ASCII'), 'ASCII');
        $data = mdecrypt_generic($td, $data);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        
        return trim($data);
    }
}

?>