<?php

namespace Woof\System;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\System\ArrayRandom
 */
class ArrayRandomTest extends TestCase
{
    /**
     * コンストラクタ引数に空の配列を指定した場合、InvalidArgumentException をスローすることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructFailByEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ArrayRandom([]);
    }

    /**
     * next() を実行するたびに指定された配列の中身を順番に参照することを確認します。
     *
     * @covers ::__construct
     * @covers ::next
     */
    public function testNext(): void
    {
        $seq      = [1, 5, 3];
        $expected = [1, 5, 3, 1, 5, 3, 1, 5, 3, 1];
        $obj      = new ArrayRandom($seq);
        $result   = [];

        for ($i = 0; $i < 10; $i++) {
            $result[] = $obj->next();
        }
        $this->assertSame($expected, $result);
    }

    /**
     * コンストラクタ引数の配列内に範囲外の値が含まれていた場合に LogicException をスローすることを確認します。
     *
     * @covers ::__construct
     * @covers ::next
     */
    public function testNextFailByInvalidValue(): void
    {
        $this->expectException(LogicException::class);
        $seq = [1, 4, -2, 5, 3];
        $obj = new ArrayRandom($seq);
        for ($i = 0; $i < 5; $i++) {
            $obj->next();
        }
    }
}
