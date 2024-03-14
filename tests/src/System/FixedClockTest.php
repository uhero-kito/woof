<?php

namespace Woof\System;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\System\FixedClock
 */
class FixedClockTest extends TestCase
{
    /**
     * コンストラクタ引数に指定された整数値が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getTime
     */
    public function testGetTime()
    {
        $obj = new FixedClock(1555555555);
        $this->assertSame(1555555555, $obj->getTime());
    }
}
