<?php

namespace Mirai;

class BaseEvent {
	
    protected $_bot;
    public $type;
    private $_prop = true;
	
	/**
	 *
	 * BaseEvent
	 *
	 * @param mixed $obj Raw message
	 * @param Bot $bot Bot instance
	 *
	 */
	
    public function __construct($obj,Bot &$bot) {
    	$this->type = $obj->type;
        $this->_bot = &$bot;
    }

    /**
     *
     * Get event type
     *
     * @return string Event type
     *
     */

    public function getType(): string {
        return $this->type;
    }

    /**
     *
     * Get bot instance
     *
     * @return Bot Bot instance
     *
     */

    public function getBot(): Bot {
        return $this->_bot;
    }

    /**
     *
     * Stop event propagation
     *
     * @return bool Stop successfully
     *
     */

    public function stopPropagation():bool {
        $this->_prop = false;
        return true;
    }

    /**
     *
     * Get event propagation status
     *
     * @return bool Propagation status
     *
     */

    public function getPropagation(): bool {
        return $this->_prop;
    }
}

abstract class MessageEvent extends BaseEvent {
	
	public $messageChain;
	public $sender;
	
	/**
	 *
	 * MessageEvent
	 *
	 * @param mixed $obj Raw message
	 * @param Bot $bot Bot instance
	 *
	 * @throws IllegalParamsException
	 *
	 */
	public function __construct($obj, Bot &$bot) {
		$this->messageChain = new MessageChain($obj->messageChain);
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Get message chain
	 *
     * @return MessageChain Message chain
     *
     */

    public function getMessageChain(): MessageChain {
        return $this->messageChain;
    }

    /**
     *
     * Get sender instance
     *
     * @return User Sender instance
     *
     */

    abstract public function getSender();

    /**
     *
     * Quick reply to this message
     *
     * @param mixed $msg Message to reply,accept String and Array
	 * @param bool $quote Quote this message to reply or not
     *
	 * @throws \Exception Unknown Error occurred
	 *
     * @return int Message id
     *
     */

    abstract public function quickReply($msg, bool $quote = false);
}
class GroupMessageEvent extends MessageEvent {
	
	public $group;
	
	/**
	 * GroupMessageEvent
	 *
	 * @param mixed $obj Raw Message
	 * @param Bot $bot Bot instance
	 *
	 * @throws IllegalParamsException
	 *
	 */
	
	public function __construct($obj, Bot &$bot) {
		$this->sender = new GroupUser($obj->sender,$bot);
		$this->group = $this->sender->getGroup();
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Get group instance
     *
     * @return Group Group instance
     *
     */

    public function getGroup(): Group {
        return $this->group;
    }
	
	/**
	 *
	 * Quick reply to this message
	 *
	 * @param array|MessageChain|string $msg Message to reply,accept String,MessageChain and Array
	 * @param bool $quote Quote this message to reply or not
	 *
	 * @throws BotMutedException Bot has been muted in this group
	 * @throws IllegalParamsException Message chain is not valid
	 * @throws MessageTooLongException Message too long
	 * @throws TargetNotFoundException Bot may not in this group anymore
	 *
	 * @return int Message id
	 *
	 */

    public function quickReply($msg, bool $quote = false):int {
		$quote = $quote == true ? $this->getMessageChain()->getId() : null;
        return $this->_bot->sendGroupMessage($this->getGroup()->getId(),$msg,$quote);
    }
	
	/**
	 *
	 * Recall this message
	 *
	 * @throws PermissionDeniedException Bot has no permission to recall this message
	 *
	 * @return bool Recall success or not
	 *
	 */

    public function recallMessage():bool {
        return $this->_bot->recallMessage($this->getMessageChain()->getId());
    }

    /**
     *
     * Kick message sender
     *
	 * @param string $msg Kick message
	 *
	 * @throws TargetNotFoundException User may already leave group
	 * @throws PermissionDeniedException Bot has no permission to do this
	 *
     * @return bool Kick successfully or not
     *
     */

    public function kickMember(string $msg = ""): bool {
        return $this->_bot->kickMember($this->getGroup()->getId(),$this->getSender()->getId(), $msg);
    }

    /**
     *
     * Get sender instance
     *
     * @return GroupUser User instance
     *
     */

    public function getSender(): GroupUser {
        return $this->sender;
    }
}
class FriendMessageEvent extends MessageEvent {
	
