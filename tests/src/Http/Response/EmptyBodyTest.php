<?php

namespace Woof\Http\Response;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\Response\EmptyBody
 */
class EmptyBodyTest extends TestCase
{
    /**
     * コンテンツ長として 0 が返されることを確認します。
     *
     * @covers ::getContentLength
     */
    public function testGetContentLength(): void
    {
        $obj = EmptyBody::getInstance();
        $this->assertSame(0, $obj->getContentLength());
    }

    /**
     * Content-Type として空文字列が返されることを確認します。
     *
     * @covers ::getContentType
     */
    public function testGetContentType(): void
    {
        $obj = EmptyBody::getInstance();
        $this->assertSame("", $obj->getContentType());
    }

    /**
     * 出力内容として空文字列が返されることを確認します。
     *
     * @covers ::getOutput
     */
    public function testGetOutput(): void
    {
        $obj = EmptyBody::getInstance();
        $this->assertSame("", $obj->getOutput());
    }

    /**
     * 送信処理を実行しても何も出力されず、true が返されることを確認します。
     *
     * @covers ::sendOutput
     */
    public function testSendOutput(): void
    {
        $obj = EmptyBody::getInstance();
        $this->expectOutputString("");
        $this->assertTrue($obj->sendOutput());
    }

    /**
     * 常に同一のインスタンスが返されることを確認します。
     *
     * @covers ::getInstance
     */
    public function testGetInstance(): void
    {
        $obj1 = EmptyBody::getInstance();
        $obj2 = EmptyBody::getInstance();
        $this->assertInstanceOf(EmptyBody::class, $obj1);
        $this->assertSame($obj1, $obj2);
    }
}
