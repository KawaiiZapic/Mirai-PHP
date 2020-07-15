<?php
/**
 * PHP SDK for Mirai HTTP API
 * 
 * @author Zapic <kawaiizapic@zapic.cc>
 * @license AGPL-3.0
 * @version beta-0.0.1
 * 
 * @package Mirai
 * 
 */

namespace Mirai;

require_once "Events.php";
require_once "Exceptions.php";
require_once "Messages.php";
require_once "Objects.php";

/**
 * Bot Instance
 */

class Bot {
    private $_authKey;
    private $_session;
    private $_conn;
    private $_qq;

    /**
     * Connect to HTTP API
     * 
     * @param \Swoole\Coroutine\Http\Client $client An Swoole\Coroutine\Http\Client that connected to HTTP API
     * @param string $authKey Authkey for HTTP API
     * @param int $target QQ number of the target bot
     * 
     * @return void
     */

    public function __construct(\Swoole\Coroutine\Http\Client $client, string $authKey,int $target) {
        $this->_conn = $client;
        $this->_authKey = $authKey;
        $this->_qq = $target;
        $this->login();
    }

    /**
     * Login to HTTP API
     * 
     * @throws InvaildKeyException Invaild Authkey
     * @throws InvaildRespondException Server may not be a Mirai HTTP API
     * @throws ConnectFaliedError Failed to connect to HTTP API
     * @throws BindFailedException API return an error code while try to bind session to QQ
     * 
     * @return void
     */

    private function login():void{
        $client = &$this->_conn;
        $client->get("/about");
        $info = json_decode($client->body);
        if(!$info || $info->code != 0){
            throw new \Exception("Invaild respond,it doesn't seem like a vaild HTTP API.");
        }
        $ret = $client->post("/auth", json_encode(["authKey" => $this->_authKey]));
        if ($ret) {
            $ret = json_decode($client->body);
            if ($ret->code != 0) {
                throw new \Exception("Invaild AuthKey.");
            } else {
                $this->_session = $ret->session;
            }
        } else {
            throw new \Exception("Failed to connect to HTTP API.");
        }
        $ret = $client->post("/verify", json_encode(["sessionKey" => $this->_session, "qq" => $this->_qq]));
        if ($ret) {
            $ret = json_decode($client->body);
            if ($ret->code != 0) {
                throw new \Exception("Failed to bind seesion to QQ({$this->_qq}):{$ret->msg}.");
            }
        } else {
            throw new \Exception("Failed to connect to HTTP API.");
        }
        $this->callBotAPI("/config",["enableWebsocket"=>true]);
    }

    /**
     * Set callback function to handle bot events.
     * Callback function will recive 2 params,first is decoded json,last is the raw content.
     * 
     * @param callable $callback Function that handle callback
     * 
     * @throws ConnectionCloseError Connection to API closed.
     * @throws UpgradeFailedError Failed to upgrade connection to Websocket.
     * @throws Error Some unknown error occured 
     * 
     * @return void
     * 
     */

    public function setEventHandler(callable $callback):void{
        $this->_conn = new \Swoole\Coroutine\Http\Client($this->_conn->host, $this->_conn->port,$this->_conn->ssl);
        $ret = $this->_conn->upgrade("/all?sessionKey={$this->_session}");
        if(!$ret || $this->_conn->statusCode != 101){
            throw new \Exception("Failed to upgrade connection to Websocket.");
        }
        \go(function() use ($callback){
            while(true){
                $frame = $this->_conn->recv();
                if ($frame) {
                    $result = $this->EventFactory(json_decode($frame->data));
                } elseif($frame === false && $this->_conn->errCode === 0) {
                    throw new \Exception("Connection to Mirai HTTP API Closed:{$this->_conn->errMsg}.");
                } elseif($this->_conn->errCode == 104 && $this->_conn->closeNormal === true) {
                    break;
                } else {
                    throw new \Exception("Unknown error while recv from Websocket.");
                }
                call_user_func_array($callback,[$result,$frame->data]);
            }
        });
    }
    /**
     * Send request to API
     * 
     * @param string $path API to request
     * @param array $params Params that pass to API
     * @param string $method Default "post",Request method,only "post" or "get"
     * @param bool $raw Default false,if true,will return the raw content but not the decoded content.
     * 
     * @throws InvaildParamException Request method may not correct
     * @throws FetchFailedException Failed to fecth response form API.
     * 
     * @return any API respose
     */

    public function callBotAPI(string $path, array $params = [], string $method = "post",$raw = false){
        $client = new \Swoole\Coroutine\Http\Client($this->_conn->host, $this->_conn->port,$this->_conn->ssl);
        if ($method == "post") {
            $params['sessionKey'] = $this->_session;
            $params = json_encode($params);
            $ret = $client->post($path, $params);
        } elseif ($method == "get") {
            $qstr = "";
            foreach ($params as $k => $v) {
                $k = urlencode($k);
                $v = urlencode($v);
                $qstr .= "&{$k}={$v}";
            }
            $ret = $client->get("{$path}?sessionKey={$this->_session}{$qstr}");
        } else {
            $client->close();
            throw new \Exception("Unknown request method {$method}");
        }
        $client->close();
        if ($ret) {
            return $raw === true ? $client->body : json_decode($client->body);
        } else {
            throw new \Exception("Failed to fetch API response");
        }
    }

    /**
     * Get QQ ID of this Bot instance
     * 
     * @return int QQ ID
     * 
     */

    public function getId():int{
        return $this->_qq;
    }

    /**
     * Close connection to HTTP API
     * 
     * @return void
     * 
     */

    public function ShutDown():void{
        $this->_conn->closeNormal = true;
        $this->_conn->close();
    }

