<?php

namespace Woof\Util;

/**
 * JSON 形式の文字列を配列に変換する StringDecoder の実装です。
 *
 * このクラスは直接インスタンス化することはできません。
 * getInstance() メソッドを使用してインスタンスを取得してください。
 */
class JsonDecoder implements StringDecoder
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
     * JSON 形式の文字列を連想配列に変換して返します。
     * パースに失敗した場合や、結果が配列でなかった場合は空の配列を返します。
     *
     * @param string $src パース対象の JSON 形式の文字列
     * @return array 変換された配列 (失敗時は空の配列)
     */
    public function parse(string $src): array
    {
        $result = json_decode($src, true);
        return is_array($result) ? $result : [];
    }

    /**
     * このクラスの唯一のインスタンスを取得します。
     *
     * @return JsonDecoder JsonDecoder インスタンス
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
