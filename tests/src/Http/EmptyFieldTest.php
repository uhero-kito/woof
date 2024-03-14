<?php

namespace Woof\Http;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\EmptyField
 */
class EmptyFieldTest extends TestCase
{
    /**
     * 空文字列が返されることを確認します。
     *
     * @covers ::getName
     */
    public function testGetName()
    {
        $obj = EmptyField::getInstance();
        $this->assertSame("", $obj->getName());
    }

    /**
     * null が返されることを確認します。
     *
     * @covers ::getValue
     */
    public function testGetValue()
    {
        $obj = EmptyField::getInstance();
        $this->assertNull($obj->getValue());
    }

    /**
     * 空文字列が返されることを確認します。
     *
     * @covers ::format
     */
    public function testFormat()
    {
        $obj = EmptyField::getInstance();
        $this->assertSame("", $obj->format());
    }

    /**
     * 常に同一のインスタンスが返されることを確認します。
     *
     * @covers ::getInstance
     */
    public function testGetInstance()
    {
        $obj1 = EmptyField::getInstance();
        $obj2 = EmptyField::getInstance();
        $this->assertInstanceOf(EmptyField::class, $obj1);
        $this->assertSame($obj1, $obj2);
    }
}
