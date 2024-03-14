<?php

namespace Woof\System;

/**
 * システム時刻を返却する Clock の標準実装です。
 *
 * このクラスは PHP の time() 関数を利用して現在時刻を取得します。
 * 主に本番環境での利用を想定しています。
 *
 * @codeCoverageIgnore
 */
class DefaultClock implements Clock
{
    /**
     * 外部からのインスタンス生成を禁止することで getInstance() の使用を強制します。
     */
    private function __construct()
    {
    }

    /**
     * 唯一の DefaultClock インスタンスを返します。
     *
     * このメソッドは常に同一のインスタンスを返します。
     *
     * @return DefaultClock このクラスの唯一のインスタンス
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
     * 現在のシステム時刻を取得します。
     *
     * この実装は time() の結果 (Unix Time) をそのまま返します。
     *
     * @return int 現在時刻を表す整数値 (Unix Time)
     */
    public function getTime(): int
    {
        return time();
    }
}
