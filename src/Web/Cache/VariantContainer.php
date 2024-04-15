<?php

namespace Woof\Web\Cache;

use LogicException;

/**
 * キャッシュされたバリアントの永続化と読み込みを行うインタフェースです。
 */
interface VariantContainer
{
    /**
     * 指定された ID のバリアントが存在するかどうかを判定します。
     * 引数の ID が存在し、かつ有効期限内の場合のみ true を返します。
     *
     * @param string $id     バリアントの ID
     * @param int    $maxAge キャッシュの有効期限 (秒)
     * @return bool 有効なバリアントが存在する場合のみ true
     */
    public function contains(string $id, int $maxAge): bool;

    /**
     * 指定された ID のバリアントを読み込みます。
     *
     * @param string $id 対象のバリアントの ID
     * @return Variant 読み込まれたバリアント
     * @throws LogicException バリアントが存在しない場合
     */
    public function load(string $id): Variant;

    /**
     * 指定されたコンテンツをバリアントとして保存します。
     *
     * @param string $id      保存するバリアントの ID
     * @param string $content 保存するコンテンツ内容
     * @return bool 保存に成功した場合は true
     */
    public function save(string $id, string $content): bool;

    /**
     * 有効期限を過ぎた古いバリアントデータをこの VariantContainer から一括で削除します。
     *
     * @param int $maxAge キャッシュの有効期限 (秒)
     * @return int 削除されたレコード (ファイル) の数
     */
    public function cleanExpiredVariants(int $maxAge): int;
}
