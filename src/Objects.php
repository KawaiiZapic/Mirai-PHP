<?php

namespace Mirai;

use ArrayObject;
use JsonSerializable;

class MessageChain extends ArrayObject implements JsonSerializable {
	
	/**
	 * MessageChain constructor.
	 *
	 * @param array $input Message chain array
	 * @param int $flags
	 * @param string $iterator_class
	 *
	 * @throws IllegalParamsException Message chain is not valid
	 *
	 */
	
	public function __construct($input = array(), $flags = 0, $iterator_class = "ArrayIterator") {
		foreach ($input as $key => &$obj) {
			if (gettype($obj) == "string") {
				$obj = new PlainMessage($obj);
			}
			if (!self::checkMessageValid($obj)) {
				throw new IllegalParamsException("Invalid message component at {$key}");
			}
		}
		parent::__construct($input, $flags, $iterator_class);
	}
	
	/**
	 *
	 * Get id of message
	 * if this message chain hasn't got id,will return -1
	 *
	 * @return int Id of message
	 *
	 */
	
	public function getId(): int {
		return isset($this[0]) && $this->offsetGet(0)->type == "Source" ? $this->offsetGet(0)->id : -1;
	}
	
	/**
	 *
	 * @param mixed $index
	 * @param mixed $obj
	 *
	 * @throws IllegalParamsException Invalid message component
	 *
	 */
	public function offsetSet($index, $obj) {
		if (gettype($obj) == "string") {
			$obj = new PlainMessage($obj);
		}
		if (!self::checkMessageValid($obj)) {
			throw new IllegalParamsException("Invalid message component");
		}
		parent::offsetSet($index, $obj);
	}
	
	/**
	 * @param mixed $obj
	 *
	 * @throws IllegalParamsException Invalid message component
	 *
	 */
	public function append($obj) {
		if (gettype($obj) == "string") {
			$obj = new PlainMessage($obj);
		}
		if (!self::checkMessageValid($obj)) {
			throw new IllegalParamsException("Invalid message component");
		}
		parent::append($obj);
	}
	
	public function __toString(): string {
		return self::toString($this);
	}
	
	/**
	 *
	 * Turn a message chain to string
	 * this is IRREVERSIBLE
	 *
	 * @param MessageChain $chain
	 *
	 * @return string Result
	 *
	 */
	
	public static function toString(MessageChain $chain): string {
		$str = '';
		foreach ($chain as $msg) {
			switch ($msg->type) {
				case "Source":
					break;
				case "Quote":
					$str .= "[mirai:quote:{$msg->id}:{$msg->senderId}]";
					break;
				case "At":
					$str .= "[mirai:at:{$msg->target}]";
					break;
				case "AtAll":
					$str .= "[mirai:atall]";
					break;
				case "Face":
					$s = empty($msg->id) ? $msg->name : $msg->id;
					$str .= "[mirai:face:{$s}]";
					break;
				case "Plain":
					$str .= $msg->text;
					break;
				case "Image":
					$s = empty($msg->imageId) ? (empty($msg->url) ? "path:" . $msg->path : "url:" . $msg->url) : $msg->imageId;
					$str .= "[mirai:image:{$s}]";
					break;
				case "Voice":
					$s = empty($msg->voiceId) ? (empty($msg->url) ? "path:" . $msg->path : "url:" . $msg->url) : $msg->voiceId;
					$str .= "[mirai:voice:{$s}]";
					break;
				case "FlashImage":
					$s = empty($msg->imageId) ? (empty($msg->url) ? "path:" . $msg->path : "url:" . $msg->url) : $msg->imageId;
					$str .= "[mirai:flashimage:{$s}]";
					break;
				case "Xml":
					$str .= "[mirai:xml:{$msg->xml}]";
					break;
				case "Json":
					$str .= "[mirai:json:{$msg->json}]";
					break;
				case "App":
					$str .= "[mirai:app:{$msg->content}]";
					break;
				case "Poke":
					$str .= "[mirai:poke:{$msg->name}]";
					break;
			}
		}
		return $str;
	}
	
	/**
	 *
	 * Check message component is valid or not
	 *
	 * @param mixed $obj Message component
	 *
	 * @return bool Valid or not
	 *
	 */
	
