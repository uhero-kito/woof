<?php

namespace Woof\System;

/**
 * mt_rand() を利用して次の乱数を取得する Random クラスの実装です。
 * このクラスが生成する乱数は暗号学的に安全ではありません。
 *
 * @codeCoverageIgnore
 */
class DefaultRandom implements Random
{
    /**
     * 外部からのインスタンス生成を禁止することで getInstance() の使用を強制します。
     */
    private function __construct()
    {
    }

    /**
     * 唯一の DefaultRandom インスタンスを返します。
     *
     * @return DefaultRandom このクラスの唯一のインスタンス
     */
    public static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * この実装は引数なしの mt_rand() の結果をそのまま返します。
     *
     * @return int mt_rand() によって生成された乱数
     */
    public function next(): int
    {
        return mt_rand();
    }
}
