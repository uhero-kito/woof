<?php

namespace Woof\Http\Response;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\Response\TextBody
 */
class TextBodyTest extends TestCase
{
    /**
     * テスト用の文字列データです。
     *
     * @var string
     */
    const TEST_STRING = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.";

    /**
     * テスト用の TextBody インスタンスを生成して返します。
     *
     * @return TextBody テスト用のインスタンス
     */
    private function getTestObject(): TextBody
    {
        return new TextBody(self::TEST_STRING, "text/plain");
    }

    /**
     * 設定した文字列が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getOutput
     */
    public function testGetOutput(): void
    {
        $obj = $this->getTestObject();
        $this->assertSame(self::TEST_STRING, $obj->getOutput());
    }

    /**
     * 設定した文字列が正しく出力され、true が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::sendOutput
     */
    public function testSendOutput(): void
    {
        $this->expectOutputString(self::TEST_STRING);
        $obj = $this->getTestObject();
        $this->assertTrue($obj->sendOutput());
    }

    /**
     * 設定した Content-Type が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getContentType
     */
    public function testGetContentType(): void
    {
        $obj = $this->getTestObject();
        $this->assertSame("text/plain", $obj->getContentType());
    }

    /**
     * 設定した文字列のバイト数が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getContentLength
     */
    public function testGetContentLength(): void
    {
        $obj = $this->getTestObject();
        $this->assertSame(123, $obj->getContentLength());
    }
}
