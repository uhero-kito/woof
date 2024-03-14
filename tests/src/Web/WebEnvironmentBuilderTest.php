<?php

namespace Woof\Web;

use PHPUnit\Framework\TestCase;
use Woof\Http\HeaderParser;
use Woof\Http\HttpDateFormat;
use Woof\System\FixedClock;
use Woof\System\VariablesBuilder;
use Woof\Web\Session\FileSessionContainer;
use Woof\Web\Session\SessionStorageBuilder;

/**
 * @coversDefaultClass Woof\Web\WebEnvironmentBuilder
 */
class WebEnvironmentBuilderTest extends TestCase
{
    /**
     * テスト用ディレクトリのパスです。
     *
     * @var string
     */
    const TMP_DIR = TEST_DATA_DIR . "/Web/WebEnvironmentBuilder/tmp";

    /**
     * SessionStorage の設定・存在確認・取得が正しく機能することを確認します。
     *
     * @covers ::setSessionStorage
     * @covers ::hasSessionStorage
     * @covers ::getSessionStorage
     */
    public function testSessionStorage(): void
    {
        $ss  = (new SessionStorageBuilder())
            ->setSessionContainer(new FileSessionContainer(self::TMP_DIR))
            ->setKey("test_key")
            ->build();
        $obj = new WebEnvironmentBuilder();
        $this->assertFalse($obj->hasSessionStorage());
        $this->assertSame($obj, $obj->setSessionStorage($ss));
        $this->assertTrue($obj->hasSessionStorage());
        $this->assertSame($ss, $obj->getSessionStorage());
    }

    /**
     * HeaderParser の設定・存在確認・取得が正しく機能することを確認します。
     *
     * @covers ::setHeaderParser
     * @covers ::hasHeaderParser
     * @covers ::getHeaderParser
     */
    public function testHeaderParser(): void
    {
        $hp  = new HeaderParser([], [], new HttpDateFormat(new FixedClock(1555555555)));
        $obj = new WebEnvironmentBuilder();
        $this->assertFalse($obj->hasHeaderParser());
        $this->assertSame($obj, $obj->setHeaderParser($hp));
        $this->assertTrue($obj->hasHeaderParser());
        $this->assertSame($hp, $obj->getHeaderParser());
    }

    /**
     * 必要な情報が設定された状態で、正しく WebEnvironment インスタンスが構築されることを確認します。
     *
     * @covers ::build
     */
    public function testBuild(): void
    {
        $server = [
            "HTTP_HOST"   => "www.example.com",
            "REMOTE_ADDR" => "127.0.0.1",
            "REQUEST_URI" => "/",
        ];
        $var = (new VariablesBuilder())
            ->setServer($server)
            ->build();
        $tmpdir = self::TMP_DIR;
        $obj    = (new WebEnvironmentBuilder())
            ->setConfigDir($tmpdir)
            ->setResourcesDir($tmpdir)
            ->setVariables($var)
            ->build();
        $this->assertInstanceOf(WebEnvironment::class, $obj);
    }
}