    /**
     * Upload image to Tecent server
     * 
     * @param string $type Type of image,"friend" or "group".
     * @param string $file Path to image.
     * 
     * @throws FileNotFoundException Image file not found
     * 
     * @return mixed API Respond.
     */
    
    public function uploadImage(string $type, string $file) {
        if(!file_exists($file) || !is_file($file)){
            throw new \Exception("File not found.");
        }
	    $boundary = uniqid();
	    $client = new \Swoole\Coroutine\Http\Client($this->_conn->host, $this->_conn->port,$this->_conn->ssl);
	    $client->setHeaders([
	        "Content-Type" => "multipart/form-data; boundary={$boundary};",
        ]);
	    $client->setMethod("POST");
	    $body = "--{$boundary}\r\nContent-Disposition: form-data; name=\"sessionKey\"\r\n\r\n{$this->_session}\r\n";
	    $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"type\"\r\n\r\n{$type}\r\n";
	    $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"img\"; filename=\"img\"\r\nContent-Type: " . getimagesize($file)['mime'] . "\r\n\r\n" . \Co::readFile($file) . "\r\n";
	    $body .= "--{$boundary}--\r\n";
	    $client->setData($body);
	    $client->execute("/uploadImage");
        $client->close();
        return json_decode($client->body);
    }

    public function sendImageMessage(string $urls, int $qq = null, int $group = null, int $target = null) {
        $pre = [];
        if (!is_null($qq)) {
            $pre['qq'] = $qq;
        }
        if (!is_null($group)) {
            $pre['group'] = $group;
        }
        if (!is_null($target)) {
            $pre['target'] = $target;
        }
        $pre['urls'] = $urls;
        return $this->callBotAPI("/sendImageMessage", $pre);
    }

    public function sendFriendMessage(int $target, array $chain, bool $quote = null) {
        $pre = [
            "target" => $target,
            "messageChain" => $chain,
        ];
        if (!is_null($quote)) {
            $pre['quote'] = $quote;
        }
        return $this->callBotAPI("/sendFriendMessage", $pre);
    }

    public function sendTempMessage(int $qq, int $group, array $chain, bool $quote = null) {
        $pre = [
            "qq" => $qq,
            "group" => $group,
            "messageChain" => $chain,
        ];
        if (!is_null($quote)) {
            $pre['quote'] = $quote;
        }
        return $this->callBotAPI("/sendTempMessage", $pre);
    }
    public function sendGroupMessage(int $target, array $chain, bool $quote = null) {
        $pre = [
            "target" => $target,
            "messageChain" => $chain,
        ];
        if (!is_null($quote)) {
            $pre['quote'] = $quote;
        }
        return $this->callBotAPI("/sendGroupMessage", $pre);
    }

    public function recallMessage(int $id) {
        return $this->callBotAPI("/recall", ["target" => $id]);
    }

    public function getFriendList() {
        return $this->callBotAPI("/friendList", [], "get");
    }

    public function getGroupList() {
        return $this->callBotAPI("/groupList", [], "get");
    }

    public function getMemberList(int $target) {
        return $this->callBotAPI("/groupList", ["target" => $target], "get");
    }

    public function muteAll(int $target) {
        return $this->callBotAPI("/muteAll", ["target" => $target]);
    }
    public function unmuteAll(int $target) {
        return $this->callBotAPI("/unmuteAll", ["target" => $target]);
    }
    public function muteMember(int $group, int $qq, int $time = 0) {
        return $this->callBotAPI("/mute", ["target" => $group, "memberId" => $qq, "time" => $time]);
    }
    public function unmuteMember(int $group, int $qq) {
        return $this->callBotAPI("/unmute", ["target" => $group, "memberId" => $qq]);
    }
    public function kickMember(int $group, int $qq, string $msg = "") {
        return $this->callBotAPI("/kick", ["target" => $group, "memberId" => $qq, "msg" => $msg]);
    }
    public function quitGroup(int $target) {
        return $this->callBotAPI("/quit", ["target" => $target]);
    }
    public function GroupConfig(int $target, string $name, string $value = null) {
        $d = $this->_bot->callBotAPI("/groupConfig", ["target" => $target], "get");
        if ($name == null) {
            return $d;
        }
        if ($value !== null) {
            $d->$name = $value;
            return $this->_bot->callBotAPI("/groupConfig", ["target" => $target, "config" => $d]);
        }
        return $d->$name;
    }

    public function memberInfo(int $target, int $qq, string $name = null, $value = null) {
        $d = $this->_bot->callBotAPI("/memberInfo", ["target" => $target, "memberId" => $qq], "get");
        if ($name == null) {
            return $d;
        }
        if ($value !== null) {
            $d->$name = $value;
            return $this->_bot->callBotAPI("/memberInfo", ["target" => $target, "memberId" => $qq, "info" => $d]);
        }
        return $d->$name;
    }

    public function registerCommand(string $name, array $alias = [], string $desc = "", string $usage = null) {
        return $this->callBotAPI("/command/register", [
            "authKey" => $this->_authKey,
            "name" => $name,
            "alias" => $alias,
            "description" => $desc,
            "usage" => $usage,
        ]);
    }
    public function sendCommand(string $name, array $args = []) {
        return $this->callBotAPI("/command/send", [
            "authKey" => $this->_authKey,
            "name" => $name,
            "args" => $args,
        ]);
    }
    public function getManagers() {
        return $this->callBotAPI("managers?qq={$this->_qq}");
    }

    public function EventFactory($frame){
        $msgEvt = ["GroupMessage","FriendMessage","TempMessage"];
        if(in_array($frame->type,$msgEvt)){
            $frame->type .= "Event";
        }
        $evt = "\\Mirai\\".$frame->type;
        return new $evt($frame,$this);
    }
}
