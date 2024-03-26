<?php

namespace Woof\Web\Session;

use Generator;

/**
 * SessionContainer が共通で利用する処理を補助する、ステートレスなヘルパークラスです。
 *
 * セッションデータのシリアライズや、特定のディレクトリ内のキー一覧からセッションファイルのみを抽出するなどのドメインロジックを提供します。
 */
class SessionContainerHelper
{
    /**
     * 連想配列を独自のセッションフォーマット文字列にシリアライズします。
     *
     * @param array $data シリアライズ対象の連想配列
     * @return string シリアライズされた文字列
     */
    public function serialize(array $data): string
    {
        $result = "";
        foreach ($data as $key => $value) {
            $serialized = serialize($value);
            $result     .= "{$key}|{$serialized}";
        }
        return $result;
    }

    /**
     * キーの一覧の中から、セッションデータの対象 (ファイル名が "sess_" から始まるもの) のみを抽出します。
     *
     * @param iterable<string> $keys キーの配列またはイテレータ
     * @return Generator<string>
     */
    public function filterSessionKeys(iterable $keys): Generator
    {
        foreach ($keys as $key) {
            if (substr(basename($key), 0, 5) === "sess_") {
                yield $key;
            }
        }
    }
}
