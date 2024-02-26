<?php

namespace Woof;

/**
 * バッチ処理などの CLI 環境での利用を前提とした Environment の実装です。
 *
 * このクラスをインスタンス化するには DefaultEnvironmentBuilder を使用してください。
 */
class DefaultEnvironment extends Environment
{
    /**
     * 外部からのインスタンス生成を禁止することで DefaultEnvironmentBuilder を通じたインスタンス化を強制します。
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * このオブジェクトの設定内容を元に DefaultEnvironment インスタンスを生成します。
     *
     * @param DefaultEnvironmentBuilder $builder 各種設定が行われたビルダー
     * @return Environment 生成された DefaultEnvironment オブジェクト
     * @throws LogicException 初期化に失敗した場合 (Config が未指定の場合など)
     */
    public static function newInstance(DefaultEnvironmentBuilder $builder): self
    {
        $instance = new self();
        $instance->init($builder);
        return $instance;
    }
}
