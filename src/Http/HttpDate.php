<?php

namespace Woof\Http;

/**
 * HTTP-date 形式 (RFC 822 準拠の時刻文字列) の値を持つヘッダーフィールドです。
 *
 * Last-Modified や Expires などのヘッダーを取り扱うために使用します。
 */
class HttpDate implements HeaderField
{
    /**
     * 文字列のパースおよびフォーマットを行うためのオブジェクトです。
     *
     * @var HttpDateFormat
     */
    private $format;

    /**
     * ヘッダー名をあらわします。
     *
     * @var string
     */
    private $name;

    /**
     * ヘッダーの値となる Unix time を保持します。
     *
     * @var int
     */
    private $time;

    /**
     * 指定されたヘッダー名および時刻を持つ HttpDate オブジェクトを生成します。
     * 第 3 引数の HttpDateFormat は、デバッグやテストのために現在時刻を調整したい場合のみ指定してください。
     *
     * @param string $name ヘッダー名
     * @param int $time このシステムのタイムゾーンを基準とした Unix time
     * @param HttpDateFormat|null $format 任意の HttpDateFormat (通常は未指定で構いません)
     */
    public function __construct(string $name, int $time, HttpDateFormat $format = null)
    {
        $this->name   = $name;
        $this->time   = $time;
        $this->format = $format ?? new HttpDateFormat();
    }

    /**
     * 保持している Unix time を HTTP-date 形式 (GMT) の文字列として返します。
     *
     * @return string フォーマットされた HTTP-date 文字列
     */
    public function format(): string
    {
        return $this->format->format($this->time);
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
     * 設定された Unix time をそのまま返します。
     *
     * @return int ヘッダー値 (Unix time)
     */
    public function getValue()
    {
        return $this->time;
    }
}
