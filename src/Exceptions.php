<?php
namespace Mirai;
class IllegalParamsException extends \Exception{}
class IllegalMessageObjectException extends \Exception{}

class MiraiException extends \Exception{
    protected $errcode;
    public function getErrCode():int{
        return $this->errcode;
    }
}
class InvaildKeyException extends MiraiException{
    protected $errcode = 1;
}

class BotNotFoundException extends MiraiException{
    protected $errcode = 2;
}

class SessionNotExistsException extends MiraiException{
    protected $errcode = 3;
}

class SessionNotVerifiedException extends MiraiException{
    protected $errcode = 4;
}
class TargetNotFoundException extends MiraiException{
    protected $errcode = 5;
}
class PermissionDeniedException extends MiraiException{
    protected $errcode = 10;
}
class BotMutedException extends MiraiException{
    protected $errcode = 20;
}
class MessageTooLongException extends MiraiException{
    protected $errcode = 30;
}

class InvaildRequestException extends MiraiException{
    protected $errcode = 400;
}

class FileNotFoundException extends \Exception{}
class InvaildRespondException extends \Exception{}
class BindFailedException extends \Exception{}
class TimeoutException extends \Exception{}

class ConnectionError extends \Error{};
class ConnectionCloseError extends ConnectionError{}
class ConnectFaliedError extends ConnectionError{}
class UpgradeFailedError extends ConnectionError{}
class FetchFailedError extends \Error{}
