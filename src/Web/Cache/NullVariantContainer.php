<?php

namespace Woof\Web\Cache;

use LogicException;

/**
 * キャッシュ機能を無効化するためのダミーの VariantContainer の実装です。
 * Null Object パターンとして機能し、すべての操作に対してキャッシュが存在しないものとして振る舞います。
 */
class NullVariantContainer implements VariantContainer
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
     * このクラスの唯一のインスタンスを取得します。
     *
     * @return NullVariantContainer このクラスの唯一のインスタンス
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

    /**
     * 指定された ID のバリアントが存在するかどうかを判定します。
     * 常にキャッシュが存在しないものとして false を返します。
     *
     * @param string $id      バリアント of ID
     * @param int    $maxAge キャッシュの有効期限 (秒)
     * @return bool 常に false
     */
    public function contains(string $id, int $maxAge): bool
    {
        return false;
    }

    /**
     * 指定された ID のバリアントを読み込みます。
     * このコンテナはロード操作をサポートしないため、常に LogicException を常にスローします。
     *
     * @param string $id 対象のバリアントの ID
     * @return Variant 読み込まれたバリアント
     * @throws LogicException 常にスローされます
     */
    public function load(string $id): Variant
    {
        throw new LogicException("This VariantContainer does not support load operation. ID: '{$id}'");
    }

    /**
     * 指定されたコンテンツをバリアントとして保存します。
     * 何も処理を行わず、常に false を返します。
     *
     * @param string $id      保存するバリアントの ID
     * @param string $content 保存するコンテンツ内容
     * @return bool 常に false
     */
    public function save(string $id, string $content): bool
    {
        return false;
    }

    /**
     * 有効期限を過ぎた古いバリアントデータをこの VariantContainer から一括で削除します。
     * 何も処理を行わず、常に 0 を返します。
     *
     * @param int $maxAge キャッシュの有効期限 (秒)
     * @return int 常に 0
     */
    public function cleanExpiredVariants(int $maxAge): int
    {
        return 0;
    }
}
