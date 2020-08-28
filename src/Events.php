<?php

namespace Mirai;

use Exception;

class BaseEvent {
    protected $_raw;
    protected $_bot;
    private $_prop = true;
    public final function __construct($obj, &$bot) {
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
     * @return void
     *
     */

    public function stopPropagation(): void {
        $this->_prop = false;
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
     * Get sender object
     *
     * @return \Mirai\User Sender object
     *
     */

    abstract public function getSender();

    /**
     *
     * Quick reply to this message
     *
     * @param mixed $msg Message to reply,accept String,MessageChain and Array.
     *
     * @return mixed API response.
     *
     */

    abstract public function quickReply($msg, bool $quote = false);
}
class GroupMessageEvent extends MessageEvent {

    /**
     *
     * Get group object.
     *
     * @return Group Group object.
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->sender->group, $this->_bot);
    }

    /**
     *
     * Quick reply to this message
     *
     * @param mixed $msg Message to reply,accept String,MessageChain and Array.
     *
     * @return mixed API response.
     *
     */

    public function quickReply($msg, bool $quote = false) {
        if (gettype($msg) == "string") {
            $msg = new MessageChain([$msg]);
        }
        $msg = $msg instanceof MessageChain ? $msg->toArray() : $msg;
        $pre = [
            "target" => $this->getGroup()->getId(),
            "messageChain" => $msg,
        ];
        if ($quote) {
            $pre['quote'] = $this->getMessageChain()->getId();
        }
        return $this->_bot->callBotAPI("/sendGroupMessage", $pre);
    }

    /**
     *  Recall this message
     *
     * @return mixed API Response
     *
     */

    public function recallMessage() {
        return $this->_bot->callBotAPI("/recall", ["target" => $this->getMessageChain()->getId()]);
    }

    /**
     *
     * Kick message sender
     *
     * @return mixed API response
     *
     */

    public function kickMember($msg = "") {
        return $this->_bot->callBotAPI("/kick", ["target" => $this->getGroup()->getId(), "memberId" => $this->getSender()->getId(), "msg" => $msg]);
    }

    /**
     *
     * Get sender object
     *
     * @return mixed API response
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
     * @param mixed $msg Message to reply,accept String,MessageChain and Array.
     * @param bool $quote Should quote this message to reply or not
     *
     * @return mixed API response.
     *
     */

    public function quickReply($msg, bool $quote = false) {
        $pre = [
            "target" => $this->getSender()->getId(),
            "messageChain" => $msg->toArray(),
        ];
        if ($quote) {
            $pre['quote'] = $this->getMessageChain()->getId();
        }
        return $this->_bot->callBotAPI("/sendFriendMessage", $pre);
    }

    /**
     *
     * Get sender object
     *
     * @return PrivateUser Sender object
     *
     */

    public function getSender(): PrivateUser {
        return new PrivateUser($this->_raw->sender, $this->_bot);
    }
}
class TempMessageEvent extends MessageEvent {

    /**
     *
     * Get group object
     *
     * @return mixed Group object
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->sender->group, $this->_bot);
    }

    /**
     *
     * Quick reply to this message
     *
     * @param mixed $msg Message to reply,accept String,MessageChain and Array.
     * @param bool $quote Should quote this message to reply or not
     *
     * @return mixed API response.
     *
     */

    public function quickReply($msg, bool $quote = false) {
        $pre = [
            "qq" => $this->getSender()->getId(),
            "group" => $this->getGroup()->getId(),
            "messageChain" => $msg->toArray(),
        ];
        if ($quote) {
            $pre['quote'] = $this->getMessageChain()->getId();
        }
        return $this->_bot->callBotAPI("/sendTempMessage", $pre);
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
     * Get mute operator object
     *
     * @return \Mirai\GroupUser Operator object
     *
     */

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }

    /**
     *
     * Get group object which bot has been muted
     *
     * @return \Mirai\Group Group object
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->operator->group, $this->_bot);
    }

    /**
     *
     * Get mute operator object
     *
     * @return \Mirai\GroupUser Operator object
     *
     */

    public function getMember(): GroupUser {
        return new GroupUser($this->_bot->getId(), $this->_raw->group->operator->id, $this->_bot);
    }
}
class BotUnmuteEvent extends BotEvent {

    /**
     *
     * Get group object which bot has been muted
     *
     * @return \Mirai\Group Group object
     *
     */

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }

    /**
     *
     * Get mute operator object
     *
     * @return \Mirai\GroupUser Operator object
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
class BotGroupPermissionChangeEvent extends BotEvent {

    /**
     *
     * Get origin permission
     *
     * @return string Origin permission
     *
     */

    public function getOrigin(): string {
        return $this->_raw->origin;
    }

    /**
     *
     * Get current permission
     *
     * @return string Current permission
     *
     */

    public function getCurrent(): string {
        return $this->_raw->current;
    }

    /**
     *
     * Get group which bot permission has been changed
     *
     * @return \Mirai\Group Group object
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
class BotJoinGroupEvent extends BotEvent {

    /**
     *
     * Get group object which bot joined
     *
     * @return \Mirai\Group Group object
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
abstract class BotLeaveEvent extends BotEvent {

    /**
     *
     * Get group object which bot (has been) left
     *
     * @return \Mirai\Group Group object
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
     * @return \Mirai\User User object
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
     * Get recall operator object
     *
     * @return \Mirai\User Operator object
     *
     */

    abstract public function getOperator();
}
class GroupRecallEvent extends RecallEvent {

    /**
     *
     * Get recall operator object
     *
     * @return \Mirai\GroupUser Operator object
     *
     */

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }
}
class FriendRecallEvent extends RecallEvent {

    /**
     *
     * Get recall operator object
     *
     * @return \Mirai\PrivateUser Operator object
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
     * Get target group object
     *
     * @return \Mirai\Group Target group object
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }

    /**
     *
     * Get operator object
     *
     * @return \Mirai\GroupUser Operator object
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
     * Get joined user object
     *
     * @return \Mirai\GroupUser User object
     *
     */

    public function getMember(): GroupUser {
        return new GroupUser($this->_raw->member, $this->_bot);
    }
}
abstract class MemberLeaveEvent extends GroupEvent {

    /**
     *
     * Get left user object
     *
     * @return \Mirai\GroupUser User object
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
     * Get mute operator object
     *
     * @return GroupUser Operator object
     *
     */

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }

    /**
     *
     * Get group object which user has been muted
     *
     * @return \Mirai\Group Group object
     *
     */

    public function getGroup(): Group {
        return new Group($this->_raw->operator->group, $this->_bot);
    }

    /**
     *
     * Get mute operator object
     *
     * @return \Mirai\GroupUser Operator object
     *
     */

    public function getMember(): GroupUser {
        return new GroupUser($this->_raw->member, $this->_bot);
    }
}
class MemberUnmuteEvent extends GroupEvent {
    
    /** 
    *
    * Get group object which user has been muted
    *
    * @return \Mirai\Group Group object
    *
    */

    public function getGroup(): Group {
        return new Group($this->_raw->operator->group, $this->_bot);
    }

    /**
     *
     * Get mute operator object
     *
     * @return \Mirai\GroupUser Operator object
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
     * Get group object which request from.
     * 
     * @return \Mirai\Group Group object
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
