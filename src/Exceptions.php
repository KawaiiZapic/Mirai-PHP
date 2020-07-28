<?php
namespace Mirai;
class IllegalParamsException extends \Exception{}
class IllegalMessageObjectException extends \Exception{}

class InvaildKeyException extends \Exception{}
class InvaildRespondException extends \Exception{}
class FileNotFoundException extends \Exception{}
class BindFailedException extends \Exception{}
class TimeoutException extends \Exception{}
class TargetNotFoundException extends \Exception{}
class MessageTooLongException extends \Exception{}
class BotMutedException extends \Exception{}
class PermissionDeniedException extends \Exception{}

class ConnectionError extends \Error{};
class ConnectionCloseError extends ConnectionError{}
class ConnectFaliedError extends ConnectionError{}
class UpgradeFailedError extends ConnectionError{}
class FetchFailedError extends \Error{}
