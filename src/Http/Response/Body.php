<?php

namespace Woof\Http\Response;

/**
 * HTTP レスポンスのボディ部分を表現するインタフェースです。
 */
interface Body
{
    /**
     * レスポンスボディの本体を文字列として取得します。
     *
     * @return string レスポンスボディの文字列
     */
    public function getOutput(): string;

    /**
     * レスポンスボディの本体を直接クライアントに送信 (出力) します。
     *
     * @return bool 送信に成功した場合は true
     */
    public function sendOutput(): bool;

    /**
     * このレスポンスの Content-Type ヘッダーの値を返します。
     *
     * @return string Content-Type の値 (例: "application/json", "text/html; charset=UTF-8" など)
     */
    public function getContentType(): string;

    /**
     * このレスポンスの Content-Length ヘッダーの値を返します。
     *
     * @return int コンテンツのバイト数
     */
    public function getContentLength(): int;
}