	/**
	 * FriendMessageEvent
	 *
	 * @param mixed $obj Raw Message
	 * @param Bot $bot Bot instance
	 *
	 * @throws IllegalParamsException
	 *
	 */
	
	public function __construct($obj, Bot &$bot) {
		$this->sender = new PrivateUser($obj->sender,$bot);
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Quick reply to this message
     *
     * @param string|MessageChain|array $msg Message to reply,accept String,MessageChain and Array.
     * @param bool $quote Should quote this message to reply or not
	 *
	 * @throws IllegalParamsException Message chain is not valid
	 * @throws MessageTooLongException Message too long
	 * @throws TargetNotFoundException Bot may not in this group anymore
     *
     * @return mixed API response.
     *
     */

    public function quickReply($msg, bool $quote = null) {
    	$quote = $quote == true ? $this->getMessageChain()->getId() : null;
        return $this->_bot->sendFriendMessage($this->sender->getId(),$msg ,$quote);
    }

    /**
     *
     * Get sender instance
     *
     * @return PrivateUser Sender instance
     *
     */

    public function getSender(): PrivateUser {
        return $this->sender;
    }
}
class TempMessageEvent extends MessageEvent {

	public $group;
	
	public function __construct($obj, Bot &$bot) {
		$this->sender = new PrivateUser($obj->sender,$bot);
		$this->group = new Group($obj->sender->group,$bot);
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Get group instance
     *
     * @return mixed Group instance
     *
     */

    public function getGroup(): Group {
        return $this->group;
    }

    /**
     *
     * Quick reply to this message
     *
     * @param array|string $msg Message to reply,accept String,MessageChain and Array.
     * @param bool $quote Should quote this message to reply or not
     *
	 * @throws IllegalParamsException Message chain is not valid
	 * @throws MessageTooLongException Message too long
	 * @throws TargetNotFoundException Bot may not in this group anymore
	 *
     * @return mixed API response.
     *
     */

    public function quickReply($msg, bool $quote = false) {
        $quote = $quote == true ? $this->getMessageChain()->getId(): -1;
        return $this->_bot->sendTempMessage($this->sender->getId(),$this->group->getId(),$msg,$quote);
    }
    public function getSender(): PrivateUser {
        return $this->sender;
    }
}
abstract class BotSelfEvent extends BaseEvent {

	public $qq;
	
	public function __construct($obj, Bot &$bot) {
		$this->qq = $obj->qq;
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Get bot qq number
     *
     * @return int QQ number of bot
     *
     */

    public function getId(): int {
        return $this->qq;
    }
}
class BotOnlineEvent extends BotSelfEvent {}
abstract class BotOfflineEvent extends BotSelfEvent {}
class BotOfflineEventActive extends BotOfflineEvent {}
class BotOfflineEventForce extends BotOfflineEvent {}
class BotOfflineEventDropped extends BotOfflineEvent {}
class BotReloginEvent extends BotSelfEvent {}
abstract class BotGroupEvent extends BaseEvent {
	
	public $group;
	
	public function __construct($obj, Bot &$bot) {
		parent::__construct($obj, $bot);
	}
	
	/**
	 *
	 * Get group instance which bot has been muted
	 *
	 * @return Group Group instance
	 *
	 */
	
	public function getGroup():Group {
		return $this->group;
	}
}
class BotMuteEvent extends BotGroupEvent {

