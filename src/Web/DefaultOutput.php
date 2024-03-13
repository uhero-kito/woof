<?php

namespace Woof\Web;

use Woof\Http\Response;

/**
 * 標準の PHP 関数 (header や setcookie など) を使用して、
 * HTTP レスポンスをクライアントに送信する Output インタフェースの標準実装です。
 *
 * @codeCoverageIgnore
 */
class DefaultOutput implements Output
{
    /**
     * 指定された Response オブジェクトの内容を、
     * ステータスコード, HTTP ヘッダー, Cookie, レスポンスボディとしてクライアントに出力します。
     *
     * @param Response $response 送信する HTTP レスポンス
     * @return bool 出力処理が完了した場合に true
     */
    public function send(Response $response)
    {
        header($response->getStatus()->format());
        foreach ($response->getHeaderList() as $header) {
            $name  = $this->formatHeaderName($header->getName());
            $value = $header->format();
            header("{$name}: {$value}");
        }
        foreach ($response->getCookieList() as $cookie) {
            $name     = $cookie->getName();
            $value    = $cookie->getValue();
            $expires  = $cookie->getExpires();
            $path     = $cookie->getPath();
            $domain   = $cookie->getDomain();
            $secure   = $cookie->isSecure();
            $httpOnly = $cookie->isHttpOnly();
            setcookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
        }
        return $response->getBody()->sendOutput();
    }

    /**
     * ヘッダー名を書式化します。 (例: "content-type" を "Content-Type" に変換します)
     *
     * @param string $name 元のヘッダー名
     * @return string 書式化されたヘッダー名
     */
    private function formatHeaderName(string $name): string
    {
        $parts = explode("-", $name);
        return implode("-", array_map("ucfirst", $parts));
    }
}
