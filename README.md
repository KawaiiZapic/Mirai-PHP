# Mirai-PHP
A PHP SDK for Mirai based on Swoole + Coroutine &amp; PHP7+.

## 目前大部分API都已经理论可用  
对于API的实际使用检查与文档进一步补全正在进行,如果在实际使用过程中发现问题可以向我提交Issues报告.  
  
## 快速起步
由于尚未正式发布,所以需要将`minimum-stability` 设置为`dev`.
```
composer require kawaiizapic/mirai-php
```
```php
require_once "vendor/autoload.php";

use \Mirai\Bot;
use \Mirai\GroupMessageEvent;
use \Mirai\MessageChain;
use \Mirai\ImageMessage;

\Co\run(function(){
  $host = "127.0.0.1";
  $port = 8080;
  $key = "HTTPAPIKEY";
  $qq = 1234567890;
    $cli = new \Co\Http\Client($host,$port);
    $bot = new Bot($cli,$key,$qq);
    $bot->setEventHandler(function($event,$raw){
        if($event instanceof GroupMessageEvent && $event->getMessageChain()->__toString() == ":steamsalty:" ) {
          $event->quickReply(new MessageChain([new ImageMessage("{A94288C3-AB52-28B0-FBE2-FA197B92A49E}.mirai")]));
        }
    });
});
```

更多使用方法可以参看源码,源码拥有较为完整的注释.  

## Progress  
* [x] 主体
  * [x] 机器人实例
  * [x] 事件实例
  * [x] 消息与消息链实例
  * [x] 异常与错误  
  

* [ ] 检查API是否可以正常调用
  * [x] 登录
  * [x] 图片上传 
  * [ ] 接收事件
    * [x] 好友消息事件
    * [x] 群消息事件
    * [x] 临时消息事件
    * [ ] Bot在线状况事件
      * [x] 登录成功
      * [ ] 主动离线
      * [ ] 被挤下线
      * [x] 被服务器断开或因网络问题而掉线
      * [x] 重新登录
    * [x] 禁言与解禁
    * [ ] ...
  * [x] 发送消息
    * [x] 好友消息
    * [x] 群消息
    * [x] 临时会话消息 
  * [x] 禁言与解禁
  * [ ] 移除成员
  * [ ] 退群
  * [x] 撤回消息
  * [ ] 好友列表与群(员)列表
  * [ ] 群(员)设置
  * [ ] 机器人管理员列表
  * [ ] 命令注册与监听
  * [ ] ...
  
* [ ] Docs
  * [ ] 主体
  * [ ] 事件
  * [ ] 消息与消息链

## 何时完成?  
快完了快完了(小声

## 需要一个像Mirai-Console的插件框架?
Potabot是一个基于Swoole和本SDK的协程机器人框架,提供完整的插件管理/事件分发/数据存储/权限管理.
**但是她还在开发,需要等待本SDK不再快速迭代之后才能确保稳定**