	public $durationSeconds;
	public $operator;
	
	public function __construct($obj, Bot &$bot) {
		$this->durationSeconds = $obj->durationSeconds;
		$this->operator = new GroupUser($obj->operator,$bot);
		$this->group = new Group($obj->operator->group,$bot);
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Get mute duration.
     *
     * @return int Duration in second.
     *
     */

    public function getDuration(): int {
        return $this->durationSeconds;
    }

    /**
     *
     * Get mute operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getOperator(): GroupUser {
        return $this->operator;
    }

    
}
class BotUnmuteEvent extends BotGroupEvent {
	
	public $operator;
	
	public function __construct($obj, Bot &$bot) {
		$this->operator = new GroupUser($obj->operator,$bot);
		$this->group = new Group($obj->operator->group,$bot);
		parent::__construct($obj, $bot);
	}
	
	/**
	 *
	 * Get mute operator instance
	 *
	 * @return GroupUser Operator instance
	 *
	 */
	
	public function getOperator(): GroupUser {
		return $this->operator;
	}
}
class BotGroupPermissionChangeEvent extends BotGroupEvent {
	
	public $origin;
	public $current;
	public $group;
	
	public function __construct($obj, Bot &$bot) {
		$this->group = new Group($obj->group,$bot);
		$this->origin = $obj->origin;
		$this->current = $obj->current;
		parent::__construct($obj, $bot);
	}
	
	/**
	 *
	 * Get current permission
	 *
	 * @return string Current permission
	 *
	 */
	
    public function getOrigin(): string {
        return $this->origin;
    }
	
	
	/**
	 *
	 * Get origin permission
	 *
	 * @return string Origin permission
	 *
	 */

    public function getCurrent(): string {
        return $this->current;
    }
}
class BotJoinGroupEvent extends BotGroupEvent {

	public function __construct($obj, Bot &$bot) {
		$this->group = new Group($obj->group,$bot);
		parent::__construct($obj, $bot);
	}
	
}
abstract class BotLeaveEvent extends BotGroupEvent {
	public function __construct($obj, Bot &$bot) {
		$this->group = new Group($obj->group,$bot);
		parent::__construct($obj, $bot);
	}
}
class BotLeaveEventActive extends BotLeaveEvent {}
class BotLeaveEventKick extends BotLeaveEvent {}
abstract class RecallEvent extends BaseEvent {

	public $messageId;
	public $authorId;
	public $time;
	
	protected $_author;
	
    /**
     *
     * Get author of recalled message
     *
     * @return User User instance
     *
     */

    public function getAuthorId():int {
    	return $this->authorId;
	}

    /**
     *
     * Get id of message which is recalled
     *
     * @return int Id of message
     *
     */

    public function getId(): int {
        return $this->messageId;
    }

    /**
     *
     * Get recall time
     *
     * @return int Time in second
     */

    public function getTime(): int {
        return $this->time;
    }

    /**
     *
     * Get recall operator instance
     *
     * @return User Operator instance
     *
     */

    abstract public function getOperator();
    abstract public function getAuthor();
}
class GroupRecallEvent extends RecallEvent {
	
	public $group;
	public $operator;
	
	public function __construct($obj, Bot &$bot) {
		$this->group = new Group($obj->group, $bot);
		$this->_author = new GroupUser($obj->authorId,$obj->group->id,$bot);
		$this->operator = new GroupUser($obj->operator, $bot);
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Get recall operator instance
     *
     * @return GroupUser Operator instance
     *
     */
    
	public function getOperator(): GroupUser {
        return $this->operator;
    }
    
    public function getAuthor():GroupUser {
		return $this->_author;
	}
}
class FriendRecallEvent extends RecallEvent {
	
	public $operator;
	private $_operator;
	
	public function __construct($obj, Bot &$bot) {
		$this->_author = new PrivateUser($obj->authorId,$bot);
		$this->_operator = new PrivateUser($obj->operator,$bot);
		$this->operator = $obj->operator;
		parent::__construct($obj, $bot);
	}
	
