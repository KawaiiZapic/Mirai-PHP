<?php
namespace Mirai;
class User {
    protected $_user;
    protected $_bot;
    public function __construct($dat,&$bot) {
        $this->_bot = $bot;
        if (is_numeric($dat)) {
            $this->_user = new \stdClass();
            $this->_user->id = $dat;
        } else {
            $this->_user = $dat;
        }
    }

    public function toObject(){
        return $this->_user;
    }

    public function getId():int{
        return $this->_user->id;
    }
}
class Group {
    private $_group;
    private $_bot;
    public function __construct($dat, &$bot) {
        $this->_bot = $bot;
        if (is_numeric($dat)) {
            $this->_group = new \stdClass();
            $this->_group->id = $dat;
        } else {
            $this->_group = $dat;
        }
    }
    public function toObject(){
        return $this->_group;
    }
    public function getId():int {
        return $this->_group->id;
    }
    public function getBotPermission():string {
        return $this->_group->permission;
    }
    public function getGroupName():string {
        return $this->_group->name;
    }
    public function getMemberList() {
        return $this->_bot->getMemberList($this->getId());
    }
    public function muteAll() {
        return $this->_bot->muteAll($this->getId());
    }
    public function unmuteAll() {
        return $this->_bot->unmuteAll($this->getId());
    }

    public function muteMember($target, $time = 0) {
        return $this->_bot->muteMember($this->getId(),$target,$time);
    }
    public function unmuteMember($target) {
        return $this->_bot->unmuteMember($this->getId(),$target);
    }
    public function kickMember($target, $msg = "") {
        return $this->_bot->kickMember($this->getId(),$target,$msg);
    }
    public function quitGroup() {
        return $this->_bot->quitGroup($this->getId());
    }
    public function GroupConfig($name, $value = null) {
        return $this->_bot->GroupConfig($this->getId(),$name,$value);
    }

    public function memberInfo($target, $name, $value = null) {
        return $this->_bot->GroupConfig($this->getId(),$target,$name,$value);
    }

    public function sendMessage($msg,$quote=null){
        $msg = $msg instanceof MessageChain ? $msg->toArray() : $msg;
        $pre = [
            "target" => $this->_group->id,
            "messageChain" => $msg
        ];
        return $this->_bot->sendGroupMessage($this->getId(),$pre,$quote);
    }

}
class GroupUser extends User {
    public function __construct($dat,&$sec,&$bot=null){
        if (is_numeric($dat)) {
            $this->_bot = $bot;
            $this->_user = new \stdClass();
            $this->_user->id = $dat;
            $this->_user->group->id = intval($sec);
        } else {
            $this->_user = $dat;
            $this->_bot = $sec;
        }
    }

    public function getGroup():Group{
        return new Group($this->_user->group,$this->_bot);
    }

    public function Kick($msg=""){
        return $this->_bot->callBotAPI("/kick", ["target" => $this->_user->group->id, "msg" => $msg, "memberId" =>  $this->_user->id]);
    }
    public function Mute($time = 0){
        return $this->_bot->callBotAPI("/mute", ["target" => $this->_user->group->id, "memberId" => $this->_user->id, "time" => $time]);
    }
    public function Unmute(){
        return $this->_bot->callBotAPI("/unmute", ["target" => $this->_user->group->id, "memberId" => $this->_user->id]);
    }
    public function Info($name,$value=null){
        $d = $this->_bot->callBotAPI("/memberInfo", ["target" => $this->_user->group->id, "memberId" => $this->_user->id], "get");
        if ($value !== null) {
            $d->$name = $value;
            return $this->_bot->callBotAPI("/memberInfo", ["target" => $this->_user->group->id, "memberId" => $this->_user->id, "info" => $d]);
        }
        return $d->$name;
    }

    public function sendMessage($msg){
        $msg = $msg instanceof MessageChain ? $msg->toArray() : $msg;
        $pre = [
            "qq" => $this->_user->id,
            "group"=>$this->_user->group->id,
            "messageChain" => $msg
        ];
        return $this->_bot->callBotAPI("/sendTempMessage",$pre);
    }
}
class PrivateUser extends User {
    public function __construct($dat,$bot=null){
        $this->_bot = $bot;
        if (is_numeric($dat)) {
            $this->_user = new \stdClass();
            $this->_user->id = $dat;
        } else {
            $this->_user = $dat;
        }
    }
    public function sendMessage($msg){
        $msg = $msg instanceof MessageChain ? $msg->toArray() : $msg;
        $pre = [
            "target" => $this->_user->id,
            "messageChain" => $msg
        ];
        return $this->_bot->callBotAPI("/sendFriendMessage",$pre);
    }
}