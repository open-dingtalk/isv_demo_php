<?php

class Cache
{
    public function setSuiteTicket($ticket)
    {
        $memcache = $this->getMemcache();
        $memcache->set("suite_ticket", $ticket);
    }
    
    public function getSuiteTicket()
    {
        $memcache = $this->getMemcache();
        return $memcache->get("suite_ticket");
    }
    
    public function setJsTicket($key,$ticket)
    {
        $memcache = $this->getMemcache();
        $memcache->set($key, $ticket, time() + 7000); // js ticket有效期为7200秒，这里设置为7000秒
    }
    
    public function getJsTicket($key)
    {
        $memcache = $this->getMemcache();
        return $memcache->get($key);
    }
    
    public function setSuiteAccessToken($accessToken)
    {
        $memcache = $this->getMemcache();
        $memcache->set("suite_access_token", $accessToken, time() + 7000); // suite access token有效期为7200秒，这里设置为7000秒
    }
    
    public function getSuiteAccessToken()
    {
        $memcache = $this->getMemcache();
        return $memcache->get("suite_access_token");
    }

    public function setIsvCorpAccessToken($key,$accessToken)
    {
        $memcache = $this->getMemcache();
        $memcache->set($key, $accessToken, time() + 7000);
    }

    public function getIsvCorpAccessToken($key)
    {
        $memcache = $this->getMemcache();
        return $memcache->get($key);
    }

    public function setTmpAuthCode($tmpAuthCode){
        $memcache = $this->getMemcache();
        $memcache->set("tmp_auth_code", $tmpAuthCode);
    }

    public function getTmpAuthCode(){
        $memcache = $this->getMemcache();
        return $memcache->get("tmp_auth_code");
    }

    public function setPermanentCode($key,$value){
        $memcache = $this->getMemcache();
        $memcache->set($key, $value);
    }

    public function getPermanentCode($key){
        $memcache = $this->getMemcache();
        return $memcache->get($key);
    }

    public function setActiveStatus($corpKey){
        $memcache = $this->getMemcache();
        $memcache->set($corpKey,100);
    }

    public function getActiveStatus($key){
        $memcache = $this->getMemcache();
        return $memcache->get($key);
    }

    public function setCorpInfo($data){
        $memcache = $this->getMemcache();
        $memcache->set('dingding_corp_info',$data);
    }

    public function getCorpInfo(){
        $memcache = $this->getMemcache();
        $corpInfo =  $memcache->get('dingding_corp_info');
        return $corpInfo;
    }


    public function setAuthInfo($key,$authInfo){
        $memcache = $this->getMemcache();
        $memcache->set($key,$authInfo);
    }

    public function getAuthInfo($key){
        $memcache = $this->getMemcache();
        return $memcache->get($key);
    }

    public function removeByKeyArr($arr){
        $memcache = $this->getMemcache();
        foreach($arr as $a){
            $memcache->set($a,'');
        }
    }

    private function getMemcache()
    {
        /*if (class_exists("Memcache"))
        {
            $memcache = new Memcache; 
            if ($memcache->connect('localhost', 11211))
            {
                return $memcache;   
            }
        }*/

        return new FileCache;
    }
    
    public function get($key)
    {
        return $this->getMemcache()->get($key);
    }
    
    public function set($key, $value)
    {
        $this->getMemcache()->set($key, $value);
    }
}

/**
 * fallbacks 
 */
class FileCache
{
    function set($key, $value, $expire_time = 0) {
        if($key&&$value){
            $data = json_decode($this->get_file(DIR_ROOT ."filecache.php"),true);
            $item = array();
            $item["$key"] = $value;

            $item['expire_time'] = $expire_time;
            $item['create_time'] = time();
            $data["$key"] = $item;
            $this->set_file("filecache.php",json_encode($data));
        }
    }

	function get($key)
	{
        if($key){
            $data = json_decode($this->get_file(DIR_ROOT ."filecache.php"),true);
            if($data&&array_key_exists($key,$data)){
                $item = $data["$key"];
                if(!$item){
                    return false;
                }
                if($item['expire_time']>0&&$item['expire_time'] < time()){
                    return false;
                }

                return $item["$key"];
            }else{
                return false;
            }

        }
	}

    function get_file($filename) {
        if (!file_exists($filename)) {
            $fp = fopen($filename, "w");
            fwrite($fp, "<?php exit();?>" . '');
            fclose($fp);
            return false;
        }else{
            $content = trim(substr(file_get_contents($filename), 15));
        }
        return $content;
    }

    function set_file($filename, $content) {
        $fp = fopen($filename, "w");
        fwrite($fp, "<?php exit();?>" . $content);
        fclose($fp);
    }
}
