<?php

namespace Woof\Web\Session;

/**
 * セッションデータの保存・読み込み・削除を行うためのインタフェースです。
 */
interface SessionContainer
{
    /**
     * 指定された ID のセッションが存在するかどうかを判定します。
     * 引数の ID が存在し、かつ有効期限内の場合のみ true を返します。
     *
     * @param string $id セッション ID
     * @param int $maxAge セッションの生存期間 (秒数)
     * @return bool セッションが存在する場合のみ true
     */
    public function contains(string $id, int $maxAge): bool;

    /**
     * 指定された ID のセッションデータを読み込みます。
     * セッションが存在しない場合・有効期限が切れている場合・フォーマットが不正な場合は空の配列を返します。
     *
     * @param string $id セッション ID
     * @return array セッションデータの連想配列 (存在しない場合は空の配列)
     */
    public function load(string $id): array;

    /**
     * 指定されたセッションデータを保存します。
     *
     * @param string $id セッション ID
     * @param array $data 保存するセッションデータの連想配列
     * @return bool 書き込みに成功した場合に true
     */
    public function save(string $id, array $data): bool;

    /**
     * 有効期限切れのセッションをこの SessionContainer から削除します。
     *
     * @param int $maxAge セッションの生存期間 (秒数)
     * @return int 削除されたセッションの件数
     */
    public function cleanExpiredSessions(int $maxAge): int;
}
