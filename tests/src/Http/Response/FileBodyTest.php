<?php

namespace Woof\Http\Response;

use PHPUnit\Framework\TestCase;
use Woof\System\FileSystemException;

/**
 * @coversDefaultClass Woof\Http\Response\FileBody
 */
class FileBodyTest extends TestCase
{
    /**
     * テストデータが配置されるディレクトリのパスです。
     *
     * @var string
     */
    const DATA_DIR = TEST_DATA_DIR . "/Http/Response/FileBody";

    /**
     * テスト用の FileBody インスタンスを生成して返します。
     *
     * @return FileBody テスト用のインスタンス
     */
    private function getTestObject(): FileBody
    {
        return new FileBody(self::DATA_DIR . "/sample.txt", "text/plain");
    }

    /**
     * 存在しないファイルを指定してインスタンスを生成しようとした際に FileSystemException がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructFailByFileNotFound(): void
    {
        $this->expectException(FileSystemException::class);
        new FileBody(self::DATA_DIR . "/notfound.txt", "text/plain");
    }

    /**
     * 対象ファイルの内容が文字列として正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getOutput
     */
    public function testGetOutput(): void
    {
        $obj      = $this->getTestObject();
        $expected = file_get_contents(self::DATA_DIR . "/sample.txt");
        $this->assertSame($expected, $obj->getOutput());
    }

    /**
     * 対象ファイルの内容が正しく出力され、true が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::sendOutput
     */
    public function testSendOutput(): void
    {
        $obj      = $this->getTestObject();
        $expected = file_get_contents(self::DATA_DIR . "/sample.txt");
        $this->expectOutputString($expected);
        $this->assertTrue($obj->sendOutput());
    }

    /**
     * 指定した Content-Type が正しく取得できることを確認します。
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
     * 対象ファイルのサイズが正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getContentLength
     */
    public function testGetContentLength(): void
    {
        $obj = $this->getTestObject();
        $this->assertSame(446, $obj->getContentLength());
    }
}
