<?php

namespace Woof\System;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\System\ShiftedClock
 */
class ShiftedClockTest extends TestCase
{
    /**
     * 基準の Clock から指定された秒数だけシフトされた時刻が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getTime
     */
    public function testGetTime()
    {
        $original = new FixedClock(1555555555);
        $obj      = new ShiftedClock(-50000, $original);
        $this->assertSame(1555505555, $obj->getTime());
    }

    /**
     * コンストラクタで指定した基準の Clock オブジェクトが返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getOriginal
     */
    public function testGetOriginal()
    {
        $original = new FixedClock(1234567890);
        $obj      = new ShiftedClock(123, $original);
        $this->assertSame($original, $obj->getOriginal());
    }

    /**
     * コンストラクタ引数を省略した場合は DefaultClock のインスタンスが適用されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getOriginal
     */
    public function testGetOriginalWithoutArgument()
    {
        $obj = new ShiftedClock(123);
        $this->assertInstanceOf(DefaultClock::class, $obj->getOriginal());
    }
}
