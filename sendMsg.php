<?php
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/util/Log.php");
require_once(__DIR__ . "/util/Cache.php");
require_once(__DIR__ . "/api/Auth.php");
require_once(__DIR__ . "/api/User.php");
require_once(__DIR__ . "/api/Message.php");
require_once(__DIR__ . "/api/ISVServiceImpl.php");

$message = new Message();
$isvServierImpl = new ISVServiceImpl();
$user = new User();

$event = $_POST["event"];
switch($event){
    case '':
        echo json_encode(array("error_code"=>"4000"));
        break;
    case 'send_to_conversation':
        $sender = $_POST['sender'];
        $cid = $_POST['cid'];
        $content = $_POST['content'];
        $corpId = $_POST['corpId'];
        $corpInfo = $isvServierImpl->getCorpInfo($corpId);
        $accessToken = $corpInfo['corpAccessToken'];
        $option = array(
            "sender"=>$sender,
            "cid"=>$cid,
            "msgtype"=>"text",
            "text"=>array("content"=>$content)
        );
        $response = $message->sendToConversation($accessToken,$option);
        echo json_encode($response);
        break;

    case 'get_userinfo':
        $corpId = $_POST['corpId'];
        $corpInfo = $isvServierImpl->getCorpInfo($corpId);
        $accessToken = $corpInfo['corpAccessToken'];
        $code = $_POST["code"];
        $userInfo = $user->getUserInfo($accessToken, $code);
        echo json_encode($userInfo);
        break;
}