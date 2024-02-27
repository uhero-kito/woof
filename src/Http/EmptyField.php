<?php

namespace Woof\Http;

/**
 * 指定されたヘッダーが存在しないことをあらわす空の HeaderField の実装です。
 *
 * ヘッダー取得時に値が存在しなかった場合の Null Object として機能します。
 */
class EmptyField implements HeaderField
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
     * 常に空文字列を返します。
     *
     * @return string 空文字列
     */
    public function format(): string
    {
        return "";
    }

    /**
     * 常に空文字列を返します。
     *
     * @return string 空文字列
     */
    public function getName(): string
    {
        return "";
    }

    /**
     * 常に null を返します。
     *
     * @return mixed 常に null
     */
    public function getValue()
    {
        return null;
    }

    /**
     * 唯一の EmptyField インスタンスを取得します。
     *
     * @return EmptyField EmptyField インスタンス
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
}
