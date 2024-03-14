<?php

namespace Woof\Util;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Util\RawDataObject
 */
class RawDataObjectTest extends TestCase
{
    /**
     * コンストラクタで指定した値がそのまま取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::toValue
     */
    public function testToValue(): void
    {
        $arr = [
            "abc" => 123,
            "xyz" => "test",
        ];
        $obj = new RawDataObject($arr);
        $this->assertSame($arr, $obj->toValue());
    }
}
