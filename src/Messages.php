<?php
namespace Mirai;

class MessageChain implements \Countable, \Iterator,\ArrayAccess {
    private $_chain;
    private $_pos;
    public function __construct($arr = []) {
        $this->_pos = 0;
        if (!is_array($arr)) {
            $t = gettype($arr);
            throw new IllegalParamsException("Need an array,but a(n) {$t} given.");
        }
        foreach ($arr as &$obj) {
            if(gettype($obj) == "string"){
                $obj = new PlainMessage($obj);
            }
            if($obj instanceof BaseMessage){
                $obj = $obj->toObject();
            }
            if (!MessageChain::__checkMessageVaild($obj)) {
                throw new \Exception("Invaild message in MessageChain.");
            }
        }
        $this->_chain = $arr;
    }

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
    public function append($obj) {
        if ($obj instanceof BaseMessage) {
            $obj = $obj->toObject();
        }
        $this->_chain[] = $obj;
    }

    public function getId():int {
        if (isset($this->_chain[0]) && $this->_chain[0]->type == "Source") {
            return $this->_chain[0]->id;
        }
        return false;
    }

    public function prepend($obj) {
        if ($obj instanceof BaseMessage) {
            $obj = $obj->toObject();
        }
        array_unshift($this->_chain, $obj);
    }

    public function remove($index) {
        unset($this->_chain[$index]);
        $this->_chain = array_values($this->_chain);
    }

    public function get($index) {
        return $this->_chain[$index];
    }
    public function set($index,$value) {
        $this->_chain[$index] = $value;
    }
    public function toArray():array {
        return $this->_chain;
    }

    public function rewind() {
        $this->_pos = 0;
    }

    public function current() {
        return $this->_chain[$this->_pos];
    }

    public function key():int {
        return $this->_pos;
    }

    public function next() {
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
class BaseMessage {
    protected $_raw;

    private function __construct() {}

    public function getType():string {
        return $this->_raw->type;
    }

    public function toObject():\stdClass {
        return $this->_raw;
    }

}
class SourceMessage extends BaseMessage {
    public function __construct(int $id,int $time) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Source";
        $this->_raw->id = $id;
        $this->_raw->time = $time;
    }

    public function getTime():int {
        return $this->_raw->time;
    }

    public function getId():int {
        return $this->_raw->id;
    }

    public function __toString():string{
        return "";
    }

}
class QuoteMessage extends BaseMessage {
    public function __construct(int $id,array $chain,int $sender,int $group = 0) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Quote";
        $this->_raw->id = $id;
        $this->_raw->senderId = $sender;
        $this->_raw->groupId = $group;
        $this->_raw->targetId = $sender;
        $this->_raw->origin = $chain;
    }

    public function toObject():\stdClass {
        return $this->_raw;
    }

    public function getId():int {
        return $this->_raw->id;
    }
    public function getSenderId():int {
        return $this->_raw->senderId;
    }
    public function getGroupId():int {
        return $this->_raw->groupId;
    }
    public function getTargetId():int {
        return $this->_raw->targetId;
    }
    public function getOrigin():array {
        return $this->_raw->origin;
    }
    public function __toString():string{
        return "[mirai:quote:{$this->_raw->id}:{$this->_raw->senderId}]";
    }
}
class AtMessage extends BaseMessage {
    public function __construct(int $id) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "At";
        $this->_raw->target = $id;
    }

    public function getTargetId():int {
        return $this->_raw->target;
    }

    public function __toString():string{
        return "[mirai:at:{$this->_raw->target}]";
    }

}
class AtAllMessage extends BaseMessage {
    public function __construct() {
        $this->_raw = new \stdClass;
        $this->_raw->type = "AtAll";
    }
    public function __toString():string{
        return "[mirai:atall]";
    }
}
class FaceMessage extends BaseMessage {
    public function __construct(int $id = null,string $name = "") {
        $this->_raw = new \stdClass;
        $this->_raw->faceId = $id;
        $this->_raw->name = $name;
    }

    public function getFaceId():int {
        return $this->_raw->faceId;
    }

    public function getName():string {
        return $this->_raw->name;
    }
    public function __toString():string{
        $s = empty($this->_raw->id) ? $this->_raw->name : $this->_raw->id;
        return "[mirai:face:{$s}]";
    }
}
class PlainMessage extends BaseMessage {

    public function __construct(string $text) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Plain";
        $this->_raw->text = $text;
    }

    public function getText():string {
        return $this->_raw->text;
    }
    public function __toString():string{
        return $this->_raw->text;
    }
}
class ImageMessage extends BaseMessage {
    public function __construct(string $imageId = "",string $url = "",string $path = "") {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Image";
        $this->_raw->imageId = $imageId;
        $this->_raw->url = $url;
        $this->_raw->path = $path;
    }

    public function getImageId():string {
        return $this->_raw->imageId;
    }

    public function getUrl():string {
        return $this->_raw->url;
    }

    public function getPath():string {
        return $this->_raw->path;
    }
    public function __toString():string{
        $s = empty($this->_raw->imageId) ? (empty($this->_raw->url) ? "path:" . $this->_raw->path : "url:" . $this->_raw->url) : $this->_raw->imageId;
        return "[mirai:image:{$s}]";
    }
}
class FlashImageMessage extends ImageMessage {
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
    public function __construct(string $xml) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Xml";
        $this->_raw->xml = $xml;
    }

    public function getXml():string {
        return $this->_raw->xml;
    }
    public function __toString():string{
        return "[mirai:xml:{$this->_raw->xml}]";
    }
}
class JsonMessage extends BaseMessage {
    public function __construct(string $json) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Json";
        $this->_raw->json = $json;
    }

    public function getJson():string {
        return $this->_raw->json;
    }
    public function __toString():string{
        return "[mirai:json:{$this->_raw->json}]";
    }
}
class AppMessage extends BaseMessage {
    public function __construct($content) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "App";
        $this->_raw->content = $content;
    }

    public function getContent():string {
        return $this->_raw->content;
    }
    public function __toString():string{
        return "[mirai:app:{$this->_raw->content}]";
    }
}
class PokeMessage extends BaseMessage {
    public function __construct($name) {
        $this->_raw = new \stdClass;
        $this->_raw->type = "Poke";
        $this->_raw->xml = $name;
    }

    public function getName():string {
        return $this->_raw->Name;
    }
    public function __toString():string{
        return "[mirai:poke:{$this->_raw->name}]";
    }
}