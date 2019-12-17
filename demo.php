<?php
// 此示例需要开启php_curl、php_openssl模块

define('RSA_PUBLIC', '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCy5CulBZvqtUqL1w5iaO6lJibW
CU3yuIes+K65cxZVDscu0i6KPju1ktOPCVxSsWybMEejXsX0xn1Vyx6Aglnp+cd2
c6xpIAwg9e6N9G95R+tlh5efhDB3f+RoUXzOmqpqtjs0KdUDIbsJ68W4OMRIDL6A
C8ae1GgDbw6Areb7kwIDAQAB
-----END PUBLIC KEY-----');

// RSA加解密
class RSA {
    var $publicKey;
    function __construct($publicKey) {
        $this->publicKey = $publicKey;
    }
    function encryptByPublicKey($data) {
        //公钥加密
        $public_key = openssl_pkey_get_public($this->publicKey);
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_public_encrypt($chunk, $result, $public_key);
            $crypto.= $result;
        }
        return base64_encode($crypto);
    }
    // 公钥解密
    function decryptByPublicKey($data) {
        //公钥解密
        $public_key = openssl_pkey_get_public($this->publicKey);
        $crypto = '';
        foreach (str_split(base64_decode($data) , 128) as $chunk) {
            openssl_public_decrypt($chunk, $result, $public_key);
            $crypto.= $result;
        }
        return $crypto;
    }
}

// POST请求
function sendPost($url, $params) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/x-www-form-urlencoded"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// 创建订单示例
$rsa = new RSA(RSA_PUBLIC);
$params = array("merchantOrderNo" => "C22230224662456", "amount" => 1000, "type" => 0, "notifyUrl" => "http://www.baidu.com/callback", "remark" => "xxxxxxx", "ip" => "50.50.50.50");
$data = $rsa->encryptByPublicKey(json_encode($params));
echo "加密结果:<br/>" . $data . "<br/>";
$result = sendPost("https://user.wbzf.info:4431/merchant-api/api/open/createOrder", array("merchantId" => 800000, "data" => $data));
echo "请求结果:" . $result . "<br/>";
$json = json_decode($result);
if ($json->code == 0) {
    echo $rsa->decryptByPublicKey($json->data);
}

