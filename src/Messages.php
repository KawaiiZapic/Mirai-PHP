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

class MessageChain implements \Countable, \Iterator,\ArrayAccess {
    private $_chain;
    private $_pos;
    /**
     * 
     * Create a message chain
     * 
     * @param array $arr Message chain array
     * 
     * @throws IllegalParamsException Maybe there is a not vaild object in chain
     * 
     * @return void
     * 
     */
    public function __construct(array $arr = []) {
        $this->_pos = 0;
        foreach ($arr as &$obj) {
            if(gettype($obj) == "string"){
                $obj = new PlainMessage($obj);
            }
            if($obj instanceof BaseMessage){
                $obj = $obj->toObject();
            }
            if (!MessageChain::__checkMessageVaild($obj)) {
                throw new IllegalParamsException("Invaild message in MessageChain.");
            }
        }
        $this->_chain = $arr;
    }

    /**
     * 
     * Stringify chain
     * 
     * @return string Stringify chain
     * 
     */

    public function __toString():string {
        $str = '';
        foreach ($this->_chain as $msg) {
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
     * Check message is vaild
     * 
     * @param mixed $obj Message object
     * 
     * @return bool Is vaild
     * 
     */

    private static function __checkMessageVaild($obj):bool {
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
                if (!MessageChain::__checkMessageVaild($v)) {
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
        case "Image":
            if (!property_exists($obj, "imageId") && !property_exists($obj, "url") && !property_exists($obj, "path")) {
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

    /**
     * 
     * Append a message to chain
     * 
     * @param mixed $obj Message to append
     * 
     * @throws IllegalParamsException Message is not invaild.
     * 
     * @return void
     * 
     */

    public function append($obj) {
        if(gettype($obj) == "string"){
            $obj = new PlainMessage($obj);
        }
        if ($obj instanceof BaseMessage) {
            $obj = $obj->toObject();
        }
        if (!MessageChain::__checkMessageVaild($obj)) {
            throw new IllegalParamsException("Invaild message object.");
        }
        $this->_chain[] = $obj;
    }

    /**
     * 
     * Return the id of message.
     * If the first object of chain is not a Source Message,function will return -1
     * 
     * @return int Id of message
     * 
     */

    public function getId():int {
        if (isset($this->_chain[0]) && $this->_chain[0]->type == "Source") {
            return $this->_chain[0]->id;
        }
        return -1;
    }

    /**
     * 
     * Prepend a message to chain
     * 
     * @throws IllegalParamsException Message is not invaild.
     * 
     * @return void
     *  
     */

    public function prepend($obj) {
        if(gettype($obj) == "string"){
            $obj = new PlainMessage($obj);
        }
        if ($obj instanceof BaseMessage) {
            $obj = $obj->toObject();
        }
        if (!MessageChain::__checkMessageVaild($obj)) {
            throw new IllegalParamsException("Invaild message object.");
        }
        array_unshift($this->_chain, $obj);
    }

    /**
     * 
     * Remove a object from chain
     * 
     * @param int Index of object
     * 
     * @return bool Removed or not
     * 
     */

    public function remove(int $index):bool {
        if(!isset($this->_chain[$index])){
            return false;
        }
        unset($this->_chain[$index]);
        $this->_chain = array_values($this->_chain);
        return true;
    }

    /**
     *  Return chain as array
     * 
     * @return array Array of chain
     * 
     */

    public function toArray():array {
        return $this->_chain;
    }

    // Interface

    public function get($index) {
        return $this->_chain[$index];
    }

    public function set($index,$value):void {
        $this->_chain[$index] = $value;
    }

    public function rewind():void {
        $this->_pos = 0;
    }

    public function current():int {
        return $this->_chain[$this->_pos];
    }

    public function key():int {
        return $this->_pos;
    }

    public function next():void {
        $this->_pos++;
    }

    public function valid():bool {
        return isset($this->_chain[$this->_pos]);
    }

    public function count():int {
        return count($this->_chain);
    }

    public function offsetSet($offset, $value) {
        if(!is_int($offset)){
            return false;
        }
        if($value instanceof BaseMessage){
            $value = $value->toObject();
        }
        if(!$this->__checkMessageVaild($value)){
            throw new IllegalMessageObjectException();
        }
        if (is_null($offset)) {
            $this->_chain[] = $value;
        } else {
            $this->_chain[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->_chain[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_chain[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->_chain[$offset]) ? $this->_chain[$offset] : null;
    }

}
abstract class BaseMessage {
    protected $_raw;

    public function __construct(){}

    /**
     * 
     * Get type of message
     * 
     * @return string Type of message
     * 
     */

    public function getType():string {
        return $this->_raw->type;
    }

    /**
     * 
     * Return object of message
     * 
     * @return stdClass Message object
     * 
     */

    public function toObject():\stdClass {
        return $this->_raw;
    }

    public function __get($name){
        return $this->_raw->$name;
    }

}
class SourceMessage extends BaseMessage {

    /**
     * 
     * Source message
     * 
     * @param int $id Id of message
     * @param int $time Timestamp of message(second)
     * 
     */

    public function __construct(int $id,int $time) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Source";
        $this->_raw->id = $id;
        $this->_raw->time = $time;
    }

    /**
     * 
     * Get timestamp of message
     * 
     * @return int Timestamp of message
     * 
     */

    public function getTime():int {
        return $this->_raw->time;
    }

    /**
     * 
     * Get id of message
     * 
     * @return int $id Id of message
     * 
     */

    public function getId():int {
        return $this->_raw->id;
    }

    public function __toString():string{
        return "";
    }

}
class QuoteMessage extends BaseMessage {

    /**
     * 
     * Quote message
     * If you need to quote a message when you send message,DO NOT append this to chain,use Quote param instead.
     * 
     * @param int $id Id of message which is quote
     * @param array $chain Chain of message which is quote
     * @param int $sender Sender of message which is quote
     * @param int $group Group which sender in,if is 0,message is in private chat
     * 
     */
    
    public function __construct(int $id,array $chain,int $sender,int $group = 0) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Quote";
        $this->_raw->id = $id;
        $this->_raw->senderId = $sender;
        $this->_raw->groupId = $group;
        $this->_raw->targetId = $sender;
        $this->_raw->origin = $chain;
    }

    /**
     * 
     * Get id of message which is quote
     * 
     * @return int Id of message which is quote
     * 
     */
    
    public function getId():int {
        return $this->_raw->id;
    }
    
    /**
     * 
     * Get sender id of message which is quote
     * 
     * @return int Id of sender 
     * 
     */
    
    public function getSenderId():int {
        return $this->_raw->senderId;
    }

    /**
     * 
     * Get group which sender of message which is quote in
     * 
     * @return int Id of group
     * 
     */

    public function getGroupId():int {
        return $this->_raw->groupId;
    }
     
    /**
     * 
     * Get sender id of message which is quote
     * 
     * @return int Id of sender 
     * 
     */

    public function getTargetId():int {
        return $this->_raw->targetId;
    }
     
    /**
     * 
     * Get message chain which is quote
     * 
     * @return array Quote meesage chain
     * 
     */

    public function getOrigin():array {
        return $this->_raw->origin;
    }

    public function __toString():string{
        return "[mirai:quote:{$this->_raw->id}:{$this->_raw->senderId},{$this->_raw->groupId}]";
    }
}
class AtMessage extends BaseMessage {

    /**
     * 
     * At someone in group
     * 
     * @param int $id Target to at
     * 
     */
    public function __construct(int $id) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "At";
        $this->_raw->target = $id;
    }

    /**
     * 
     * Get id of target
     * 
     * @return int Id of target
     */

    public function getTargetId():int {
        return $this->_raw->target;
    }

    public function __toString():string{
        return "[mirai:at:{$this->_raw->target}]";
    }

}
class AtAllMessage extends BaseMessage {

    /**
     * 
     * At all message
     * 
     */

    public function __construct() {
        $this->_raw = new \stdClass;
        $this->_raw->type = "AtAll";
    }
    public function __toString():string{
        return "[mirai:atall]";
    }
}
class FaceMessage extends BaseMessage {

    /**
     * 
     * Face message
     * One param must not be null at least
     * 
     * @param int $id Id of face
     * @param string $name Name of face
     * 
     */

    public function __construct(int $id = null,string $name = "") {
        $this->_raw = new \stdClass;
        $this->_raw->faceId = $id;
        $this->_raw->name = $name;
    }

    /**
     * 
     * Get id of face
     * 
     * @return int Face id
     */

    public function getFaceId():int {
        return $this->_raw->faceId;
    }

    /**
     * 
     * Get name of face
     * 
     * @return string Face name
     * 
     */

    public function getName():string {
        return $this->_raw->name;
    }

    public function __toString():string{
        $s = empty($this->_raw->id) ? $this->_raw->name : $this->_raw->id;
        return "[mirai:face:{$s}]";
    }
}
class PlainMessage extends BaseMessage {

    /**
     * 
     * Plain message
     * If you need send a text message,you can directly pass a string to function or chain instead use this(Incompleted).
     * 
     * @param string $text Text of message
     * 
     */

    public function __construct(string $text) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Plain";
        $this->_raw->text = $text;
    }

    /**
     * 
     * Get text of message
     * 
     * @return string Message text
     * 
     */

    public function getText():string {
        return $this->_raw->text;
    }

    public function __toString():string{
        return $this->_raw->text;
    }
}
class ImageMessage extends BaseMessage {

    /**
     * 
     * Image message
     * One param must not be empty at least
     * 
     * @param string $imageId Id of image
     * @param string $url Url of image
     * @param string $path Path to image
     * 
     */

    public function __construct(string $imageId = "",string $url = "",string $path = "") {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Image";
        $this->_raw->imageId = $imageId;
        $this->_raw->url = $url;
        $this->_raw->path = $path;
    }

    /**
     * 
     * Get id of image
     * 
     * @return string Image id
     * 
     */

    public function getImageId():string {
        return $this->_raw->imageId;
    }

    /**
     * 
     * Get url of image
     * 
     * @return string Image url
     * 
     */

    public function getUrl():string {
        return $this->_raw->url;
    }

    /**
     * 
     * Get path to image
     * 
     * @return string Path to image
     */

    public function getPath():string {
        return $this->_raw->path;
    }
    public function __toString():string{
        $s = empty($this->_raw->imageId) ? (empty($this->_raw->url) ? "path:" . $this->_raw->path : "url:" . $this->_raw->url) : $this->_raw->imageId;
        return "[mirai:image:{$s}]";
    }
}
class FlashImageMessage extends ImageMessage {
    
    /**
     * 
     * Flash image message
     * One param must not be empty at least
     * 
     * @param string $imageId Id of image
     * @param string $url Url of image
     * @param string $path Path to image
     * 
     */

    public function __construct($imageId = "", $url = "", $path = "") {
        $this->_raw = new \stdClass;
        $this->_raw->type = "FlashImage";
        $this->_raw->imageId = $imageId;
        $this->_raw->url = $url;
        $this->_raw->path = $path;
    }

    public function __toString():string{
        $s = empty($this->_raw->imageId) ? (empty($this->_raw->url) ? "path:" . $this->_raw->path : "url:" . $this->_raw->url) : $this->_raw->imageId;
        return "[mirai:flashimage:{$s}]";
    }
}
class XmlMessage extends BaseMessage {

    /**
     * 
     * XML card message
     * 
     * @param string $xml XML card content
     * 
     */

    public function __construct(string $xml) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Xml";
        $this->_raw->xml = $xml;
    }

    /**
     * 
     * Get XML content of card
     * 
     * @return string XML content
     * 
     */

    public function getXml():string {
        return $this->_raw->xml;
    }

    public function __toString():string{
        return "[mirai:xml:{$this->_raw->xml}]";
    }
}
class JsonMessage extends BaseMessage {

    /**
     * 
     * JSON card message
     * 
     * @param string $json Json card content
     * 
     */

    public function __construct(string $json) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Json";
        $this->_raw->json = $json;
    }

    /**
     * 
     * Get JSON content of card
     * 
     * @return string JSON Content
     * 
     */

    public function getJson():string {
        return $this->_raw->json;
    }

    public function __toString():string{
        return "[mirai:json:{$this->_raw->json}]";
    }
}
class AppMessage extends BaseMessage {

    /**
     * 
     * App card message
     * 
     * @param string App card content
     */
    public function __construct($content) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "App";
        $this->_raw->content = $content;
    }

    /**
     * 
     * Get App content of card
     * 
     * @return string Card content.
     */

    public function getContent():string {
        return $this->_raw->content;
    }

    public function __toString():string{
        return "[mirai:app:{$this->_raw->content}]";
    }
}
class PokeMessage extends BaseMessage {

    /**
     * 
     * Poke message
     * Allow: Poke, ShowLove, Like, Heartbroken, SixSixSix, FangDaZhao
     * 
     * @param string $name Poke name.
     * 
     */
    public function __construct(string $name) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Poke";
        $this->_raw->xml = $name;
    }

    /**
     * 
     * Get name of poke
     * 
     * @return string Name of poke 
     * 
     */

    public function getName():string {
        return $this->_raw->name;
    }

    public function __toString():string{
        return "[mirai:poke:{$this->_raw->name}]";
    }
}