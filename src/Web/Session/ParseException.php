<?php

namespace Woof\Web\Session;

use Exception;

/**
 * セッションデータの解析 (パース) の際に不正なフォーマットを検出した場合にスローされる例外です。
 */
class ParseException extends Exception
{
}
