<?php

namespace Mirai;

class BaseEvent {
    protected $_raw;
    protected $_bot;
    private $_prop = true;
    public final function __construct($obj, &$bot) {
        $this->_raw = $obj;
        $this->_bot = $bot;
    }
    public function getType(): string {
        return $this->_raw->type;
    }
    public function getBot(): Bot {
        return $this->_bot;
    }
    protected function Init() {
    }
    public function stopPropagation() {
        $this->_prop = false;
    }
    public function getPropagation(): bool {
        return $this->_prop;
    }
}
abstract class MessageEvent extends BaseEvent {
    protected function Init() {
        $this->_raw->messageChain = new MessageChain($this->_raw->messageChain);
    }
    public function getMessageChain(): MessageChain {
        return new MessageChain($this->_raw->messageChain);
    }
    abstract public function getSender();
    abstract public function quickReply($msg,bool $quote = false);
}
class GroupMessageEvent extends MessageEvent {
    public function getGroup(): Group {
        return new Group($this->_raw->sender->group, $this->_bot);
    }

    public function quickReply($msg,bool $quote = false) {
        if(gettype($msg) == "string"){
            $msg = new \Mirai\MessageChain([$msg]);
        }
        $msg = $msg instanceof MessageChain ? $msg->toArray() : $msg;
        $pre = [
            "target" => $this->getGroup()->getId(),
            "messageChain" => $msg
        ];
        if ($quote) {
            $pre['quote'] = $this->getMessageChain()->getId();
        }
        return $this->_bot->callBotAPI("/sendGroupMessage", $pre);
    }

    public function recallMessage() {
        return $this->_bot->callBotAPI("/recall", ["target" => $this->getMessageChain()->getId()]);
    }

    public function kickMember($msg = "") {
        return $this->_bot->callBotAPI("/kick", ["target" => $this->getGroup()->getId(), "memberId" => $this->getSender()->getId(), "msg" => $msg]);
    }
    public function getSender(): GroupUser {
        return new GroupUser($this->_raw->sender, $this->_bot);
    }
}
class FriendMessageEvent extends MessageEvent {
    public function quickReply($msg,bool $quote = false) {
        $pre = [
            "target" => $this->getSender()->getId(),
            "messageChain" => $msg->toArray()
        ];
        if ($quote) {
            $pre['quote'] = $this->getMessageChain()->getId();
        }
        return $this->_bot->callBotAPI("/sendFriendMessage", $pre);
    }
    public function getSender(): PrivateUser {
        return new PrivateUser($this->_raw->sender, $this->_bot);
    }
}
class TempMessageEvent extends MessageEvent {
    public function getGroup(): Group {
        return new Group($this->_raw->sender->group, $this->_bot);
    }
    public function quickReply($msg,bool $quote = false) {
        $pre = [
            "qq" => $this->getSender()->id,
            "group" => $this->getGroup()->id,
            "messageChain" => $msg->toArray()
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
    public function getDuration(): int {
        return $this->_raw->durationSeconds;
    }

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }

    public function getGroup(): Group {
        return new Group($this->_raw->operator->group, $this->_bot);
    }

    public function getMember(): GroupUser {
        return new GroupUser($this->_bot->getId(), $this->_raw->group->operator->id, $this->_bot);
    }
}
class BotUnmuteEvent extends BotEvent {
    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
class BotGroupPermissionChangeEvent extends BotEvent {
    public function getOrigin(): string {
        return $this->_raw->origin;
    }

    public function getCurrent(): string {
        return $this->_raw->current;
    }

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
class BotJoinGroupEvent extends BotEvent {
    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
abstract class BotLeaveEvent extends BotEvent {
    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }
}
class BotLeaveEventActive extends BotLeaveEvent {}
class BotLeaveEventKick extends BotLeaveEvent {}
abstract class RecallEvent extends BaseEvent {
    private $_operator;

    public function getAuthor(): User {
        return new User($this->_raw->authorId, $this->_bot);
    }
    public function getMessageId(): int {
        return $this->_raw->messageId;
    }
    public function getTime(): int {
        return $this->_raw->time;
    }
    abstract public function getOperator();
}
class GroupRecallEvent extends RecallEvent {
    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }
}
class FriendRecallEvent extends RecallEvent {
    public function getOperator(): PrivateUser {
        return new PrivateUser($this->_raw->operator, $this->_bot);
    }
}
abstract class GroupEvent extends BaseEvent {}
abstract class GroupChangeEvent extends GroupEvent {

