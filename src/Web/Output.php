<?php

namespace Woof\Web;

use Woof\Http\Response;

/**
 * 構築された HTTP レスポンスをクライアントに送信 (出力) するためのインタフェースです。
 */
interface Output
{
    /**
     * 指定された HTTP レスポンスをクライアントに送信します。
     *
     * @param Response $response 送信する HTTP レスポンス
     * @return bool HTTP レスポンスが正常に送信された場合は true
     */
    public function send(Response $response);
}
