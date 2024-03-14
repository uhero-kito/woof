<?php

namespace Woof\Http;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\TextField
 */
class TextFieldTest extends TestCase
{
    /**
     * 設定した値がそのまま文字列として返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::format
     */
    public function testFormat(): void
    {
        $obj = new TextField("Pragma", "no-cache");
        $this->assertSame("no-cache", $obj->format());
    }

    /**
     * 設定したヘッダー名が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getName
     */
    public function testGetName(): void
    {
        $obj = new TextField("Pragma", "no-cache");
        $this->assertSame("Pragma", $obj->getName());
    }

    /**
     * 設定した値が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getValue
     */
    public function testGetValue(): void
    {
        $obj = new TextField("Pragma", "no-cache");
        $this->assertSame("no-cache", $obj->getValue());
    }
}
