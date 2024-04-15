<?php

namespace Woof\Web\Cache;

use Generator;

/**
 * VariantContainer が共通で利用する処理を補助する、ステートレスなヘルパークラスです。
 *
 * キャッシュキーの生成・有効期限の判定・特定のキーの一覧からキャッシュファイルのみを抽出するなどのドメインロジックを提供します。
 */
class VariantContainerHelper
{
    /**
     * バリアント ID と末尾文字列からファイル名 (またはキー名) を生成します。
     *
     * @param string $id     バリアント ID
     * @param string $suffix ファイル名 (またはキー名) に付与する末尾文字列
     * @return string        結合されたファイル名 (またはキー名)
     */
    public function formatFilename(string $id, string $suffix): string
    {
        return $id . $suffix;
    }

    /**
     * キーの一覧の中から、指定された末尾文字列を持つキャッシュ対象のキーのみを抽出します。
     *
     * @param iterable<string> $keys   キーの配列またはイテレータ
     * @param string           $suffix 対象とする末尾文字列
     * @return Generator<string>
     */
    public function filterVariantKeys(iterable $keys, string $suffix): Generator
    {
        foreach ($keys as $key) {
            if ($suffix === "" || substr($key, -strlen($suffix)) === $suffix) {
                yield $key;
            }
        }
    }

    /**
     * キャッシュデータが有効期限切れであるかどうかを判定します。
     *
     * @param int $mtime  キャッシュの最終更新日時
     * @param int $maxAge キャッシュの生存期間 (秒)
     * @param int $now    現在の基準時刻
     * @return bool       有効期限切れの場合は true
     */
    public function checkExpired(int $mtime, int $maxAge, int $now): bool
    {
        $limit = $mtime + $maxAge;
        return 0 < $limit && $limit < $now;
    }
}
