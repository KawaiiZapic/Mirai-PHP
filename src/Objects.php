<?php
namespace Mirai;
class User {
    protected $_user;
    protected $_bot;

    /**
     * 
     * "Abstract" User object
     * Can not do any thing
     * 
     * @param mixed $dat User dat,accept object and int
     * @param \Mirai\Bot $bot Bot instance
     * 
     */

    public function __construct($dat,\Mirai\Bot &$bot) {
        $this->_bot = $bot;
        if (is_numeric($dat)) {
            $this->_user = new \stdClass();
            $this->_user->id = $dat;
        } else {
            $this->_user = $dat;
        }
    }

    /**
     * 
     * Get user data object
     * 
     * @return mixed User data object
     * 
     */

    public function toObject(){
        return $this->_user;
    }

    /**
     * 
     * Get user id
     * 
     * @return int QQ user id
     * 
     */

    public function getId():int{
        return $this->_user->id;
    }
}
class Group {
    private $_group;
    private $_bot;

    /**
     * 
     * Group object
     * 
     * @param mixed $dat Group data,accept object and int
     * @param \Mirai\Bot Bot instance
     * 
     */

    public function __construct($dat,\Mirai\Bot &$bot) {
        $this->_bot = $bot;
        if (is_numeric($dat)) {
            $this->_group = new \stdClass();
            $this->_group->id = $dat;
        } else {
            $this->_group = $dat;
        }
    }

    /**
     * 
     * Get group data object
     * 
     * @return mixed User data object
     * 
     */

    public function toObject(){
        return $this->_group;
    }

    /**
     * 
     * Get group id
     * 
     * @return int Group id
     * 
     */

    public function getId():int {
        return $this->_group->id;
    }

    /**
     * 
     * Get bot permission in this group
     * 
     * @return string Bot permission
     * 
     */

    public function getBotPermission():string {
        return $this->_group->permission;
    }

    /**
     * 
     * Get group name
     * 
     * @return string Group name
     * 
     */

    public function getGroupName():string {
        return $this->_group->name;
    }

    /**
     * 
     * Get group member list
     * 
     * @return mixed Group user list
     * 
     */

    public function getMemberList() {
        return $this->_bot->getMemberList($this->getId());
    }

    /**
     * 
     * Enable group hole mute
     * 
     * @return mixed API response
     * 
     */

    public function muteAll() {
        return $this->_bot->muteAll($this->getId());
    }

    /**
     * 
     * Disable group hole mute
     * 
     * @return mixed API response
     * 
     */

    public function unmuteAll() {
        return $this->_bot->unmuteAll($this->getId());
    }

    /**
     * 
     * Mute someone in group
     * 
     * @param int $target Target id
     * @param int $time Duration of mute
     * 
     * @return mixed API response
     * 
     */

    public function muteMember($target, $time = 0) {
        return $this->_bot->muteMember($this->getId(),$target,$time);
    }

    /**
     * 
     * Unmute someone in group
     * 
     * @param int $target Target id
     * 
     * @return mixed API response
     * 
     */

    public function unmuteMember($target) {
        return $this->_bot->unmuteMember($this->getId(),$target);
    }

    /**
     * 
     * Kick someone in group
     * 
     * @param int $target Target to remove
     * @param string $msg Kick message
     * 
     * @return mixed API response 
     * 
     */

    public function kickMember($target, $msg = "") {
        return $this->_bot->kickMember($this->getId(),$target,$msg);
    }

    /**
     * 
     * Leave this group
     * 
     * @return mixed API response
     * 
     */

    public function quitGroup() {
        return $this->_bot->quitGroup($this->getId());
    }

    /**
     * 
     * Get/Set config of group
     * This is a jQuery-like function
     * If the sencond param is null,function will return the config vale
     * If the second param is not null,the value will be save to group
     * 
     * @param string $name Config name
     * @param mixed $value Value to set
     * 
     * @return mixed API response
     * 
     */
    public function GroupConfig($name, $value = null) {
        return $this->_bot->GroupConfig($this->getId(),$name,$value);
    }

    /**
     * 
     * Get/Set info of group member
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

    public function memberInfo($target, $name, $value = null) {
        return $this->_bot->GroupConfig($this->getId(),$target,$name,$value);
    }

    /**
     * 
     * Send message to group
     * 
     * @param mixed $msg Message to send,accept MessageChain,Array and string
     * @param int $quote Id of message  to quote
     * 
     * @return mixed API response
     * 
     */

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

    /**
     * 
     * Group user object
     * Accept 2 or 3 params.
     * 
     * @param mixed $dat Group user data or QQ id
     * @param mixed $sec Bot object or group id
     * @param \Mirai\Bot $bot Bot object
     * 
     */

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

    /**
     * 
     * Get group object which sender in
     * 
     * @return \Mirai\Group Group object
     * 
     */

    public function getGroup():Group{
        return new Group($this->_user->group,$this->_bot);
    }

    /**
     * 
     * Kick this user
     * 
     * @param string Kick message
     * 
     * @return mixed API response
     * 
     */

    public function Kick(string $msg=""){
        return $this->_bot->callBotAPI("/kick", ["target" => $this->_user->group->id, "msg" => $msg, "memberId" =>  $this->_user->id]);
    }

    /**
     * 
     * Mute this user
     * 
     * @param int $time Mute duration
     * 
     * @return mixed API response
     * 
     */

    public function Mute($time = 0){
        return $this->_bot->callBotAPI("/mute", ["target" => $this->_user->group->id, "memberId" => $this->_user->id, "time" => $time]);
    }

    /**
     * 
     * Unmute this user
     * 
     * @return mixed API response
     * 
     */

    public function Unmute(){
        return $this->_bot->callBotAPI("/unmute", ["target" => $this->_user->group->id, "memberId" => $this->_user->id]);
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

    public function Info($name,$value=null){
        $d = $this->_bot->callBotAPI("/memberInfo", ["target" => $this->_user->group->id, "memberId" => $this->_user->id], "get");
        if ($value !== null) {
            $d->$name = $value;
            return $this->_bot->callBotAPI("/memberInfo", ["target" => $this->_user->group->id, "memberId" => $this->_user->id, "info" => $d]);
        }
        return $d->$name;
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

    public function sendMessage($msg,$quote = null){
        $msg = $msg instanceof MessageChain ? $msg->toArray() : $msg;
        return $this->_bot->sendTempMessage($this->_user->id,$this->_user->group->id,$msg,$quote);
    }
}
class PrivateUser extends User {

    /**
     * 
     * Private user
     * 
     * @param mixed $dat User data,accept int and object
     * @param \Mirai\Bot Bot object
     * 
     */

    public function __construct($dat,\Mirai\Bot $bot){
        $this->_bot = $bot;
        if (is_numeric($dat)) {
            $this->_user = new \stdClass();
            $this->_user->id = $dat;
        } else {
            $this->_user = $dat;
        }
    }

    /**
     * 
     * Send private chat to this user
     * 
     * @param mixed $msg Message to send,accept MessageChain,Array and string
     * @param int $quote Id of message to quote
     * 
     * @return mixed API response
     * 
     */

    public function sendMessage($msg,$quote = null){
        $msg = $msg instanceof MessageChain ? $msg->toArray() : $msg;
        return $this->_bot->sendFriendMessage($this->_user->id,$this->_user->group->id,$msg,$quote);
    }
}