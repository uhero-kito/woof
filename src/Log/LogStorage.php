<?php

namespace Woof\Log;

/**
 * アプリケーションログの保存先を抽象化するインタフェースです。
 */
interface LogStorage
{
    /**
     * 指定されたメッセージ・時刻・ログレベルでログを記録します。
     * 第 1 引数の $content には LogFormat オブジェクトで書式化された結果の文字列が指定されます。
     * 成功時に true を返します。
     *
     * @param string $content 出力するログの内容
     * @param int $time ログの発生時刻 (Unix time)
     * @param int $level ログレベル
     * @return bool 書き込みに成功した場合に true
     */
    public function write(string $content, int $time, int $level): bool;
}
