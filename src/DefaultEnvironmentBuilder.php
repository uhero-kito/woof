<?php

namespace Woof;

/**
 * DefaultEnvironment を構築するためのビルダークラスです。
 */
class DefaultEnvironmentBuilder extends EnvironmentBuilder
{
    /**
     * 設定された内容に基づいて DefaultEnvironment インスタンスを生成します。
     *
     * @return DefaultEnvironment 生成された DefaultEnvironment オブジェクト
     */
    public function build(): DefaultEnvironment
    {
        return DefaultEnvironment::newInstance($this);
    }
}
