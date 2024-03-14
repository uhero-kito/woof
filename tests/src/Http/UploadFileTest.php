<?php

namespace Woof\Http;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\UploadFile
 */
class UploadFileTest extends TestCase
{
    /**
     * テストデータが配置されるディレクトリのパスです。
     *
     * @var string
     */
    const DATA_DIR = TEST_DATA_DIR . "/Http/UploadFile";

    /**
     * テスト用の UploadFile インスタンスです。
     *
     * @var UploadFile
     */
    private $object;

    /**
     * テスト用の UploadFile インスタンスを準備します。
     */
    protected function setUp(): void
    {
        $path         = self::DATA_DIR . "/tmp.txt";
        $size         = filesize($path);
        $this->object = new UploadFile("tmp.txt", $path, 0, $size);
    }

    /**
     * 添付ファイルの元のファイル名が正しく取得できることを確認します。
     *
     * @covers ::getName
     */
    public function testGetName(): void
    {
        $this->assertSame("tmp.txt", $this->object->getName());
    }

    /**
     * サーバー上に保管されているファイルのパスが正しく取得できることを確認します。
     *
     * @covers ::getPath
     */
    public function testGetPath(): void
    {
        $expected = self::DATA_DIR . "/tmp.txt";
        $this->assertSame($expected, $this->object->getPath());
    }

    /**
     * 設定したエラーコードが正しく取得できることを確認します。
     *
     * @covers ::getErrorCode
     */
    public function testGetErrorCode(): void
    {
        $this->assertSame(0, $this->object->getErrorCode());
    }

    /**
     * 設定したファイルサイズが正しく取得できることを確認します。
     *
     * @covers ::getSize
     */
    public function testGetSize(): void
    {
        $path     = self::DATA_DIR . "/tmp.txt";
        $expected = filesize($path);
        $this->assertSame($expected, $this->object->getSize());
    }

    /**
     * ファイルのコンテンツが文字列として正しく読み込めることを確認します。
     *
     * @covers ::getContents
     */
    public function testGetContents(): void
    {
        $path     = self::DATA_DIR . "/tmp.txt";
        $expected = file_get_contents($path);
        $this->assertSame($expected, $this->object->getContents());
    }

    /**
     * 指定されたパスにファイルが存在しない場合 getContents() が空文字列を返すことを確認します。
     *
     * @covers ::__construct
     * @covers ::getContents
     */
    public function testGetContentsReturnsNullIfNotFound(): void
    {
        $obj = new UploadFile("notfound.txt", self::DATA_DIR . "/notfound.txt", UPLOAD_ERR_NO_FILE, 0);
        $this->assertSame("", $obj->getContents());
    }
}