	public static function checkMessageValid($obj): bool {
		switch ($obj->type) {
			case "Source":
				if (!property_exists($obj, "id") || !property_exists($obj, "time") || !is_int($obj->id) || !is_int($obj->time)) {
					return false;
				}
				
				break;
			case "Quote":
				if (!property_exists($obj, "id") || !property_exists($obj, "groupId") || !property_exists($obj, "senderId") || !property_exists($obj, "targetId") || !property_exists($obj, "origin")) {
					return false;
				}
				if (!is_int($obj->id) || !is_int($obj->groupId) || !is_int($obj->senderId) || !is_int($obj->targetId) || !is_array($obj->origin)) {
					return false;
				}
				foreach ($obj->origin as $v) {
					if (!MessageChain::checkMessageValid($v)) {
						return false;
					}
				}
				break;
			case "At":
				if (!property_exists($obj, "target") || !is_int($obj->target)) {
					return false;
				}
				
				break;
			case "AtAll":
				break;
			case "Face":
				if (!property_exists($obj, "faceId") && !property_exists($obj, "name") || !(property_exists($obj, "faceId") && is_int($obj->faceId))) {
					return false;
				}
				
				break;
			case "Plain":
				if (!property_exists($obj, "text")) {
					return false;
				}
				
				break;
			case "FlashImage":
			case "Image":
				if (!property_exists($obj, "imageId") && !property_exists($obj, "url") && !property_exists($obj, "path")) {
					return false;
				}
				
				break;
			case "Voice":
				if (!property_exists($obj, "voiceId") && !property_exists($obj, "url") && !property_exists($obj, "path")) {
					return false;
				}
				
				break;
			case "Xml":
				if (!property_exists($obj, "xml")) {
					return false;
				}
				break;
			case "Json":
				if (!property_exists($obj, "json")) {
					return false;
				}
				break;
			
			case "App":
				if (!property_exists($obj, "content")) {
					return false;
				}
				break;
			case "Poke":
				$AllowPokes = ["Poke", "ShowLove", "Like", "Heartbroken", "SixSixSix", "FangDaZhao"];
				if (!property_exists($obj, "name") || !in_array($obj->name, $AllowPokes)) {
					return false;
				}
				break;
			default:
				return false;
		}
		return true;
	}

	public function jsonSerialize() {
		return (array)$this;
	}
}

abstract class User {
	public $id;
	
	protected $_bot;
	
	/**
	 *
	 * Abstract User instance
	 * Can not do any thing
	 *
	 * @param mixed $dat User data,accept object and int(qq)
	 * @param Bot $bot Bot instance
	 *
	 */
	
	public function __construct($dat, Bot &$bot) {
		$this->_bot = $bot;
		if (is_numeric($dat)) {
			$this->id = $dat;
		} else {
			$this->id = $dat->id;
		}
		
	}
	
	/**
	 *
	 * Get user id
	 *
	 * @return int QQ user id
	 *
	 */
	
	public function getId(): int {
		return $this->id;
	}
}

class Group {
	public $id;
	public $name;
	public $permission;
	
	private $_bot;
	
	/**
	 *
	 * Group instance
	 *
	 * @param mixed $dat Group data,accept object and int
	 * @param Bot Bot instance
	 *
	 */
	
	public function __construct($dat, Bot &$bot) {
		$this->_bot = &$bot;
		if (is_numeric($dat)) {
			$this->id = $dat;
		} else {
			$this->id = $dat->id;
			$this->name = $dat->name;
			$this->permission = $dat->permission;
		}
	}
	
	
	/**
	 *
	 * Get group id
	 *
	 * @return int Group id
	 *
	 */
	
	public function getId(): int {
		return $this->id;
	}
	
	/**
	 *
	 * Get bot permission in this group
	 *
	 * @return string Bot permission
	 *
	 */
	
	public function getBotPermission(): string {
		return $this->permission;
	}
	
	/**
	 *
	 * Get group name
	 *
	 * @return string Group name
	 *
	 */
	
	public function getGroupName(): string {
		return $this->name;
	}
	
	/**
	 *
	 * Get group member list
	 *
	 * @return mixed Group user list
	 *
	 */
	
	public function getMemberList() {
		return $this->_bot->getMemberList($this->id);
	}
	
	/**
	 *
	 * Enable group hole mute
	 *
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws TargetNotFoundException
	 *
	 * @return bool Success or not
	 *
	 */
	
	public function muteAll():bool {
		return $this->_bot->muteAll($this->id);
	}
	
	/**
	 *
	 * Disable group hole mute
	 *
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws TargetNotFoundException
	 *
	 * @return bool Success or not
	 *
	 */
	
	public function unmuteAll():bool {
		return $this->_bot->unmuteAll($this->getId());
	}
	
	/**
	 *
	 * Mute someone in group
	 *
	 * @param int $target Target id
	 * @param int $time Duration of mute
	 *
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws TargetNotFoundException
	 *
	 * @return bool Success or not
	 *
	 */
	
	public function muteMember(int $target,int $time = 0):bool {
		return $this->_bot->muteMember($this->getId(), $target, $time);
	}
	
	/**
	 *
	 * Unmute someone in group
	 *
	 * @param int $target Target id
	 *
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws TargetNotFoundException
	 *
	 * @return bool Success or not
	 *
	 */
	
	public function unmuteMember(int $target):bool {
		return $this->_bot->unmuteMember($this->getId(), $target);
	}
	
	/**
	 *
	 * Kick someone in group
	 *
	 * @param int $target Target to remove
	 * @param string $msg Kick message
	 *
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws TargetNotFoundException
	 *
	 * @return bool Success or not
	 *
	 */
	
	public function kickMember(int $target, string $msg = ""):bool {
		return $this->_bot->kickMember($this->getId(), $target, $msg);
	}
	
	/**
	 *
	 * Leave this group
	 *
	 * @throws PermissionDeniedException Bot has no permission to do this
	 * @throws TargetNotFoundException
	 *
	 * @return bool Success or not
	 *
	 */
	
