<?php

class WxMchPayHelper
{
    private $parameters;

    function __construct($param)
    {
        $this->parameters = $param;
    }

    /**
     * 发送单个红包
     */
    public function exec($url)
    {
        $this->parameters['sign'] = $this->get_sign();
        $postXml = $this->arrayToXml($this->parameters);//生成接口XML信息
        $responseXml = $this->curl_post_ssl($url, $postXml);
        $responseObj = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $responseObj;
    }

    /**
     * 企业向微信用户个人付款/转账
     */
    public function transfers()
    {
        return $this->exec('https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers');
    }

    /**
     * 发送单个红包
     */
    public function send_redpack()
    {
        return $this->exec('https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack');
    }

    /**
     * 发送裂变红包
     */
    public function send_group()
    {
        return $this->exec('https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack');
    }

    /**
     * 检查生成签名参数
     */
    protected function check_sign_parameters()
    {
        if ($this->parameters["nonce_str"] &&
            $this->parameters["mch_billno"] &&
            $this->parameters["mch_id"] &&
            $this->parameters["wxappid"] &&
            $this->parameters["send_name"] &&
            $this->parameters["re_openid"] &&
            $this->parameters["total_amount"] &&
//            $this->parameters["max_value"] &&
//            $this->parameters["min_value"] &&
            $this->parameters["total_num"] &&
            $this->parameters["wishing"] &&
//            $this->parameters["client_ip"] &&
            $this->parameters["act_name"] &&
            $this->parameters["remark"]
        ) {
            return true;
        }
        return false;
    }

    /**
     * 例如：
     * appid：    wxd111665abv58f4f
     * mch_id：    10000100
     * device_info：  1000
     * body：    test
     * nonce_str：  ibuaiVcKdpRxkhJA
     * 第一步：对参数按照 key=value 的格式，并按照参数名 ASCII 字典序排序如下：
     * stringA="appid=wxd930ea5d5a258f4f&body=test&device_info=1000&mch_id=10000100&nonce_str=ibuaiVcKdpRxkhJA";
     * 第二步：拼接支付密钥：
     * stringSignTemp="stringA&key=192006250b4c09247ec02edce69f6a2d"
     * sign=MD5(stringSignTemp).toUpperCase()="9A0A8659F005D6984697E2CA0A9CF3B7"
     */
    protected function get_sign()
    {
        if (!WxPayConfig::KEY) {
            die('密钥不能为空');
        }
//        if (!$this->check_sign_parameters()) {
//            die('生成签名参数缺失');
//        }
        ksort($this->parameters);
        $unSignParaString = $this->formatQueryParaMap($this->parameters, false);

        return $this->sign($unSignParaString, WxPayConfig::KEY);
    }

    function curl_post_ssl($url, $vars, $second = 30, $aHeader = array())
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //这里设置代理，如果有的话
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //cert 与 key 分别属于两个.pem文件
        curl_setopt($ch, CURLOPT_SSLCERT, dirname(__FILE__) . '/../cert/apiclient_cert.pem');
        curl_setopt($ch, CURLOPT_SSLKEY, dirname(__FILE__) . '/../cert/apiclient_key.pem');
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/../cert/rootca.pem');

        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    function formatQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($v && "sign" != $k) {
                if ($urlencode) {
                    $v = urlencode($v);
                }
                $buff .= "$k=$v&";
            }
        }
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<$key>$val</$key>";
            } else {
                $xml .= "<$key><![CDATA[$val]]></$key>";
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    protected function sign($content, $key)
    {
        if (!$content) {
            die('签名内容不能为空');
        }
        $signStr = "$content&key=$key";
        return strtoupper(md5($signStr));
    }

}
