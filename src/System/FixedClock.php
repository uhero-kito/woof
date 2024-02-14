<?php

namespace Woof\System;

/**
 * 常に固定の値を出力する Clock の実装です。
 * 時刻に依存した処理のテストにおいて、特定の時刻をシミュレートし、
 * 実行タイミングによるテスト結果のブレ (副作用) を排除するために使用します。
 */
class FixedClock implements Clock
{
    /**
     * 固定の現在時刻として保持する Unix time です。
     *
     * @var int
     */
    private $time;

    /**
     * 指定された Unix time を現在時刻とする FixedClock オブジェクトを生成します。
     *
     * @param int $time 固定の現在時刻として設定する Unix Time
     */
    public function __construct(int $time)
    {
        $this->time = (int) $time;
    }

    /**
     * 初期化時に指定された Unix time の値を返します。
     *
     * @return int 初期化時に設定された固定の Unix Time
     */
    public function getTime(): int
    {
        return $this->time;
    }
}
