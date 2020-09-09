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

abstract class BaseMessage {
	
	public $type;
	
	public function __construct() {}
	
	/**
     * 
     * Get type of message
     * 
     * @return string Type of message
     * 
     */

    public function getType():string {
        return $this->type;
    }
    
}
class SourceMessage extends BaseMessage {
	
	public $id;
	public $time;

    /**
     * 
     * Source message
     * 
     * @param int $id Id of message
     * @param int $time Timestamp of message(second)
	 *
     */

    public function __construct(int $id,int $time) {
    	parent::__construct();
        $this->type = "Source";
        $this->id = $id;
        $this->time = $time;
    }

}
class QuoteMessage extends BaseMessage {

	public $id;
	public $senderId;
	public $groupId;
	public $targetId;
	public $origin;
	
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
        parent::__construct();
        $this->type = "Quote";
        $this->id = $id;
        $this->senderId = $sender;
        $this->groupId = $group;
        $this->targetId = $sender;
        $this->origin = $chain;
    }
    
}
class AtMessage extends BaseMessage {

	public $target;
	
    /**
     * 
     * At someone in group
     * 
     * @param int $id Target to at
     * 
     */
    
    public function __construct(int $id) {
    	parent::__construct();
        $this->type = "At";
        $this->target = $id;
    }

}
class AtAllMessage extends BaseMessage {

    /**
     * 
     * At all message
     * 
     */

    public function __construct() {
        parent::__construct();
        $this->type = "AtAll";
    }
}
class FaceMessage extends BaseMessage {

	public $faceId;
	public $name; 
	
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
    	parent::__construct();
        $this->faceId = $id;
        $this->name = $name;
    }
    
}
class PlainMessage extends BaseMessage {

	public $text;
	
	/**
     * 
     * Plain message
     * If you need send a text message,you can directly pass a string to function or chain instead use this(Uncompleted).
     * 
     * @param string $text Text of message
     * 
     */

    public function __construct(string $text) {
        parent::__construct();
        $this->type = "Plain";
        $this->text = $text;
    }
    
}
class ImageMessage extends BaseMessage {

	public $imageId;
	public $url;
	public $path;
	
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
        parent::__construct();
        $this->type = "Image";
        $this->imageId = $imageId;
        $this->url = $url;
        $this->path = $path;
    }
    
}

class VoiceMessage extends BaseMessage {
	
	public $voiceId;
	public $url;
	public $path;
	
	/**
	 *
	 * Voice message
	 * One param must not be empty at least
	 *
	 * @param string $voiceId Id of voice
	 * @param string $url Url of voice
	 * @param string $path Path to voice
	 *
	 */
	
	public function __construct(string $voiceId = "",string $url = "",string $path = "") {
		parent::__construct();
		$this->type = "Voice";
		$this->voiceId = $voiceId;
		$this->url = $url;
		$this->path = $path;
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
        parent::__construct($imageId,$url,$path);
        $this->type = "FlashImage";
    }
}
class XmlMessage extends BaseMessage {

	public $xml;
	
    /**
     * 
     * XML card message
     * 
     * @param string $xml XML card content
     * 
     */

    public function __construct(string $xml) {
        parent::__construct();
        $this->type = "Xml";
        $this->xml = $xml;
    }
    
}
class JsonMessage extends BaseMessage {

	public $json;
	
    /**
     * 
     * JSON card message
     * 
     * @param string $json Json card content
     * 
     */

    public function __construct(string $json) {
        parent::__construct();
        $this->type = "Json";
        $this->json = $json;
    }
}
class AppMessage extends BaseMessage {

	public $content;
	
    /**
     * 
     * App card message
     * 
     * @param string App card content
	 *
     */
    
    public function __construct($content) {
		parent::__construct();
        $this->type = "App";
        $this->content = $content;
    }
}
class PokeMessage extends BaseMessage {
	
	public $name;
	
    /**
     * 
     * Poke message
     * Allow: Poke, ShowLove, Like, Heartbroken, SixSixSix, FangDaZhao
     * 
     * @param string $name Poke name.
     * 
     */
    
    public function __construct(string $name) {
		parent::__construct();
        $this->type = "Poke";
        $this->name = $name;
    }

}