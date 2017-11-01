<?php

require_once(__DIR__ . "/../util/Log.php");
require_once(__DIR__ . "/../util/Http.php");
require_once(__DIR__ . "/../util/Cache.php");

/**
 * ISV授权方法类
 */
class ISVService
{
    private $http;
    private $cache;
    public function __construct() {
        $this->http = new Http();
        $this->cache = new Cache();
    }

    /**
     * 获取套件授权token
     * @param $suiteTicket
     * @return bool
     */
    public function getSuiteAccessToken($suiteTicket)
    {
        $suiteAccessToken = $this->cache->getSuiteAccessToken();
	    if (!$suiteAccessToken)
        {
            $response = $this->http->post("/service/get_suite_token",
                null,
                json_encode(array(
                    "suite_key" => SUITE_KEY,
                    "suite_secret" => SUITE_SECRET,
                    "suite_ticket" => $suiteTicket
                )));
            $this->check($response);
            $suiteAccessToken = $response->suite_access_token;
            $this->cache->setSuiteAccessToken($suiteAccessToken);
        }
        return $suiteAccessToken;
    }

    /**
     * 根据corpId 查询具体Corp信息
     * @param $corpId
     * @return bool|mixed
     */
    public function getCorpInfoByCorId($corpId){
        $corpList = json_decode($this->cache->getCorpInfo(),true);
        if(!is_array($corpList)){
            return false;
        }

        foreach($corpList as $corp){
            if($corp['corp_id'] == $corpId){
                return $corp;
            }
        }

        return false;
    }

    public function getPermanentCodeInfo($suiteAccessToken,$tmpAuthCode)
    {

        $permanentCodeResult = $this->http->post("/service/get_permanent_code",
            array(
                "suite_access_token" => $suiteAccessToken
            ),
            json_encode(array(
                "tmp_auth_code" => $tmpAuthCode
            )));

        $this->check($permanentCodeResult);

        /**
         * 拿到永久授权码信息
         */
        $auth_corp_info = $permanentCodeResult->auth_corp_info;
        $simple_corp = array();
        $simple_corp['corp_name'] = $auth_corp_info->corp_name;
        $simple_corp['corp_id'] = $auth_corp_info->corpid;
        $simple_corp['permanent_code'] = $permanentCodeResult->permanent_code;
        /**
         * 获取已存储的企业信息
         */
        $corpInfo = json_decode($this->cache->getCorpInfo());

        if(!is_array($corpInfo)){
            $corpInfo = array();
        }

        /**
         * 如果当前企业的永久授权码信息没有存储,缓存起来
         */
        if(!array_key_exists($auth_corp_info->corpid, $corpInfo)){
            $corpInfo[$auth_corp_info->corpid] = $simple_corp;
            $this->cache->setCorpInfo(json_encode($corpInfo));
        }
        return $simple_corp;
    }

    /**
     * @param $corpId
     * @param $appId
     * @return 当前企业在当前应用下面的agentId
     */
    public function getCurAgentId($corpId, $appId){
        $authInfo = json_decode($this->cache->getAuthInfo("corpAuthInfo_".$corpId));
        $agents = $authInfo->agent;

        foreach($agents as $agent){
            if($agent->appid == $appId){
                $agentId = $agent->agentid;
                return $agentId;
            }
        }
        return null;
    }

    public function getIsvCorpAccessToken($suiteAccessToken, $authCorpId, $permanentCode)
    {
        $key = "IsvCorpAccessToken_".$authCorpId;
        $corpAccessToken = $this->cache->getIsvCorpAccessToken($key);
        if (!$corpAccessToken)
        {
            $response = $this->http->post("/service/get_corp_token",
                array(
                    "suite_access_token" => $suiteAccessToken
                ),
                json_encode(array(
                    "auth_corpid" => $authCorpId,
                    "permanent_code" => $permanentCode
                )));
            $this->check($response);
            $corpAccessToken = $response->access_token;
            $this->cache->setIsvCorpAccessToken($key,$corpAccessToken);
        }
        return $corpAccessToken;
    }

    public function getAuthInfo($suiteAccessToken, $authCorpId, $permanentCode)
    {
        $authInfo = json_decode($this->cache->getAuthInfo("corpAuthInfo_".$authCorpId));
        if (!$authInfo)
        {
            $authInfo = $this->http->post("/service/get_auth_info",
                array(
                    "suite_access_token" => $suiteAccessToken
                ),
                json_encode(array(
                    "suite_key" => SUITE_KEY,
                    "auth_corpid" => $authCorpId,
                    "permanent_code" => $permanentCode
                )));
            $this->check($authInfo);
            $this->cache->setAuthInfo("corpAuthInfo_".$authCorpId, json_encode($authInfo->auth_info));
        }

        return $authInfo;
    }
    
    
    public function getAgent($suiteAccessToken, $authCorpId, $permanentCode, $agentId)
    {
        $response = $this->http->post("/service/get_agent", 
            array(
                "suite_access_token" => $suiteAccessToken
            ), 
            json_encode(array(
                "suite_key" => SUITE_KEY,
                "auth_corpid" => $authCorpId,
                "permanent_code" => $permanentCode,
                "agentid" => $agentId
            )));
        $this->check($response);
        return $response;
    }
    
    
    public function activeSuite($suiteAccessToken, $authCorpId, $permanentCode)
    {
        $key = "dingdingActive_".$authCorpId;
        $response = $this->http->post("/service/activate_suite", 
            array(
                "suite_access_token" => $suiteAccessToken
            ), 
            json_encode(array(
                "suite_key" => SUITE_KEY,
                "auth_corpid" => $authCorpId,
                "permanent_code" => $permanentCode
            )));

        if($response->errcode == 0){
            $this->cache->setActiveStatus($key);
            return $response;
        }else{
            return false;

        }

    }

    public function removeCorpInfo($authCorpId){
        $arr = array();
        $key1 = "dingdingActive_".$authCorpId;
        $key2 = "corpAuthInfo_".$authCorpId;
        $key3 = "IsvCorpAccessToken_".$authCorpId;
        $key4 = "js_ticket_".$authCorpId;
        $arr[] = $key1;
        $arr[] = $key2;
        $arr[] = $key3;
        $arr[] = $key4;
        $this->cache->removeByKeyArr($arr);
    }

    static function check($res)
    {
        if ($res->errcode != 0)
        {
            Log::e("FAIL: " . json_encode($res));
            exit("Failed: " . json_encode($res));
        }
    }
}
