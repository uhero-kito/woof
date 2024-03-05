<?php

namespace Woof\Http\Response;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\Response\Cookie
 */
class CookieTest extends TestCase
{
    /**
     * テスト用の CookieAttributes インスタンスです。
     *
     * @var CookieAttributes
     */
    private $attributes;

    /**
     * テスト用の CookieAttributes インスタンスを準備します。
     */
    protected function setUp(): void
    {
        $this->attributes = (new CookieAttributesBuilder())
            ->setDomain("example.com")
            ->setPath("/test")
            ->setExpires(1555555555)
            ->setSecure(true)
            ->setHttpOnly(true)
            ->setSameSite("Lax")
            ->build();
    }

    /**
     * 設定した Cookie の名前が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getName
     */
    public function testGetName(): void
    {
        $obj = new Cookie("username", "john");
        $this->assertSame("username", $obj->getName());
    }

    /**
     * 設定した Cookie の値が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getValue
     */
    public function testGetValue(): void
    {
        $obj = new Cookie("username", "john");
        $this->assertSame("john", $obj->getValue());
    }

    /**
     * 設定したドメイン名が正しく取得できることと、未設定の場合は空文字列が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getDomain
     */
    public function testGetDomain(): void
    {
        $obj1 = new Cookie("username", "john");
        $obj2 = new Cookie("username", "john", $this->attributes);
        $this->assertSame("", $obj1->getDomain());
        $this->assertSame("example.com", $obj2->getDomain());
    }

    /**
     * 設定したパスが正しく取得できることと、未設定の場合は空文字列が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getPath
     */
    public function testGetPath(): void
    {
        $obj1 = new Cookie("username", "john");
        $obj2 = new Cookie("username", "john", $this->attributes);
        $this->assertSame("", $obj1->getPath());
        $this->assertSame("/test", $obj2->getPath());
    }

    /**
     * 設定した有効期限が正しく取得できることと、未設定の場合は 0 が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getExpires
     */
    public function testGetExpires(): void
    {
        $obj1 = new Cookie("username", "john");
        $obj2 = new Cookie("username", "john", $this->attributes);
        $this->assertSame(0, $obj1->getExpires());
        $this->assertSame(1555555555, $obj2->getExpires());
    }

    /**
     * 設定した Secure フラグが正しく取得できることと、未設定の場合は false が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::isSecure
     */
    public function testIsSecure(): void
    {
        $obj1 = new Cookie("username", "john");
        $obj2 = new Cookie("username", "john", $this->attributes);
        $this->assertSame(false, $obj1->isSecure());
        $this->assertSame(true, $obj2->isSecure());
    }

    /**
     * 設定した HttpOnly フラグが正しく取得できることと、未設定の場合は false が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::isHttpOnly
     */
    public function testIsHttpOnly(): void
    {
        $obj1 = new Cookie("username", "john");
        $obj2 = new Cookie("username", "john", $this->attributes);
        $this->assertSame(false, $obj1->isHttpOnly());
        $this->assertSame(true, $obj2->isHttpOnly());
    }

    /**
     * 設定した SameSite 属性の値が正しく取得できることと、未設定の場合は空文字列が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::getSameSite
     */
    public function testGetSameSite(): void
    {
        $obj1 = new Cookie("username", "John");
        $obj2 = new Cookie("username", "John", $this->attributes);
        $this->assertSame("", $obj1->getSameSite());
        $this->assertSame("Lax", $obj2->getSameSite());
    }
}
