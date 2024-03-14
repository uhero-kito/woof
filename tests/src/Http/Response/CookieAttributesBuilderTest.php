<?php

namespace Woof\Http\Response;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\Response\CookieAttributesBuilder
 */
class CookieAttributesBuilderTest extends TestCase
{
    /**
     * ドメイン名の設定と取得が正しく機能することを確認します。
     *
     * @covers ::setDomain
     * @covers ::getDomain
     */
    public function testSetDomainAndGetDomain(): void
    {
        $obj = new CookieAttributesBuilder();
        $this->assertSame($obj, $obj->setDomain("example.com"));
        $this->assertSame("example.com", $obj->getDomain());
    }

    /**
     * パスの設定と取得が正しく機能することを確認します。
     *
     * @covers ::setPath
     * @covers ::getPath
     */
    public function testSetPathAndGetPath(): void
    {
        $obj = new CookieAttributesBuilder();
        $this->assertSame($obj, $obj->setPath("/test"));
        $this->assertSame("/test", $obj->getPath());
    }

    /**
     * 有効期限の設定と取得が正しく機能することを確認します。
     *
     * @covers ::setExpires
     * @covers ::getExpires
     */
    public function testSetExpiresAndGetExpires(): void
    {
        $obj = new CookieAttributesBuilder();
        $this->assertSame($obj, $obj->setExpires(1555555555));
        $this->assertSame(1555555555, $obj->getExpires());
    }

    /**
     * Secure フラグの設定と取得が正しく機能することを確認します。
     *
     * @covers ::setSecure
     * @covers ::isSecure
     */
    public function testSetSecureAndIsSecure(): void
    {
        $obj = new CookieAttributesBuilder();
        $this->assertSame($obj, $obj->setSecure(true));
        $this->assertTrue($obj->isSecure());
    }

    /**
     * HttpOnly フラグの設定と取得が正しく機能することを確認します。
     *
     * @covers ::setHttpOnly
     * @covers ::isHttpOnly
     */
    public function testSetHttpOnlyAndIsHttpOnly(): void
    {
        $obj = new CookieAttributesBuilder();
        $this->assertSame($obj, $obj->setHttpOnly(true));
        $this->assertTrue($obj->isHttpOnly());
    }

    /**
     * SameSite 属性の設定と取得が正しく機能し、大文字・小文字が適切に整形されることを確認します。
     *
     * @param string $value 設定する値
     * @param string $expected 期待される整形後の値
     * @dataProvider provideTestSetSameSiteAndGetSameSite
     * @covers ::setSameSite
     * @covers ::getSameSite
     */
    public function testSetSameSiteAndGetSameSite(string $value, string $expected): void
    {
        $obj = new CookieAttributesBuilder();
        $this->assertSame($obj, $obj->setSameSite($value));
        $this->assertSame($expected, $obj->getSameSite());
    }

    /**
     * testSetSameSiteAndGetSameSite() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestSetSameSiteAndGetSameSite(): array
    {
        return [
            ["", ""],
            ["STRICT", "Strict"],
            ["lax", "Lax"],
            ["None", "None"],
        ];
    }

    /**
     * 不正な SameSite 属性の値を設定しようとした際に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::setSameSite
     */
    public function testSetSameSiteFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CookieAttributesBuilder())->setSameSite("Invalid");
    }

    /**
     * CookieAttributes インスタンスが正しく構築されることを確認します。
     *
     * @covers ::build
     */
    public function testBuild(): void
    {
        $obj = new CookieAttributesBuilder();
        $this->assertInstanceOf(CookieAttributes::class, $obj->build());
    }
}
