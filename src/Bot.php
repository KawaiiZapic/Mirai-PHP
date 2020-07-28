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

    public function __construct(\Swoole\Coroutine\Http\Client $client, string $authKey, int $target) {
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

    private function login(): void {
        $client = &$this->_conn;
        $client->get("/about");
        $info = json_decode($client->body);
        if (!$info || $info->code != 0) {
            throw new InvaildRespondException("Invaild respond,it doesn't seem like a vaild HTTP API.");
        }
        $ret = $client->post("/auth", json_encode(["authKey" => $this->_authKey]));
        if ($ret) {
            $ret = json_decode($client->body);
            if ($ret->code != 0) {
                throw new InvaildKeyException("Invaild AuthKey.");
            } else {
                $this->_session = $ret->session;
            }
        } else {
            throw new ConnectFaliedError("Failed to connect to HTTP API.");
        }
        $ret = $client->post("/verify", json_encode(["sessionKey" => $this->_session, "qq" => $this->_qq]));
        if ($ret) {
            $ret = json_decode($client->body);
            if ($ret->code != 0) {
                throw new BindFailedException("Failed to bind seesion to QQ({$this->_qq}):{$ret->msg}.");
            }
        } else {
            throw new ConnectFaliedError("Failed to connect to HTTP API.");
        }
        $this->callBotAPI("/config", ["enableWebsocket" => true]);
    }

    /**
     * Set callback function to handle bot events.
     * Callback function will recive 2 params,first is decoded json,last is the raw content.
     *
     * @param callable $callback Function that handle callback
     *
     * @throws ConnectionCloseError Connection to API closed.
     * @throws UpgradeFailedError Failed to upgrade connection to Websocket.
     * @throws Error Some unknown error occured.
     *
     * @return void
     *
     */

    public function setEventHandler(callable $callback): void {
        $this->_conn = new \Swoole\Coroutine\Http\Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
        $ret = $this->_conn->upgrade("/all?sessionKey={$this->_session}");
        if (!$ret || $this->_conn->statusCode != 101) {
            throw new UpgradeFailedError("Failed to upgrade connection to Websocket.");
        }
        \go(function () use ($callback) {
            while (true) {
                $frame = $this->_conn->recv();
                if ($frame) {
                    $result = $this->EventFactory(json_decode($frame->data));
                } elseif ($frame === false && $this->_conn->errCode === 0) {
                    throw new ConnectionCloseError("Connection to Mirai HTTP API Closed:{$this->_conn->errMsg}.");
                } elseif ($this->_conn->errCode == 104 && $this->_conn->closeNormal === true) {
                    break;
                } else {
                    throw new \Error("Unknown error while recv from Websocket.");
                }
                call_user_func_array($callback, [$result, $frame->data]);
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
     * @throws IllegalParamException Request method may not correct
     * @throws FetchFailedError Failed to fecth response form API.
     *
     * @return any API response.
     */

    public function callBotAPI(string $path, array $params = [], string $method = "post", $raw = false) {
        $client = new \Swoole\Coroutine\Http\Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
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
            throw new IllegalParamsException("Unknown request method {$method}");
        }
        $client->close();
        if ($ret) {
            return $raw === true ? $client->body : json_decode($client->body);
        } else {
            throw new FetchFailedError("Failed to fetch API response");
        }
    }

    /**
     * Get QQ ID of this Bot instance
     *
     * @return int QQ ID
     *
     */

    public function getId(): int {
        return $this->_qq;
    }

    /**
     * Close connection to HTTP API
     *
     * @throws Exception Unknown error occured while try to colse connection to API.
     * 
     * @return void
     *
     */

    public function ShutDown(): void {
        $this->_conn->closeNormal = true;
        $this->_conn->close();
        $ret = $this->callBotAPI("/release",['qq'=>$this->_qq]);
        if(!$ret->code == 0){
            throw new \Exception("Unknown error occured while try to colse connection to API.");
        }
    }

    /**
     * Upload image to Tecent server
     *
     * @param string $type Type of image,"friend" or "group".
     * @param string $file Path to image.
     * @param string $timeout Timeout for wating API response.
     *
     * @throws FileNotFoundException Image file not found
     * @throws TimeoutException API doesn't send response before timeout
     *
     * @return mixed API Response.
     * 
     */

    public function uploadImage(string $type, string $file,float $timeout = 10) {
        if (!file_exists($file) || !is_file($file)) {
            throw new FileNotFoundException("File not found.");
        }
        $boundary = "----MiraiBoundary" . uniqid();
        $client = new \Swoole\Coroutine\Http\Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
        $client->setHeaders([
            "Content-Type" => "multipart/form-data; boundary={$boundary};",
        ]);
        $client->set(['timeout' => $timeout]);
        $client->setMethod("POST");
        $body = "--{$boundary}\r\nContent-Disposition: form-data; name=\"sessionKey\"\r\n\r\n{$this->_session}\r\n";
        $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"type\"\r\n\r\n{$type}\r\n";
        $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"img\"; filename=\"img\"\r\nContent-Type: " . getimagesize($file)['mime'] . "\r\n\r\n" . \Co::readFile($file) . "\r\n";
        $body .= "--{$boundary}--\r\n";
        $client->setData($body);
        $client->execute("/uploadImage");
        $client->close();
        if($client->statusCode == -2){
            throw new TimeoutException("API doesn't send response in {$timeout}s.");
        }
        return json_decode($client->body);
    }

    /**
     * 
     * Send image to target by URL.
     * 
     * THIS FUNCTION IS NOT RECOMMEND BY DEFAULT,USE "uploadImage" INSTEAD.
     * 
     * If $qq is not null,image will send to private chat.
     * If $gorup is not null,image will send to group chat.  
     * If both $group and $target is not null,image will send to temp chat. 
     *
     * @param string $urls Url of image.
     * @param int $qq Target friend user.
     * @param int $group Target group.
     * @param int $target Target group user.
     *
     * @return mixed API Response.
     * 
     */

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

    /**
     * 
     * Send messgae to friend
     *
     * @param int $target Target user to recive messgae.
     * @param array $chain Message chain to send.
     * @param int $quote Specify a Message Id to quote.
     *
     * @throws TargetNotFoundException Target is not found.
     * @throws MessageTooLongException Message is too long.
     * @throws Exception Unknown error occured.
     * 
     * @return int Message id.
     *
     */

    public function sendFriendMessage(int $target, array $chain, int $quote = null):int {
        $pre = [
            "target" => $target,
            "messageChain" => $chain,
        ];
        if (!is_null($quote)) {
            $pre['quote'] = $quote;
        }
        $ret = $this->callBotAPI("/sendFriendMessage", $pre);
        switch($ret->code){
            case 0:
                return $ret->messageId;
            case 5:
                throw new TargetNotFoundException("Can't send message to {$target}: {$ret->message}");
            case 30: 
                throw new MessageTooLongException("Can't send message to {$target}: {$ret->message}");
            default:
                throw new \Exception("Can't send message to {$target}:{$ret->code}: {$ret->message}");
        }
    }

    /**
     * 
     * Send message to temp user.
     *
     * @param int $qq Target user.
     * @param int $group Chat source group.
     * @param array $chain Message chain to send.
     * @param int quote Specify a Message Id to quote.
     * 
     * @throws TargetNotFoundException Target is not found.
     * @throws MessageTooLongException Message is too long.
     * @throws Exception Unknown error occured.
     *
     * @return int Message id.
     *
     */

    public function sendTempMessage(int $qq, int $group, array $chain, int $quote = null):int {
        $pre = [
            "qq" => $qq,
            "group" => $group,
            "messageChain" => $chain,
        ];
        if (!is_null($quote)) {
            $pre['quote'] = $quote;
        }
        $ret =  $this->callBotAPI("/sendTempMessage", $pre);
        switch($ret->code){
            case 0:
                return $ret->messageId;
            case 5:
                throw new TargetNotFoundException("Can't send message to {$qq} @ {$group}: {$ret->message}");
            case 30: 
                throw new MessageTooLongException("Can't send message to {$qq} @ {$group}: {$ret->message}");
            default:
                throw new \Exception("Can't send message to {$qq} @ {$group}:{$ret->code}: {$ret->message}");
        }
    }

    /**
     *
     * Send message to group.
     *
     * @param int $target Target group.
     * @param array $chain Message chain to send.
     * @param int $quote Specify a Message Id to quote.
     * 
     * @throws TargetNotFoundException Target is not found.
     * @throws MessageTooLongException Message is too long.
     * @throws BotMutedException Bot has been mute in this group.
     * @throws Exception Unknown error occured.
     *
     * @return int Message id.
     *
     */

    public function sendGroupMessage(int $target, array $chain, int $quote = null) {
        $pre = [
            "target" => $target,
            "messageChain" => $chain,
        ];
        if (!is_null($quote)) {
            $pre['quote'] = $quote;
        }
        $ret = $this->callBotAPI("/sendGroupMessage", $pre);
        switch($ret->code){
            case 0:
                return $ret->messageId;
            case 5:
                throw new TargetNotFoundException("Can't send message to {$target}: {$ret->message}");
            case 20:
                throw new BotMutedException("Can't send message to {$target}:{$ret->message}");
            case 30: 
                throw new MessageTooLongException("Can't send message to {$target}: {$ret->message}");
            default:
                throw new \Exception("Can't send message to {$target}:{$ret->code}: {$ret->message}");
        }
    }

    /**
     *
     * Recall a message.
     *
     * @param int $id ID of message which need recall.
     *
     * @throws PermissionDeniedException Bot has not permission to recall this message.
     * @throws Exception Unknown error occured.
     * 
     * @return bool Recall successfully or not.
     *
     */
    public function recallMessage(int $id):bool {
        $ret = $this->callBotAPI("/recall", ["target" => $id]);
        switch($ret->code){
            case 0:
                return true;
            case 20:
                throw new PermissionDeniedException("Can't recall message {$id}:{$ret->message}");
            default:
                throw new \Exception("Can't recall message {$id}:{$ret->code}: {$ret->message}");
        }
    }

    /**
     *
     * Get qq friend list
     *
     * @return mixed API response
     *
     */

    public function getFriendList() {
        return $this->callBotAPI("/friendList", [], "get");
    }

    /**
     * Get qq group list
     *
     * @return mixed API response
     *
     */

    public function getGroupList() {
        return $this->callBotAPI("/groupList", [], "get");
    }

    /**
     *
     * Get group member list
     *
     * @param int $target Target group
     *
     * @return mixed API response
     *
     */

    public function getMemberList(int $target) {
        return $this->callBotAPI("/groupList", ["target" => $target], "get");
    }

    /**
     * Mute hole group
     *
     * @param int $target Target group
     *
     * @return mixed API response
     *
     */

    public function muteAll(int $target) {
        return $this->callBotAPI("/muteAll", ["target" => $target]);
    }

    /**
     * Unmute hole group
     *
     * @param int $target Target group
     *
     * @return mixed API response
     */

    public function unmuteAll(int $target) {
        return $this->callBotAPI("/unmuteAll", ["target" => $target]);
    }

    /**
     *
     * Mute a group member
     *
     * @param int $group Group which target in
     * @param int $qq Target member
     * @param int $time Mute time,default 0
     *
     * @return mixed API response
     *
     */

    public function muteMember(int $group, int $qq, int $time = 0) {
        return $this->callBotAPI("/mute", ["target" => $group, "memberId" => $qq, "time" => $time]);
    }

    /**
     *
     * Unmute a group member
     *
     * @param int $group Group which target in
     * @param int $target Target member
     *
     * @return mixed API response
     *
     */

    public function unmuteMember(int $group, int $qq) {
        return $this->callBotAPI("/unmute", ["target" => $group, "memberId" => $qq]);
    }

    /**
     *
     * Kick a member from group
     *
     * @param int $group Group which target in
     * @param int $qq Target member
     * @param string $msg Kick message
     *
     * @return mixed API response
     *
     */

    public function kickMember(int $group, int $qq, string $msg = "") {
        return $this->callBotAPI("/kick", ["target" => $group, "memberId" => $qq, "msg" => $msg]);
    }

    /**
     *
     * Leave a group
     *
     * @param int $target Group to leave
     *
     * @return mixed API Response
     *
     */

    public function quitGroup(int $target) {
        return $this->callBotAPI("/quit", ["target" => $target]);
    }

    /**
     * 
     * Get/Set config of group
     * This is a jQuery-like function
     * If the thrid param is null,function will return the config vale
     * If the thrid param is not null,the value will be save to group
     * 
     * @param int $target Target group
     * @param string $name Config name
     * @param mixed $value Value to set
     * 
     * @return mixed API response
     * 
     */

    public function groupConfig(int $target, string $name, $value = null) {
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
    
    /**
     * 
     * Get/Set info of group member
     * This is a jQuery-like function
     * If the last param is null,function will return the info vale
     * If the last param is not null,the value will be save to member info
     * 
     * @param int $target Group which target member in
     * @param int $qq Target member
     * @param string $name Config name
     * @param mixed $value Value to set
     * 
     * @return mixed API response
     * 
     */

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

    /**
     * Register a command
     * 
     * @param string $name Name of command
     * @param array $alias Alias of command
     * @param string $desc Description of command
     * @param string $usage Usage of command
     * 
     * @return mixed API Response
     * 
     */

    public function registerCommand(string $name, array $alias = [], string $desc = "", string $usage = null) {
        return $this->callBotAPI("/command/register", [
            "authKey" => $this->_authKey,
            "name" => $name,
            "alias" => $alias,
            "description" => $desc,
            "usage" => $usage,
        ]);
    }

    /**
     * Send command to console
     * 
     * @param string $name Name of command
     * @param array $args Arg of command
     * 
     * @return mixed API Response
     * 
     */

    public function sendCommand(string $name, array $args = []) {
        return $this->callBotAPI("/command/send", [
            "authKey" => $this->_authKey,
            "name" => $name,
            "args" => $args,
        ]);
    }

    /**
     * Get managers of bot
     * 
     * @return mixed API Response
     * 
     */
    public function getManagers() {
        return $this->callBotAPI("managers?qq={$this->_qq}");
    }

    /**
     * 
     * Event factory
     * 
     * @param mixed $frame Data of event
     * 
     * @return mixed Event instance of event
     * 
     */
    
    public function EventFactory($frame) {
        $msgEvt = ["GroupMessage", "FriendMessage", "TempMessage"];
        if (in_array($frame->type, $msgEvt)) {
            $frame->type .= "Event";
        }
        $evt = "\\Mirai\\" . $frame->type;
        return new $evt($frame, $this);
    }

    /**
     * 
     * Listen to commands
     * 
     * @throws UpgradeFailedError Failed to upgrade connection to Websocket.
     * 
     * @return \Swoole\Coroutine\Http\Client Connection to Websocket
     * 
     */
    
    public function listenCommand():\Swoole\Coroutine\Http\Client{
        $client = new \Swoole\Coroutine\Http\Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
        $ret = $client->upgrade("/commnad?authKey={$this->_authKey}");
        if (!$ret || $this->_conn->statusCode != 101) {
            throw new UpgradeFailedError("Failed to upgrade connection to Websocket.");
        }
        return $client;
    }
    
}