<?php

namespace Woof\System;

/**
 * 基準となる Clock から指定された秒数だけ進ませた (または遅らせた) 時刻を出力する Clock の実装です。
 * タイムゾーンに対応したシステムを構築する場合などに使用します。
 */
class ShiftedClock implements Clock
{
    /**
     * 基準の時刻から進ませる (または遅らせる) 秒数です。
     *
     * @var int
     */
    private $diff;

    /**
     * 基準となる時刻を出力する Clock です。
     *
     * @var Clock
     */
    private $original;

    /**
     * 進ませる (または遅らせる) 秒数およびその基準の時刻を提供する Clock を指定して、新しいインスタンスを作成します。
     * 第 2 引数を省略した場合は DefaultClock のインスタンスが適用されます。
     *
     * @param int $diff 基準の時刻から進ませる (正の値) または遅らせる (負の値) 秒数
     * @param Clock|null $original 基準となる時刻を提供する Clock オブジェクト
     */
    public function __construct(int $diff, Clock $original = null)
    {
        $this->diff     = (int) $diff;
        $this->original = ($original instanceof Clock) ? $original : DefaultClock::getInstance();
    }

    /**
     * このオブジェクトの現在時刻を返します。
     *
     * @return int 基準の時刻から指定された秒数だけシフトされた Unix Time
     */
    public function getTime(): int
    {
        return $this->original->getTime() + $this->diff;
    }

    /**
     * 基準となる Clock オブジェクトを返します。
     *
     * @return Clock 基準として保持している Clock オブジェクト
     */
    public function getOriginal(): Clock
    {
        return $this->original;
    }
}
