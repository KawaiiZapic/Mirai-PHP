<?php
namespace Mirai;
class IllegalParamsException extends \Exception{}
class IllegalMessageObjectException extends \Exception{}

class InvaildKeyException extends \Exception{}
class InvaildRespondException extends \Exception{}
class FileNotFoundException extends \Exception{}
class BindFailedException extends \Exception{}

class ConnectionError extends \Error{};
class ConnectionCloseError extends ConnectionError{}
class ConnectFaliedError extends ConnectionError{}
class UpgradeFailedError extends ConnectionError{}
class FetchFailedError extends \Error{}
