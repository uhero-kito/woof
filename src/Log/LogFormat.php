<?php

namespace Woof\Log;

/**
 * アプリケーションログの書式をカスタマイズするためのインタフェースです。
 */
interface LogFormat
{
    /**
     * 指定されたメッセージ・発生時刻・ログレベルによるログ出力を書式化します。
     *
     * @param string $message 出力するログメッセージ
     * @param int $time ログの発生時刻 (Unix time)
     * @param int $level ログレベル
     * @return string フォーマットされたログ文字列
     */
    public function format(string $message, int $time, int $level): string;
}