    /**
     *
     * Get recall operator instance
     *
     * @return PrivateUser Operator instance
     *
     */

    public function getOperator(): PrivateUser {
        return $this->_operator;
    }
    
    public function getAuthor() {
		return $this->_author;
	}
}
abstract class GroupEvent extends BaseEvent {
	public function __construct($obj, Bot &$bot) {
		parent::__construct($obj, $bot);
	}
}
abstract class GroupChangeEvent extends GroupEvent {

	public $origin;
	public $current;
	public $group;
	public $operator;
	
	public function __construct($obj, Bot &$bot) {
		$this->origin = $obj->origin;
		$this->current = $obj->current;
		$this->group = new Group($obj->group,$bot);
		$this->operator = new GroupUser($obj->operator,$bot);
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Get origin value
     *
     * @return string Origin value
     *
     */

    public function getOrigin(): string {
        return $this->origin;
    }

    /**
     *
     * Get current value
     *
     * @return string Current value
     *
     */

    public function getCurrent(): string {
        return $this->current;
    }

    /**
     *
     * Get target group instance
     *
     * @return Group Target group instance
     *
     */

    public function getGroup(): Group {
        return $this->group;
    }

    /**
     *
     * Get operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getOperator(): GroupUser {
        return $this->operator;
    }
}
class GroupNameChangeEvent extends GroupChangeEvent {}
class GroupEntranceAnnouncementChangeEvent extends GroupChangeEvent {}
class GroupMuteAllEvent extends GroupChangeEvent {}
class GroupAllowAnonymousChatEvent extends GroupChangeEvent {}
class GroupAllowConfessTalkEvent extends GroupChangeEvent {
	public $isByBot;
	
	public function __construct($obj, Bot &$bot) {
		$this->isByBot = $obj->isByBot;
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * DO NOT CALL THIS FUNCTION
     * USE "isByBot" INSTEAD
	 *
	 * @throws IllegalOperateException THIS FUNCTION SHOULD NOT BE CALLED
     *
     */

    public function getOperator(): GroupUser {
        throw new IllegalOperateException("Illegal Called function getOperator,call \"isByBot\" instead.");
        return new GroupUser(null,$this);
    }

    /**
     *
     * Check operation is by bot
     *
     * @return bool Is by bot or not
     *
     */

    public function isByBot(): bool {
        return $this->isByBot;
    }
}
class GroupAllowMemberInviteEvent extends GroupChangeEvent {}
class MemberJoinEvent extends GroupEvent {
	
	public $member;
	
	public function __construct($obj, Bot &$bot) {
		$this->member = new GroupUser($obj->member,$bot);
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Get joined user instance
     *
     * @return GroupUser User instance
     *
     */

    public function getMember(): GroupUser {
        return $this->member;
    }
}
abstract class MemberLeaveEvent extends GroupEvent {

	public $member;
	
	public function __construct($obj, Bot &$bot) {
		$this->member = new GroupUser($obj->member,$bot);
		parent::__construct($obj, $bot);
	}
	
    /**
     *
     * Get left user instance
     *
     * @return GroupUser User instance
     *
     */

    public function getMember(): GroupUser {
        return $this->member;
    }
}
class MemberLeaveEventKick extends MemberLeaveEvent {
	public $operator;
	public function __construct($obj, Bot &$bot) {
		$this->operator = new GroupUser($obj->operator,$bot);
		parent::__construct($obj, $bot);
	}
	public function getOperator(): GroupUser{
		return $this->operator;
	}
}
class MemberLeaveEventQuit extends MemberLeaveEvent {}
class GroupMemberChangeEvent extends GroupEvent {
	public $origin;
	public $current;
	public $member;
	
