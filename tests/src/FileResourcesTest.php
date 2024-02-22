<?php

namespace Woof;

use PHPUnit\Framework\TestCase;
use Woof\FileResources;

/**
 * @coversDefaultClass Woof\FileResources
 */
class FileResourcesTest extends TestCase
{
    /**
     * テストデータが配置されているディレクトリのパスです。
     *
     * @var string
     */
    const TEST_DIR = TEST_DATA_DIR . "/FileResources/subjects";

    /**
     * 指定したキーに該当するファイルの内容が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::get
     */
    public function testGet(): void
    {
        $tmpdir   = self::TEST_DIR;
        $obj      = new FileResources($tmpdir);
        $expected = file_get_contents("{$tmpdir}/test01/sample.txt");
        $this->assertSame($expected, $obj->get("test01/sample.txt"));
    }

    /**
     * 存在しないファイルを指定した場合に ResourceNotFoundException がスローされることを確認します。
     *
     * @covers ::__construct
     * @covers ::get
     */
    public function testGetFail(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $obj = new FileResources(self::TEST_DIR);
        $obj->get("test02/notfound.txt");
    }

    /**
     * 指定したファイルが存在するかどうかを正しく判定できることを確認します。
     *
     * @covers ::__construct
     * @covers ::contains
     */
    public function testContains(): void
    {
        $obj = new FileResources(self::TEST_DIR);
        $this->assertTrue($obj->contains("test01/sample.txt"));
        $this->assertFalse($obj->contains("test03/aaaa.txt"));
    }

    /**
     * 指定された相対パスが正しい絶対パスに変換されることを確認します。
     *
     * @covers ::__construct
     * @covers ::formatPath
     */
    public function testFormatPath(): void
    {
        $tmpdir = self::TEST_DIR;
        $obj    = new FileResources($tmpdir);
        $this->assertSame("{$tmpdir}/test03/xxxx.html", $obj->formatPath("test03/xxxx.html"));
    }
}
