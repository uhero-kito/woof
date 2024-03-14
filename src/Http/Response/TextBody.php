<?php

namespace Woof\Http\Response;

/**
 * 指定された文字列を直接レスポンスボディとして送信する、シンプルな Body の実装です。
 */
class TextBody implements Body
{
    /**
     * レスポンスとして出力する文字列データです。
     *
     * @var string
     */
    private $output;

    /**
     * 出力する Content-Type の値です。
     *
     * @var string
     */
    private $contentType;

    /**
     * 指定されたレスポンスボディを持つ新しい TextBody を生成します。
     *
     * @param string $output 送信するレスポンスボディ文字列
     * @param string $contentType Content-Type の値 (省略した場合は "text/html; charset=UTF-8")
     */
    public function __construct($output, $contentType = "text/html; charset=UTF-8")
    {
        $this->output      = $output;
        $this->contentType = $contentType;
    }

    /**
     * 保持している文字列データを取得します。
     *
     * @return string レスポンスボディの文字列
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * 保持している文字列データをクライアントに送信します。
     *
     * @return bool 常に true
     */
    public function sendOutput(): bool
    {
        echo $this->output;
        return true;
    }

    /**
     * 設定された Content-Type の値を返します。
     *
     * @return string Content-Type の値
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * 文字列データのバイト数を返します。
     *
     * @return int コンテンツのバイト数
     */
    public function getContentLength(): int
    {
        return strlen($this->output);
    }
}
