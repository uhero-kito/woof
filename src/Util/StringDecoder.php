<?php

namespace Woof\Util;

/**
 * 文字列を配列にパース (変換) するためのインタフェースです。
 *
 * INI や JSON といった特定のデータフォーマットの文字列を配列に変換する際に使用します。
 * また、このインタフェースを独自に実装することで、任意のフォーマットでシリアライズされたデータを配列に変換する、
 * 独自のデコーダを作成することも可能です。
 */
interface StringDecoder
{
    /**
     * 指定された文字列を配列に変換します。
     *
     * @param string $src パース対象の文字列
     * @return array 変換された配列
     */
    public function parse(string $src): array;
}
