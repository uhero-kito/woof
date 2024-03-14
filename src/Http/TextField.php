<?php

namespace Woof\Http;

/**
 * 単一の文字列を値として持つ、最も基本的な HeaderField の実装です。
 */
class TextField implements HeaderField
{
    /**
     * ヘッダー名をあらわします。
     *
     * @var string
     */
    private $name;

    /**
     * ヘッダーの値をあらわします。
     *
     * @var string
     */
    private $value;

    /**
     * ヘッダー名と値を指定して TextField インスタンスを生成します。
     *
     * @param string $name ヘッダー名
     * @param string $value ヘッダーの値
     */
    public function __construct(string $name, string $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * 設定された文字列値をそのまま返します。
     *
     * @return string ヘッダー値の文字列
     */
    public function format(): string
    {
        return $this->value;
    }

    /**
     * 設定されたヘッダー名を返します。
     *
     * @return string ヘッダー名
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 設定された文字列値をそのまま返します。
     *
     * @return string ヘッダーの値
     */
    public function getValue()
    {
        return $this->value;
    }
}