    public function getOrigin(): string {
        return $this->_raw->origin;
    }

    public function getCurrent(): string {
        return $this->_raw->current;
    }

    public function getGroup(): Group {
        return new Group($this->_raw->group, $this->_bot);
    }

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }
}
class GroupNameChangeEvent extends GroupChangeEvent {}
class GroupEntranceAnnouncementChangeEvent extends GroupChangeEvent {}
class GroupMuteAllEvent extends GroupChangeEvent {}
class GroupAllowAnonymousChatEvent extends GroupChangeEvent {}
class GroupAllowConfessTalkEvent extends GroupChangeEvent {
    public function getOperator(): GroupUser {
        throw new \Exception("Illeage Called function getOperator,call \"isByBot\" instead.");
        return new GroupUser(null, null);
    }
    public function isByBot(): bool {
        return $this->_raw->isByBot;
    }
}
class GroupAllowMemberInviteEvent extends GroupChangeEvent {}
class MemberJoinEvent extends GroupEvent {
    public function getMember(): GroupUser {
        return new GroupUser($this->_raw->member, $this->_bot);
    }
}
abstract class MemberLeaveEvent extends GroupEvent {
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
    public function getDuration(): int {
        return $this->_raw->durationSeconds;
    }

    public function getOperator(): GroupUser {
        return new GroupUser($this->_raw->operator, $this->_bot);
    }

    public function getGroup(): Group {
        return new Group($this->_raw->operator->group, $this->_bot);
    }

    public function getMember(): GroupUser {
        return new GroupUser($this->_raw->member, $this->_bot);
    }
}
class MemberUnmuteEvent extends GroupEvent {
    public function getGroup(): Group {
        return new Group($this->_raw->operator->group, $this->_bot);
    }

    public function getMember(): GroupUser {
        return new GroupUser($this->_raw->member, $this->_bot);
    }
}
abstract class RequestEvent extends BaseEvent {
    public function getEventId(): int {
        return $this->_raw->eventId;
    }
    public function getFromId(): int {
        return $this->_raw->fromId;
    }
    public function getGroup(): Group {
        return new Group($this->_raw->groupId, $this->_raw);
    }
    public function getNick(): string {
        return $this->_raw->nick;
    }
    public function getMessage(): string {
        return $this->_raw->message;
    }
    abstract public function ResponseCode(int $code,string $msg = "");
}
class NewFriendRequestEvent extends RequestEvent {
    public function Approve() {
        return $this->ResponseCode(0);
    }
    public function Deny(string $msg = "") {
        return $this->ResponseCode(1, $msg);
    }
    public function DenyBlock(string $msg = "") {
        return $this->ResponseCode(2, $msg);
    }
    public function ResponseCode(int $code, string $msg = "") {
        return $this->_bot->callBotAPI("/resp/newFriendRequestEvent", [
            "eventId" => $this->_raw->eventId,
            "fromId" => $this->_raw->fromId,
            "groupId" => $this->_raw->groupId,
            "operate" => $code,
            "message" => $msg
        ]);
    }
}
class MemberJoinRequestEvent extends RequestEvent {
    public function getGroupName(): string {
        return $this->_raw->groupName;
    }
    public function Approve() {
        return $this->ResponseCode(0);
    }
    public function Deny($msg = "") {
        return $this->ResponseCode(1, $msg);
    }
    public function DenyBlock($msg = "") {
        return $this->ResponseCode(3, $msg);
    }
    public function Ignore() {
        return $this->ResponseCode(2);
    }
    public function IgnoreBlock() {
        return $this->ResponseCode(4);
    }
    public function ResponseCode(int $code, string $msg = "") {
        return $this->_bot->callBotAPI("/resp/memberJoinRequestEvent", [
            "eventId" => $this->_raw->eventId,
            "fromId" => $this->_raw->fromId,
            "groupId" => $this->_raw->groupId,
            "operate" => $code,
            "message" => $msg
        ]);
    }
}
class BotInvitedJoinGroupRequestEvent extends RequestEvent {
    public function Approve() {
        return $this->ResponseCode(0);
    }
    public function Deny() {
        return $this->ResponseCode(1);
    }
    public function ResponseCode(int $code, string $msg = "") {
        return $this->_bot->callBotAPI("/resp/memberJoinRequestEvent", [
            "eventId" => $this->_raw->eventId,
            "fromId" => $this->_raw->fromId,
            "groupId" => $this->_raw->groupId,
            "operate" => $code,
            "message" => $msg
        ]);
    }
}
