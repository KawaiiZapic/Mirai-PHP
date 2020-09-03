<?php
namespace Mirai;
use Error;
use Exception;

class IllegalParamsException extends Exception{}

class MiraiException extends Exception{
    protected $errCode;
    public function getErrCode():int{
        return $this->errCode;
    }
}
class InvalidKeyException extends MiraiException{
    protected $errCode = 1;
}

class BotNotFoundException extends MiraiException{
    protected $errCode = 2;
}

class SessionNotExistsException extends MiraiException{
    protected $errCode = 3;
}

class SessionNotVerifiedException extends MiraiException{
    protected $errCode = 4;
}
class TargetNotFoundException extends MiraiException{
    protected $errCode = 5;
}
class PermissionDeniedException extends MiraiException{
    protected $errCode = 10;
}
class BotMutedException extends MiraiException{
    protected $errCode = 20;
}
class MessageTooLongException extends MiraiException{
    protected $errCode = 30;
}

class InvalidRequestException extends MiraiException{
    protected $errCode = 400;
}

class FileNotFoundException extends Exception{}
class InvalidRespondException extends Exception{}
class TimeoutException extends Exception{}
class IllegalOperateException extends Exception{}

class ConnectionError extends Error{}
class ConnectionCloseError extends ConnectionError{}
class ConnectFailedError extends ConnectionError{}
class UpgradeFailedError extends ConnectionError{}
class FetchFailedError extends Error{}

