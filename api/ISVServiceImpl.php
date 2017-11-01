<?php

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../util/Http.php");
require_once(__DIR__ . "/../util/Cache.php");
require_once(__DIR__ . "/ISVService.php");

class ISVServiceImpl{

    private $http;
    private $cache;
    private $isvService;
    public function __construct() {
        $this->http = new Http();
        $this->cache = new Cache();
        $this->isvService = new ISVService();
    }   
    
    public function getSuiteAccessToken(){
        $suiteTicket = $this->cache->getSuiteTicket();
        if(!$suiteTicket){
            Log::e("ERROR: suiteTicket not cached,please check the callback url");
            return false;
        }
        $suiteAccessToken = $this->isvService->getSuiteAccessToken($suiteTicket);
        return $suiteAccessToken;
    }

    public function getIsvCorpAccessToken($suiteAccessToken, $corpId, $permanetCode){
        $key = "dingdingActive_".$corpId;
        $corpAccessToken = $this->isvService->getIsvCorpAccessToken($suiteAccessToken, $corpId, $permanetCode);
        $status = $this->cache->getActiveStatus($key);
        if($status<=0&&$corpAccessToken!=""){
            $this->isvService->activeSuite($suiteAccessToken, $corpId, $permanetCode);
        }

        $this->isvService->getAuthInfo($suiteAccessToken, $corpId, $permanetCode);
        return $corpAccessToken;
    }

    public function getCorpInfo($corpId){
        $suiteAccessToken = $this->getSuiteAccessToken();
        $corpInfo = $this->isvService->getCorpInfoByCorId($corpId);
        $corpAccessToken = $this->getIsvCorpAccessToken($suiteAccessToken,$corpInfo['corp_id'],$corpInfo['permanent_code']);
        $corpInfo['corpAccessToken'] = $corpAccessToken;

        return $corpInfo;
    }
}

function check($res)
{
    if ($res->errcode != 0)
    {
        exit("Failed: " . json_encode($res));
    }
}