	public function quitGroup():bool {
		return $this->_bot->quitGroup($this->getId());
	}
	
	/**
	 *
	 * Get/Set config of group
	 * This is a jQuery-like function
	 * If the second param is null,function will return the config vale
	 * If the second param is not null,the value will be save to group
	 *
	 * @param string $name Config name
	 * @param mixed $value Value to set
	 *
	 * @return mixed API response
	 *
	 */
	public function GroupConfig(string $name, $value = null) {
		return $this->_bot->GroupConfig($this->getId(), $name, $value);
	}
	
	/**
	 *
	 * Get/Set info of group member
	 * This is a jQuery-like function
	 * If the second param is null,function will return the info vale
	 * If the second param is not null,the value will be save to member info
	 *
	 * @param int $target Target user
	 * @param string $name Config name
	 * @param mixed $value Value to set
	 *
	 * @return mixed API response
	 *
	 */
	
	public function memberInfo(int $target,string $name, $value = null) {
		return $this->_bot->memberInfo($this->id, $target, $name, $value);
	}
	
	/**
	 *
	 * Send message to group
	 *
	 * @param mixed $msg Message to send,accept MessageChain,Array and string
	 * @param int $quote Id of message  to quote
	 *
	 * @return int Message id
	 *
	 */
	
	public function sendMessage($msg, $quote = null):int {
		return $this->_bot->sendGroupMessage($this->id, $msg, $quote);
	}
	
}

class GroupUser extends User {
	
	public $group;
	public $memberName;
	public $permission;
	
	/**
	 *
	 * Group user instance
	 * Accept 2 or 3 params.
	 *
	 * @param mixed $dat Group user data or QQ id
	 * @param mixed $sec Bot instance or group id
	 * @param Bot $bot Bot instance
	 *
	 */
	
	public function __construct($dat, &$sec, &$bot = null) {
		if (is_numeric($dat)) {
			$this->group = new Group($sec,$bot);
			parent::__construct($dat,$bot);
		} else {
			$this->group = new Group($dat->group,$sec);
			$this->memberName = $dat->memberName;
			$this->permission = $dat->permission;
			parent::__construct($dat,$sec);
		}
	}
	
	/**
	 *
	 * Get group instance which sender in
	 *
	 * @return Group Group instance
	 *
	 */
	
	public function getGroup(): Group {
		return $this->group;
	}
	
	/**
	 *
	 * Kick this user
	 *
	 * @param string Kick message
	 *
	 * @return bool Success or not
	 *
	 */
	
	public function kick(string $msg = ""):bool {
		return $this->_bot->kickMember($this->group->id, $this->id, $msg);
	}
	
	/**
	 *
	 * Mute this user
	 *
	 * @param int $time Mute duration
	 *
	 * @return bool Success or not
	 *
	 */
	
	public function mute(int $time = 0):bool {
		return $this->_bot->muteMember($this->group->id, $this->id,  $time);
	}
	
	/**
	 *
	 * Unmute this user
	 *
	 * @return bool Success or not
	 *
	 */
	
	public function unmute():bool {
		return $this->_bot->unmuteMember($this->group->id, $this->id);
	}
	
	/**
	 *
	 * Get/Set info of this user
	 * This is a jQuery-like function
	 * If the second param is null,function will return the info vale
	 * If the second param is not null,the value will be save to member info
	 *
	 * @param string $name Config name
	 * @param mixed $value Value to set
	 *
	 * @return mixed API response
	 *
	 */
	
	public function Info(string $name, $value = null) {
		return $this->_bot->memberInfo($this->group->id, $this->id,$name,$value);
	}
	
	/**
	 *
	 * Send message to this user in temp chat
	 *
	 * @param mixed $msg Message to send,accept MessageChain,Array and string
	 * @param int $quote Id of message  to quote
	 *
	 * @return mixed API response
	 *
	 */
	
	public function sendMessage($msg, $quote = null) {
		return $this->_bot->sendTempMessage($this->id, $this->group->id, $msg, $quote);
	}
}

class PrivateUser extends User {
	
	public $remark;
	public $nickname;
	
	/**
	 *
	 * Private user
	 *
	 * @param mixed $dat User data,accept int and instance
	 * @param Bot Bot instance
	 *
	 */
	
	public function __construct($dat, Bot $bot) {
		if (!is_numeric($dat)) {
			$this->remark = $dat->remark;
			$this->nickname = $dat->remark;
		}
		parent::__construct($dat, $bot);
	}
	
	/**
	 *
	 * Send private chat to this user
	 *
	 * @param mixed $msg Message to send,accept MessageChain,Array and string
	 * @param int $quote Id of message to quote
	 *
	 * @throws IllegalParamsException Message chain may not valid
	 * @throws MessageTooLongException Message too long
	 * @throws TargetNotFoundException Target user not found
	 *
	 * @return mixed API response
	 */
	
	public function sendMessage($msg, int $quote = null) {
		return $this->_bot->sendFriendMessage($this->id, $msg, $quote);
	}
}