<?php

namespace Woof\Http\Response;

/**
 * レスポンスボディを持たない HTTP レスポンスがダミーとして保持するための Body の実装です。
 */
class EmptyBody implements Body
{
    /**
     * 外部からのインスタンス生成を禁止することで getInstance() の使用を強制します。
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * 唯一の EmptyBody インスタンスを取得します。
     *
     * @return EmptyBody EmptyBody インスタンス
     */
    public static function getInstance(): self
    {
        // @codeCoverageIgnoreStart
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        // @codeCoverageIgnoreEnd
        return $instance;
    }

    /**
     * 常に 0 を返します。
     *
     * @return int コンテンツのバイト数 (0)
     */
    public function getContentLength(): int
    {
        return 0;
    }

    /**
     * 常に空文字列を返します。
     *
     * @return string 空文字列
     */
    public function getContentType(): string
    {
        return "";
    }

    /**
     * 常に空文字列を返します。
     *
     * @return string 空文字列
     */
    public function getOutput(): string
    {
        return "";
    }

    /**
     * 何も出力せず、常に true を返します。
     *
     * @return bool 常に true
     */
    public function sendOutput(): bool
    {
        return true;
    }
}