	public function __construct($obj, Bot &$bot) {
		$this->origin = $obj->origin;
		$this->current = $obj->current;
		$this->member = new GroupUser($obj->member,$bot);
		parent::__construct($obj, $bot);
	}
}
class MemberCardChangeEvent extends GroupMemberChangeEvent {
	public $operator;
	public function __construct($obj, Bot &$bot) {
		$this->operator = new GroupUser($obj->operator,$bot);
		parent::__construct($obj, $bot);
	}
}
class MemberSpecialTitleChangeEvent extends GroupMemberChangeEvent {}
class MemberPermissionChangeEvent extends GroupMemberChangeEvent {}
class MemberMuteEvent extends GroupEvent {

	public $durationSeconds;
	public $member;
	public $operator;
	
	public function __construct($obj, Bot &$bot) {
		$this->durationSeconds = $obj->durationSeconds;
		$this->member = new GroupUser($obj->group,$bot);
		$this->operator = new GroupUser($obj->operator,$bot);
		parent::__construct($obj, $bot);
	}
	
	/**
     *
     * Get mute duration.
     *
     * @return int Duration in second.
     *
     */

    public function getDuration(): int {
        return $this->durationSeconds;
    }

    /**
     *
     * Get mute operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getOperator(): GroupUser {
        return $this->operator;
    }

    /**
     *
     * Get group instance which user has been muted
     *
     * @return Group Group instance
     *
     */

    public function getGroup(): Group {
        return $this->operator->group;
    }

    /**
     *
     * Get mute operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getMember(): GroupUser {
        return $this->member;
    }
}
class MemberUnmuteEvent extends GroupEvent {
 
	public $operator;
	public $member;
	
	public function __construct($obj, Bot &$bot) {
		$this->operator = new GroupUser($obj->operator,$bot);
		$this->member = new GroupUser($obj->member,$bot);
		parent::__construct($obj, $bot);
	}
	
	/**
    *
    * Get group instance which user has been muted
    *
    * @return Group Group instance
    *
    */

    public function getGroup(): Group {
        return $this->operator->group;
    }

    /**
     *
     * Get mute operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getMember(): GroupUser {
        return $this->member;
    }
}
abstract class RequestEvent extends BaseEvent {

	public $eventId;
	public $fromId;
	public $groupId;
	public $nick;
	public $message;
	
	public function __construct($obj, Bot &$bot) {
		$this->eventId = $obj->eventId;
		$this->fromId = $obj->fromId;
		$this->groupId = $obj->groupId;
		$this->nick = $obj->nick;
		$this->message = $obj->message;
		parent::__construct($obj, $bot);
	}
	
	/**
     * 
     * Get event id
     * 
     * @return int Event id
     * 
     */
    
    public function getEventId(): int {
        return $this->eventId;
    }

    /**
     * 
     * Get request sender id
     * 
     * @return int Sender id
     * 
     */

    public function getFromId(): int {
        return $this->fromId;
    }

    /**
     * 
     * Get group instance which request from.
     * 
     * @return Group Group instance
     * 
     */

    public function getGroupId(): int {
        return $this->groupId;
    }

    /**
     * 
     * Get nickname who send request
     * 
     * @return string Nickname string
     * 
     */

    public function getNick(): string {
        return $this->nick;
    }

    /**
     * 
     * Get request message
     * 
     * @return mixed Message string
     * 
     */

    public function getMessage(): string {
        return $this->message;
    }

    /**
     * 
     * Reply to request with code.
     * 
     * @param int $code Reply code
     * @param string $msg Message to reply
     * 
     * @return mixed API Response
     * 
     */

    abstract public function ResponseCode(int $code, string $msg = "");
}
class NewFriendRequestEvent extends RequestEvent {

    /**
     * 
     * Approve new friend request
     * 
     * @return mixed API response.
     */
    public function Approve() {
        return $this->ResponseCode(0);
    }

