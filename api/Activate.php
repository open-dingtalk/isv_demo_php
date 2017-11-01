<?php

require_once(__DIR__ . "/../util/Log.php");
require_once(__DIR__ . "/../util/Http.php");
require_once(__DIR__ . "/ISVService.php");

/**
 * 激活ISV套件方法类
 */

class Activate
{
    private $isvService;
    private $cache;

    public function __construct() {
        $this->isvService = new ISVService();
        $this->cache = new Cache();
    }
    
    /**
     * 某个企业的临时授权码在成功换取永久授权码后，开放平台将不再推送该企业临时授权码。
     */
    public function autoActivateSuite($tmpAuthCode)
    {
        //获取套件的suiteticket
	    $suiteTicket = $this->cache->getSuiteTicket();

        if($suiteTicket == ""){
            Log::e("[activeSuite]: suiteTicket is null");
            return false;
        }

        //获取套件的suiteAccessToken
        $suiteAccessToken = $this->isvService->getSuiteAccessToken($suiteTicket);

        if($suiteAccessToken == ""){
            Log::e("[activeSuite]: suiteAccessToken is null");
            return false;
        }
        Log::i("[Activate] getSuiteToken: " . $suiteAccessToken);

        //获取永久授权码以及corpid等信息，持久化，并激活临时授权码
        $permanetCodeInfo = $this->isvService->getPermanentCodeInfo($suiteAccessToken, $tmpAuthCode);
        if(null == $permanetCodeInfo){
            Log::e("[activeSuite]: permanetCodeInfo is empty");
            return false;
        }

        Log::i("[Activate] getPermanentCodeInfo: " . json_encode($permanetCodeInfo));
        
        $permanetCode = $permanetCodeInfo['permanent_code'];
        $authCorpId = $permanetCodeInfo['corp_id'];
        Log::i("[Activate] permanetCode: " . $permanetCode . ",  authCorpId: " . $authCorpId);
        
        /**
         * 获取企业access token
         */
        $corpAccessToken = $this->isvService->getIsvCorpAccessToken($suiteAccessToken, $authCorpId, $permanetCode);
        Log::i("[Activate] getCorpToken: " . $corpAccessToken);
        
        /**
         * 获取企业授权信息
         */
        $res = $this->isvService->getAuthInfo($suiteAccessToken, $authCorpId, $permanetCode);
        Log::i("[Activate] getAuthInfo: " . json_encode($res));
        self::check($res);

        /**
         * 激活套件
         */
        $res = $this->isvService->activeSuite($suiteAccessToken, $authCorpId, $permanetCode);
        Log::i("[activeSuite]: " . json_encode($res));
        self::check($res);
    }
    
    
    static function check($res)
    {
        if ($res->errcode != 0)
        {
            exit("Failed: " . json_encode($res));
        }
    }
}
