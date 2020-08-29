<?php

namespace Mirai;

use Exception;

/**
 * Class BaseEvent
 *
 * @package Mirai
 * @property Bot $_bot
 */
class BaseEvent {
    protected $_raw;
    protected $_bot;
    private $_prop = true;
    public final function __construct($obj,Bot &$bot) {
        $this->_raw = $obj;
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
        return $this->_raw->type;
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

    /**
     *
     * Get message chain
     *
     * @return MessageChain Message chain
     *
     */

    public function getMessageChain(): MessageChain {
        return new MessageChain($this->_raw->messageChain);
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
	 * @throws Exception Unknown Error occurred
	 *
     * @return int Message id
     *
     */

    abstract public function quickReply($msg, bool $quote = false);
}
class GroupMessageEvent extends MessageEvent {

    /**
     *
     * Get group instance
     *
     * @return Group Group instance
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->sender->group, $this->_bot);
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
        return new GroupUser($this->_raw->sender, $this->_bot);
    }
}
class FriendMessageEvent extends MessageEvent {

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
        return $this->_bot->sendFriendMessage($this->getSender()->getId(),$msg ,$quote);
    }

    /**
     *
     * Get sender instance
     *
     * @return PrivateUser Sender instance
     *
     */

    public function getSender(): PrivateUser {
        return new PrivateUser($this->_raw->sender, $this->_bot);
    }
}
class TempMessageEvent extends MessageEvent {

    /**
     *
     * Get group instance
     *
     * @return mixed Group instance
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->sender->group, $this->_bot);
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
        return $this->_bot->sendTempMessage($this->getSender()->getId(),$this->getGroup()->getId(),$msg,$quote);
    }
    public function getSender(): PrivateUser {
        return new PrivateUser($this->_raw->sender, $this->_bot);
    }
}

abstract class BotEvent extends BaseEvent {

    /**
     *
     * Get bot qq number
     *
     * @return int QQ number of bot
     *
     */

    public function getId(): int {
        return $this->_raw->qq;
    }
}

class BotOnlineEvent extends BotEvent {}
abstract class BotOfflineEvent extends BotEvent {}
class BotOfflineEventActive extends BotOfflineEvent {}
class BotOfflineEventForce extends BotOfflineEvent {}
class BotOfflineEventDropped extends BotOfflineEvent {}
class BotReloginEvent extends BotEvent {}
class BotMuteEvent extends BotEvent {

    /**
     *
     * Get mute duration.
     *
     * @return int Duration in second.
     *
     */

    public function getDuration(): int {
        return $this->_raw->durationSeconds;
    }

    /**
     *
     * Get mute operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }

    /**
     *
     * Get group instance which bot has been muted
     *
     * @return Group Group instance
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->operator->group, $this->_bot);
    }

    /**
     *
     * Get mute operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getMember(): GroupUser {
        return new GroupUser($this->_bot->getId(), $this->_raw->group->operator->id, $this->_bot);
    }
}
class BotUnmuteEvent extends BotEvent {

    /**
	 *
	 * Get mute operator instance
	 *
	 * @return GroupUser Operator instance
 	 *
 	 */

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }
	
	/**
	 *
	 * Get group instance which bot has been muted
	 *
	 * @return Group Group instance
	 *
	 */

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
class BotGroupPermissionChangeEvent extends BotEvent {

	/**
	 *
	 * Get current permission
	 *
	 * @return string Current permission
	 *
	 */
	
    public function getOrigin(): string {
        return $this->_raw->origin;
    }
	
	
	/**
	 *
	 * Get origin permission
	 *
	 * @return string Origin permission
	 *
	 */

    public function getCurrent(): string {
        return $this->_raw->current;
    }

    /**
     *
     * Get group which bot permission has been changed
     *
     * @return Group Group instance
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
class BotJoinGroupEvent extends BotEvent {

    /**
     *
     * Get group instance which bot joined
     *
     * @return Group Group instance
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
abstract class BotLeaveEvent extends BotEvent {

    /**
     *
     * Get group instance which bot (has been) left
     *
     * @return Group Group instance
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
class BotLeaveEventActive extends BotLeaveEvent {}
class BotLeaveEventKick extends BotLeaveEvent {}
abstract class RecallEvent extends BaseEvent {

    /**
     *
     * Get author of recalled message
     *
     * @return User User instance
     *
     */

    public function getAuthor() {
        return new User($this->_raw->authorId, $this->_bot);
    }

    /**
     *
     * Get id of message which is recalled
     *
     * @return int Id of message
     *
     */

    public function getId(): int {
        return $this->_raw->messageId;
    }

    /**
     *
     * Get recall time
     *
     * @return int Time in second
     */

    public function getTime(): int {
        return $this->_raw->time;
    }

    /**
     *
     * Get recall operator instance
     *
     * @return User Operator instance
     *
     */

    abstract public function getOperator();
}
class GroupRecallEvent extends RecallEvent {

    /**
     *
     * Get recall operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }
}
class FriendRecallEvent extends RecallEvent {

    /**
     *
     * Get recall operator instance
     *
     * @return PrivateUser Operator instance
     *
     */

    public function getOperator(): PrivateUser {
        return new PrivateUser($this->_raw->operator, $this->_bot);
    }
}
abstract class GroupEvent extends BaseEvent {}
abstract class GroupChangeEvent extends GroupEvent {

    /**
     *
     * Get origin value
     *
     * @return string Origin value
     *
     */

    public function getOrigin(): string {
        return $this->_raw->origin;
    }

    /**
     *
     * Get current value
     *
     * @return string Current value
     *
     */

    public function getCurrent(): string {
        return $this->_raw->current;
    }

    /**
     *
     * Get target group instance
     *
     * @return Group Target group instance
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }

    /**
     *
     * Get operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }
}
class GroupNameChangeEvent extends GroupChangeEvent {}
class GroupEntranceAnnouncementChangeEvent extends GroupChangeEvent {}
class GroupMuteAllEvent extends GroupChangeEvent {}
class GroupAllowAnonymousChatEvent extends GroupChangeEvent {}
class GroupAllowConfessTalkEvent extends GroupChangeEvent {

    /**
     *
     * DO NOT CALL THIS FUNCTION
     * USE "isByBot" INSTEAD
     *
     */

    public function getOperator(): GroupUser {
        throw new Exception("Illegal Called function getOperator,call \"isByBot\" instead.");
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
        return $this->_raw->isByBot;
    }
}
class GroupAllowMemberInviteEvent extends GroupChangeEvent {}
class MemberJoinEvent extends GroupEvent {

    /**
     *
     * Get joined user instance
     *
     * @return GroupUser User instance
     *
     */

    public function getMember(): GroupUser {
        return new GroupUser($this->_raw->member, $this->_bot);
    }
}
abstract class MemberLeaveEvent extends GroupEvent {

    /**
     *
     * Get left user instance
     *
     * @return GroupUser User instance
     *
     */

    public function getMember(): GroupUser {
        return new GroupUser($this->_raw->member, $this->_bot);
    }
}
class MemberLeaveEventKick extends MemberLeaveEvent {}
class MemberLeaveEventQuit extends MemberLeaveEvent {}
class GroupMemberChangeEvent extends GroupEvent {}
class MemberCardChangeEvent extends GroupMemberChangeEvent {}
class MemberSpecialTitleChangeEvent extends GroupMemberChangeEvent {}
class MemberPermissionChangeEvent extends GroupMemberChangeEvent {}
class MemberMuteEvent extends GroupEvent {

    /**
     *
     * Get mute duration.
     *
     * @return int Duration in second.
     *
     */

    public function getDuration(): int {
        return $this->_raw->durationSeconds;
    }

    /**
     *
     * Get mute operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }

    /**
     *
     * Get group instance which user has been muted
     *
     * @return Group Group instance
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->operator->group, $this->_bot);
    }

    /**
     *
     * Get mute operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getMember(): GroupUser {
        return new GroupUser($this->_raw->member, $this->_bot);
    }
}
class MemberUnmuteEvent extends GroupEvent {
    
    /** 
    *
    * Get group instance which user has been muted
    *
    * @return Group Group instance
    *
    */

    public function getGroup(): Group {
        return new Group($this->_raw->operator->group, $this->_bot);
    }

    /**
     *
     * Get mute operator instance
     *
     * @return GroupUser Operator instance
     *
     */

    public function getMember(): GroupUser {
        return new GroupUser($this->_raw->member, $this->_bot);
    }
}
abstract class RequestEvent extends BaseEvent {

    /**
     * 
     * Get event id
     * 
     * @return int Event id
     * 
     */
    
    public function getEventId(): int {
        return $this->_raw->eventId;
    }

    /**
     * 
     * Get request sender id
     * 
     * @return int Sender id
     * 
     */

    public function getFromId(): int {
        return $this->_raw->fromId;
    }

    /**
     * 
     * Get group instance which request from.
     * 
     * @return Group Group instance
     * 
     */

    public function getGroup(): Group {
        return new Group($this->_raw->groupId, $this->_raw);
    }

    /**
     * 
     * Get nickname who send request
     * 
     * @return string Nickname string
     * 
     */

    public function getNick(): string {
        return $this->_raw->nick;
    }

    /**
     * 
     * Get request message
     * 
     * @return mixed Message string
     * 
     */

    public function getMessage(): string {
        return $this->_raw->message;
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
            "eventId" => $this->_raw->eventId,
            "fromId" => $this->_raw->fromId,
            "groupId" => $this->_raw->groupId,
            "operate" => $code,
            "message" => $msg,
        ]);

    }
}
class MemberJoinRequestEvent extends RequestEvent {

    /**
     * 
     * Get group name which request from
     * 
     * @return string Group name
     * 
     */

    public function getGroupName(): string {
        return $this->_raw->groupName;
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
            "eventId" => $this->_raw->eventId,
            "fromId" => $this->_raw->fromId,
            "groupId" => $this->_raw->groupId,
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
            "eventId" => $this->_raw->eventId,
            "fromId" => $this->_raw->fromId,
            "groupId" => $this->_raw->groupId,
            "operate" => $code,
            "message" => $msg,
        ]);
    }
}