    /**
     * 
     * Deny the request
     * 
     * @param string $msg Message that reply to request
     * 
     * @return mixed API response
     * 
     */

    public function Deny(string $msg = "") {
        return $this->ResponseCode(1, $msg);
    }

    /**
     * 
     * Deny & block the request.
     * Bot will not receive request from this sender any more
     * 
     * @param string $msg Reply to the request
     * 
     * @return mixed API response
     * 
     */

    public function DenyBlock(string $msg = "") {
        return $this->ResponseCode(2, $msg);
    }
    
    /**
     * 
     * Reply to request with code.
     * 
     * @param int $code Reply code
     * @param string $msg Message to reply
     * 
     * @return mixed API Response
     * 
     */

    public function ResponseCode(int $code, string $msg = "") {
        return $this->_bot->callBotAPI("/resp/newFriendRequestEvent", [
            "eventId" => $this->eventId,
            "fromId" => $this->fromId,
            "groupId" => $this->groupId,
            "operate" => $code,
            "message" => $msg,
        ]);

    }
}
class MemberJoinRequestEvent extends RequestEvent {

	public $groupName;
	
	public function __construct($obj, Bot &$bot) {
		$this->groupName = $obj->groupName;
		parent::__construct($obj, $bot);
	}
	
	/**
     * 
     * Get group name which request from
     * 
     * @return string Group name
     * 
     */

    public function getGroupName(): string {
        return $this->groupName;
    }

    /**
     * 
     * Approve the join request
     * 
     * @return mixed API response
     * 
     */

    public function Approve() {
        return $this->ResponseCode(0);
    }

    /**
     * 
     * Deny the request
     * 
     * @param string Reply to message
     * 
     * 
     * @return mixed API response
     * 
     */

    public function Deny(string $msg = "") {
        return $this->ResponseCode(1, $msg);
    }

    /**
     * 
     * Deny & block the request.
     * Bot will not receive request from this sender any more
     * 
     * @param string $msg Reply to the request
     * 
     * @return mixed API response
     * 
     */

    public function DenyBlock($msg = "") {
        return $this->ResponseCode(3, $msg);
    }

    /**
     * 
     * Ignore request
     * 
     * @return mixed API response
     * 
     */

    public function Ignore() {
        return $this->ResponseCode(2);
    }

    /**
     * 
     * Ignore & block the request
     * Bot will not receive request from this sender any more
     * 
     * @return mixed API response
     * 
     */

    public function IgnoreBlock() {
        return $this->ResponseCode(4);
    }

    /**
     * 
     * Reply to request with code.
     * 
     * @param int $code Reply code
     * @param string $msg Message to reply
     * 
     * @return mixed API Response
     * 
     */
    
    public function ResponseCode(int $code, string $msg = "") {
        return $this->_bot->callBotAPI("/resp/memberJoinRequestEvent", [
            "eventId" => $this->eventId,
            "fromId" => $this->fromId,
            "groupId" => $this->groupId,
            "operate" => $code,
            "message" => $msg,
        ]);
    }
}
class BotInvitedJoinGroupRequestEvent extends RequestEvent {

    /**
     * 
     * Approve the request
     * 
     * @return mixed API response
     * 
     */

    public function Approve() {
        return $this->ResponseCode(0);
    }

    /**
     * 
     * Deny the request
     * 
     * @param mixed API response
     * 
     */

    public function Deny() {
        return $this->ResponseCode(1);
    }

    /**
     * 
     * Reply to request with code.
     * 
     * @param int $code Reply code
     * @param string $msg Message to reply
     * 
     * @return mixed API Response
     * 
     */

    public function ResponseCode(int $code, string $msg = "") {
        return $this->_bot->callBotAPI("/resp/memberJoinRequestEvent", [
            "eventId" => $this->eventId,
            "fromId" => $this->fromId,
            "groupId" => $this->groupId,
            "operate" => $code,
            "message" => $msg,
        ]);
    }
}