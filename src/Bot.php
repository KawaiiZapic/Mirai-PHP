<?php

/**
 *
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

use Co;
use Error;
use Exception;
use Swoole\Coroutine\Http\Client;
use function go;

require_once "Events.php";
require_once "Exceptions.php";
require_once "Messages.php";
require_once "Objects.php";

class Bot {
	private $_authKey;
	private $_session;
	private $_conn;
	private $_qq;
	const msgEvt = ["GroupMessage", "FriendMessage", "TempMessage"];
	
	/**
	 *
	 * Connect to HTTP API
	 *
	 * @param Client $client An Swoole\Coroutine\Http\Client that connected to HTTP API
	 * @param string $authKey Auth key for HTTP API
	 * @param int $target QQ number of the target bot
	 *
	 * @throws Exception Exceptions when trying to login into HTTP API
	 *
	 */

	public function __construct(Client $client, string $authKey, int $target) {
		$this->_conn = $client;
		$this->_authKey = $authKey;
		$this->_qq = $target;
		$this->login();
	}
	
	/**
	 *
	 * Login to HTTP API
	 *
	 * @throws InvalidRespondException Server may not be a Mirai HTTP API
	 * @throws ConnectFailedError Failed to connect to HTTP API
	 * @throws Exception Exceptions when trying to login into HTTP API
	 *
	 * @return bool Login successfully
	 *
	 */

	private function login(): bool {
		$client = &$this->_conn;
		$client->get("/about");
		$info = json_decode($client->body);
		if (!$info || $info->code != 0) {
			throw new InvalidRespondException("Invalid respond,it doesn't seem like a valid HTTP API.");
		}
		$client->post("/auth", json_encode(["authKey" => $this->_authKey]));
		if ($client->statusCode == 200) {
			$ret = json_decode($client->body);
			if ($ret->code != 0) {
				throw self::ExceptionFactory($ret,"Failed to auth:");
			} else {
				$this->_session = $ret->session;
			}
		} else {
			throw new ConnectFailedError("Failed to connect to HTTP API.");
		}
		$client->post("/verify", json_encode(["sessionKey" => $this->_session, "qq" => $this->_qq]));
		if ($client->statusCode == 200) {
			$ret = json_decode($client->body);
			if ($ret->code != 0) {
				throw self::ExceptionFactory($ret,"Failed to bind session to QQ({$this->_qq}):");
			}
		} else {
			throw new ConnectFailedError("Failed to connect to HTTP API.");
		}
		$ret = $this->callBotAPI("/config", ["enableWebsocket" => true]);
		if ($ret) {
			$ret = json_decode($client->body);
			if ($ret->code != 0) {
				throw self::ExceptionFactory($ret,"Failed to enable websocket:");
			}
		} else {
			throw new ConnectFailedError("Failed to connect to HTTP API.");
		}
		return true;
	}

	/**
	 *
	 * Set callback function to handle bot events
	 * Callback function will receive 2 params,first is decoded json,last is the raw content
	 *
	 * @param callable $callback Function that handle callback
	 *
	 * @throws ConnectionCloseError Connection to API closed
	 * @throws UpgradeFailedError Failed to upgrade connection to Websocket
	 * @throws Error Some unknown error occurred
	 *
	 * @return int Coroutine container id
	 *
	 */

	public function setEventHandler(callable $callback): int {
		$this->_conn = new Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
		$ret = $this->_conn->upgrade("/all?sessionKey={$this->_session}");
		if (!$ret || $this->_conn->statusCode != 101) {
			throw new UpgradeFailedError("Failed to upgrade connection to Websocket.");
		}
		return go(function () use ($callback) {
			while (true) {
				$frame = $this->_conn->recv();
				if ($frame) {
					$result = $this->EventFactory(json_decode($frame->data));
				} elseif ($frame === false && $this->_conn->errCode === 0) {
					$err = socket_strerror($this->_conn->errCode);
					throw new ConnectionCloseError("Connection to Mirai HTTP API Closed:{$err}.");
				} elseif ($this->_conn->errCode == 104 && $this->_conn->closeNormal === true) {
					break;
				} else {
					throw new Error("Unknown error while recv from Websocket.");
				}
				call_user_func_array($callback, [$result, $frame->data]);
			}
		});
	}
	
	/**
	 *
	 * Get a websocket connection for message
	 *
	 * @return Client Websocket client
	 *
	 */
	
	public function getMessageWebsocket(){
		$conn = new Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
		$ret = $conn->upgrade("/message?sessionKey={$this->_session}");
		if (!$ret || $conn->statusCode != 101) {
			throw new UpgradeFailedError("Failed to upgrade connection to Websocket.");
		}
		return $conn;
	}
	
	/**
	 *
	 * Get a websocket connection for event
	 *
	 * @return Client Websocket client
	 *
	 */
	
	public function getEventWebsocket(){
		$conn = new Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
		$ret = $conn->upgrade("/event?sessionKey={$this->_session}");
		if (!$ret || $conn->statusCode != 101) {
			throw new UpgradeFailedError("Failed to upgrade connection to Websocket.");
		}
		return $conn;
	}
	
	/**
	 *
	 * Get a websocket connection for all
	 *
	 * @return Client Websocket client
	 *
	 */
	
	public function getAllWebsocket(){
		$conn = new Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
		$ret = $conn->upgrade("/all?sessionKey={$this->_session}");
		if (!$ret || $conn->statusCode != 101) {
			throw new UpgradeFailedError("Failed to upgrade connection to Websocket.");
		}
		return $conn;
	}
	
	/**
	 *
	 * Fetch message from API (Remove after fetch)
	 *
	 * @param int $count
	 *
	 * @return mixed
	 *
	 */
	
	public function fetchMessage(int $count){
		return $this->callBotAPI("/fetchMessage",["count"=>$count],"get")->data;
	}
	
	/**
	 *
	 * Fetch latest message from API (Remove after fetch)
	 *
	 * @param int $count
	 *
	 * @return mixed
	 *
	 */
	
	public function fetchLatestMessage(int $count){
		return $this->callBotAPI("/fetchAllMessage",["count"=>$count],"get")->data;
	}
	
	/**
	 *
	 * Peek message from API (Keep after peek)
	 *
	 * @param int $count
	 *
	 * @return mixed
	 *
	 */
	
	public function peekMessage(int $count){
		return $this->callBotAPI("/peekMessage",["count"=>$count],"get")->data;
	}
	
	/**
	 *
	 * Peek latest message from API (Keep after peek)
	 *
	 * @param int $count
	 *
	 * @return mixed
	 *
	 */
	
	public function peekLatestMessage(int $count){
		return $this->callBotAPI("/peekAllMessage",["count"=>$count],"get")->data;
	}
	
	/**
	 * Count message in HTTP API
	 *
	 * @return int Message count
	 *
	 */
	
	public function countMessage():int{
		return $this->callBotAPI("countMessage",[],"get")->data;
	}
	
	/**
	 *
	 * Get a message chain by id
	 * @param int $id Message id
	 *
	 * @return mixed Message chain
	 *
	 */
	
	public function messageFromId(int $id){
		return $this->callBotAPI("/messageFromId",["id"=>$id],"get")->data;
	}

	/**
	 *
	 * Send request to API
	 *
	 * @param string $path API to request
	 * @param array $params Params that pass to API
	 * @param string $method Default "post",Request method,only "post" or "get"
	 * @param bool $raw Default false,if true,will return the raw content but not the decoded content
	 *
	 * @throws IllegalParamsException Request method may not correct
	 * @throws FetchFailedError Failed to fetch response form API
	 *
	 * @return mixed API response.
	 *
	 */

	public function callBotAPI(string $path, array $params = [], string $method = "post", $raw = false) {
		$client = new Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
		if ($method == "post") {
			$params['sessionKey'] = $this->_session;
			$params = json_encode($params);
			$ret = $client->post($path, $params);
		} elseif ($method == "get") {
			$queryStr = "";
			foreach ($params as $k => $v) {
				$k = urlencode($k);
				$v = urlencode($v);
				$queryStr .= "&{$k}={$v}";
			}
			$ret = $client->get("{$path}?sessionKey={$this->_session}{$queryStr}");
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
	 *
	 * Get QQ ID of this Bot instance
	 *
	 * @return int QQ ID
	 *
	 */

	public function getId(): int {
		return $this->_qq;
	}

	/**
	 *
	 * Close connection to HTTP API
	 *
	 * @throws Exception Unknown error occurred while try to close connection to API
	 *
	 * @return bool Shutdown successfully
	 *
	 */

	public function ShutDown(): bool {
		$this->_conn->closeNormal = true;
		$this->_conn->close();
		$ret = $this->callBotAPI("/release", ['qq' => $this->_qq]);
		if (!$ret->code == 0) {
			throw new Exception("Unknown error occurred while try to close connection to API.");
		}
		return true;
	}

	/**
	 *
	 * Upload image to Tencent server
	 *
	 * @param string $type Type of image,"friend" or "group"
	 * @param string $file Path to image
	 * @param float $timeout Timeout for waiting API response
	 *
	 * @throws FileNotFoundException Image file not found
	 * @throws TimeoutException API doesn't send response before timeout
	 *
	 * @return mixed API Response
	 *
	 */

	public function uploadImage(string $type, string $file, float $timeout = 10) {
		if (!file_exists($file) || !is_file($file)) {
			throw new FileNotFoundException("File \"{$file}\" not found.");
		}
		$boundary = "----MiraiBoundary" . uniqid();
		$client = new Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
		$client->setHeaders([
			"Content-Type" => "multipart/form-data; boundary={$boundary};",
		]);
		$client->set(['timeout' => $timeout]);
		$client->setMethod("POST");
		$body = "--{$boundary}\r\nContent-Disposition: form-data; name=\"sessionKey\"\r\n\r\n{$this->_session}\r\n";
		$body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"type\"\r\n\r\n{$type}\r\n";
		$body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"img\"; filename=\"img\"\r\nContent-Type: " . getimagesize($file)['mime'] . "\r\n\r\n" . Co::readFile($file) . "\r\n";
		$body .= "--{$boundary}--\r\n";
		$client->setData($body);
		$client->execute("/uploadImage");
		$client->close();
		if ($client->statusCode == -2) {
			throw new TimeoutException("API doesn't send response in {$timeout}s.");
		}
		return json_decode($client->body);
	}
	
	/**
	 *
	 * Upload voice to Tencent server
	 *
	 * @param string $type Type of voice,"group" only
	 * @param string $file Path to voice file
	 * @param float $timeout Timeout for waiting API response
	 *
	 * @throws FileNotFoundException Voice file not found
	 * @throws TimeoutException API doesn't send response before timeout
	 *
	 * @return mixed API Response
	 *
	 */
	
	public function uploadVoice(string $type, string $file, float $timeout = 10){
		if (!file_exists($file) || !is_file($file)) {
			throw new FileNotFoundException("File \"{$file}\" not found.");
		}
		$boundary = "----MiraiBoundary" . uniqid();
		$client = new Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
		$client->setHeaders([
			"Content-Type" => "multipart/form-data; boundary={$boundary};",
		]);
		$client->set(['timeout' => $timeout]);
		$client->setMethod("POST");
		$body = "--{$boundary}\r\nContent-Disposition: form-data; name=\"sessionKey\"\r\n\r\n{$this->_session}\r\n";
		$body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"type\"\r\n\r\n{$type}\r\n";
		$body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"voice\"; filename=\"voice\" \r\n\r\n" . Co::readFile($file) . "\r\n";
		$body .= "--{$boundary}--\r\n";
		$client->setData($body);
		$client->execute("/uploadVoice");
		$client->close();
		if ($client->statusCode == -2) {
			throw new TimeoutException("API doesn't send response in {$timeout}s.");
		}
		return json_decode($client->body);
	}
	
	/**
	 *
	 * Send image to target by URL
	 *
	 * THIS FUNCTION IS NOT RECOMMEND BY DEFAULT,USE "uploadImage" INSTEAD
	 *
	 * If $qq is not null,image will send to private chat
	 * If $group is not null,image will send to group chat
	 * If both $group and $target is not null,image will send to temp chat
	 *
	 * @param string $urls Url of image
	 * @param int|null $qq Target friend user
	 * @param int|null $group Target group
	 * @param int|null $target Target group user
	 *
	 * @return mixed API Response
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
	 * Send message to friend
	 *
	 * @param int $target Target user to receive message
	 * @param array|MessageChain|string $chain Message chain to send
	 * @param int|null $quote Specify a Message Id to quote
	 *
	 * @throws IllegalParamsException MessageChain is not valid
	 * @throws TargetNotFoundException Target is not found
	 * @throws MessageTooLongException Message is too long
	 * @throws Exception Unknown error occurred
	 *
	 * @return int Message id
	 *
	 */

	public function sendFriendMessage(int $target, $chain, int $quote = null): int {
		$chain = gettype($chain) == "string" ? [new PlainMessage($chain)] : ($chain instanceof MessageChain ? (array)$chain : $chain);
		if(!is_array($chain)){
			throw new IllegalParamsException("Only accept string or array,but something else given.");
		}
		$pre = [
			"target" => $target,
			"messageChain" => $chain,
		];
		if (!is_null($quote)) {
			$pre['quote'] = $quote;
		}
		$ret = $this->callBotAPI("/sendFriendMessage", $pre);
		if($ret->code != 0){
			throw self::ExceptionFactory($ret,"Failed to send message to {$target}:");
		}
		return $ret->messageId;
	}
	
	/**
	 *
	 * Send message to temp user
	 *
	 * @param int $qq Target user
	 * @param int $group Chat source group
	 * @param array|MessageChain|string $chain Message chain to send
	 * @param int|null $quote Specify a Message Id to quote
	 *
	 * @throws IllegalParamsException MessageChain is not valid
	 * @throws MessageTooLongException Message is too long
	 * @throws TargetNotFoundException Target is not found
	 * @throws Exception Unknown error occurred
	 *
	 * @return int Message id
	 *
	 */

	public function sendTempMessage(int $qq, int $group, $chain, int $quote = null): int {
		$chain = gettype($chain) == "string" ? [new PlainMessage($chain)] : ($chain instanceof MessageChain ? (array)$chain : $chain);
		$pre = [
			"qq" => $qq,
			"group" => $group,
			"messageChain" => $chain,
		];
		if (!is_null($quote)) {
			$pre['quote'] = $quote;
		}
		$ret = $this->callBotAPI("/sendTempMessage", $pre);
		if($ret->code != 0) {
			throw self::ExceptionFactory($ret,"Failed to send message to {$qq}@{$group}:");
		}
		return $ret->messageId;
	}
	
	/**
	 *
	 * Send message to group
	 *
	 * @param int $target Target group
	 * @param array|MessageChain|string $chain Message chain to send
	 * @param int|null $quote Specify a Message Id to quote
	 *
	 * @throws IllegalParamsException MessageChain is not valid
	 * @throws TargetNotFoundException Target is not found
	 * @throws MessageTooLongException Message is too long
	 * @throws BotMutedException Bot has been mute in this group
	 * @throws Exception Unknown error occurred
	 *
	 * @return int Message id
	 *
	 */

	public function sendGroupMessage(int $target, $chain, int $quote = null): int {
		$chain = gettype($chain) == "string" ? [new PlainMessage($chain)] : ($chain instanceof MessageChain ? (array)$chain : $chain);
		$pre = [
			"target" => $target,
			"messageChain" => $chain,
		];
		if (!is_null($quote)) {
			$pre['quote'] = $quote;
		}
		$ret = $this->callBotAPI("/sendGroupMessage", $pre);
		if($ret->code != 0) {
			throw self::ExceptionFactory($ret,"Failed to send message to {$target}:");
		}
		return $ret->messageId;
	}

	/**
	 *
	 * Recall a message.
	 *
	 * @param int $id ID of message which need recall.
	 *
	 * @throws PermissionDeniedException Bot has no permission to recall this message
	 * @throws Exception Unknown error occurred
	 *
	 * @return bool Recall successfully or not
	 *
	 */

	public function recallMessage(int $id): bool {
		$ret = $this->callBotAPI("/recall", ["target" => $id]);
		if($ret->code != 0) {
			throw self::ExceptionFactory($ret,"Unable to recall message {$id}:");
		}
		return true;
	}
	
	/**
	 *
	 * Get qq friend list
	 *
	 * @return array Friend list
	 *
	 */

	public function getFriendList(): array {
		return $this->callBotAPI("/friendList", [], "get");
	}
	
	/**
	 *
	 * Get qq group list
	 *
	 * @return array Group list
	 *
	 */

	public function getGroupList(): array {
		return $this->callBotAPI("/groupList", [], "get");
	}
	
	/**
	 *
	 * Get group member list
	 *
	 * @param int $target Target group
	 *
	 * @return array Group user list
	 *
	 */

	public function getMemberList(int $target): array {
		return $this->callBotAPI("/groupList", ["target" => $target], "get");
	}

	/**
	 *
	 * Mute whole group
	 *
	 * @param int $target Target group
	 * 
	 * @throws TargetNotFoundException Target group not found
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws Exception Unknown error occurred
	 *
	 * @return bool Mute successfully or not
	 *
	 */

	public function muteAll(int $target): bool {
		$ret = $this->callBotAPI("/muteAll", ["target" => $target]);
		if($ret->code != 0){
			throw self::ExceptionFactory($ret,"Failed to mute whole group {$target}: ");
		}
		return true;
	}

	/**
	 *
	 * Unmute hole group
	 *
	 * @param int $target Target group
	 * 
	 * @throws TargetNotFoundException Target group not found
	 * @throws PermissionDeniedException Bot has no permission to do this
     *
	 * @throws Exception Unknown error occurred
	 * 
	 * @return bool Unmute successfully or not
	 *
	 */

	public function unmuteAll(int $target): bool {
		$ret = $this->callBotAPI("/unmuteAll", ["target" => $target]);
		if($ret->code != 0){
			throw self::ExceptionFactory($ret,"Failed to unmute whole group {$target}: ");
		}
		return true;
	}

	/**
	 *
	 * Mute a group member
	 *
	 * @param int $group Group which target in
	 * @param int $qq Target member
	 * @param int $time Mute time,default 0
	 *
	 * @throws TargetNotFoundException Target group not found
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws Exception Unknown error occurred
	 * 
	 * @return bool Mute successfully or not
	 *
	 */

	public function muteMember(int $group, int $qq, int $time = 0): bool {
		$ret = $this->callBotAPI("/mute", ["target" => $group, "memberId" => $qq, "time" => $time]);
		if($ret->code != 0){
			throw self::ExceptionFactory($ret,"Failed to mute {$qq}@{$group}: ");
		}
		return true;
	}

	/**
	 *
	 * Unmute a group member
	 *
	 * @param int $group Group which target in
	 * @param int $qq Target member
	 *
	 * @throws TargetNotFoundException Target group not found
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws Exception Unknown error occurred
	 * 
	 * @return bool Unmute successfully or not
	 *
	 */

	public function unmuteMember(int $group, int $qq): bool {
		$ret = $this->callBotAPI("/unmute", ["target" => $group, "memberId" => $qq]);
		if($ret->code != 0){
			throw self::ExceptionFactory($ret,"Failed to unmute {$qq}@{$group}: ");
		}
		return true;
	}

    /**
     *
     * Kick a member from group
     *
     * @param int $group Group which target in
     * @param int $qq Target member
     * @param string $msg Kick message
	 *
	 * @throws TargetNotFoundException Target group not found
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws Exception Unknown error occurred
     *
     * @return bool Kick successfully or not
     *
     */

	public function kickMember(int $group, int $qq, string $msg = ""):bool {
		$ret =  $this->callBotAPI("/kick", ["target" => $group, "memberId" => $qq, "msg" => $msg]);
		if($ret->code != 0){
			throw self::ExceptionFactory($ret,"Failed to kick {$qq}@{$group}: ");
		}
		return true;
	}

    /**
     *
     * Leave a group
     *
     * @param int $target Group to leave
     *
	 * @throws TargetNotFoundException Target group not found
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws Exception Unknown error occurred
	 *
     * @return bool Leave successfully or not
     *
     */

	public function quitGroup(int $target):bool {
		$ret = $this->callBotAPI("/quit", ["target" => $target]);
		if($ret->code != 0){
			throw self::ExceptionFactory($ret,"Failed to leave group {$target}: ");
		}
		return true;
	}
	
	/**
	 *
	 * Get/Set config of group
	 * This is a jQuery-like function
	 * If the third param is null,function will return the config vale
	 * If the third param is not null,the value will be save to group
	 *
	 * @param int $target Target group
	 * @param string $name Config name
	 * @param mixed $value Value to set
	 *
	 * @throws Exception Unknown error occurred
	 *
	 * @return mixed API response
	 *
	 */

	public function groupConfig(int $target, string $name, $value = null) {
		$d = $this->callBotAPI("/groupConfig", ["target" => $target], "get");
		if ($name == null) {
			return $d;
		}
		if ($value !== null) {
			$d->$name = $value;
			$ret = $this->callBotAPI("/groupConfig", ["target" => $target, "config" => $d]);
			if($ret->code != 0){
				throw self::ExceptionFactory($ret,"Failed to set group config of {$target}:");
			}
			return true;
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
	 * @param string|null $name Config name
	 * @param mixed $value Value to set
	 *
	 * @throws Exception Unknown error occurred
	 *
	 * @return mixed API response
	 *
	 */

	public function memberInfo(int $target, int $qq, string $name = null, $value = null) {
		$d = $this->callBotAPI("/memberInfo", ["target" => $target, "memberId" => $qq], "get");
		if ($name == null) {
			return $d;
		}
		if ($value !== null) {
			$d->$name = $value;
			$ret = $this->callBotAPI("/memberInfo", ["target" => $target, "memberId" => $qq, "info" => $d]);
			if($ret->code != 0){
				throw self::ExceptionFactory($ret,"Failed to set user config of {$qq}@{$target}:");
			}
			return true;
		}
		return $d->$name;
	}
	
	/**
	 * Register a command
	 *
	 * @param string $name Name of command
	 * @param array $alias Alias of command
	 * @param string $desc Description of command
	 * @param string|null $usage Usage of command
	 *
	 * @throws Exception Unknown error occurred
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
	 * @throws Exception Unknown error occurred
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
	 *
	 * Listen to commands
	 *
	 * @throws UpgradeFailedError Failed to upgrade connection to Websocket.
	 *
	 * @return Client Connection to Websocket
	 *
	 */
	
	public function listenCommand(): Client {
		$client = new Client($this->_conn->host, $this->_conn->port, $this->_conn->ssl);
		$ret = $client->upgrade("/command?authKey={$this->_authKey}");
		if (!$ret || $this->_conn->statusCode != 101) {
			throw new UpgradeFailedError("Failed to upgrade connection to Websocket.");
		}
		return $client;
	}
	
	/**
	 * Get managers of bot
	 *
	 * @throws Exception Unknown error occurred
	 *
	 * @return mixed API Response
	 *
	 */
	
	public function getManagers():array {
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
		$evt = "\\Mirai\\" . $frame->type . (in_array($frame->type, self::msgEvt) ? 'Event' : '');
		return new $evt($frame, $this);
	}
	
	/**
	 *
	 * Instantiate an exception from the respond code
	 *
	 * @param mixed $rsp Response object
	 * @param string $pre Prefix of exception message
	 *
	 * @return Exception Exception Instance
	 *
	 */
	
	public static function ExceptionFactory($rsp,string $pre=""):Exception{
		switch($rsp->code) {
			case 1:
				return new InvalidKeyException("{$pre}{$rsp->msg}");
			case 2:
				return new BotNotFoundException("{$pre}{$rsp->msg}");
			case 3:
				return new SessionNotExistsException("{$pre}{$rsp->msg}");
			case 4:
				return new SessionNotVerifiedException("{$pre}{$rsp->msg}");
			case 5:
				return new TargetNotFoundException("{$pre}{$rsp->msg}");
			case 10:
				return new PermissionDeniedException("{$pre}{$rsp->msg}");
			case 20:
				return new BotMutedException("{$pre}{$rsp->msg}");
			case 30:
				return new MessageTooLongException("{$pre}{$rsp->msg}");
			case 400:
				return new InvalidRequestException("{$pre}{$rsp->msg}");
			default:
				return new Exception("{$pre}:Unknown error,server return {$rsp->msg}(Code {$rsp->code})");
		}
	}
}
