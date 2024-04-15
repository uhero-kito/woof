<?php

namespace Woof\Web\Cache;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Web\Cache\Variant
 */
class VariantTest extends TestCase
{
    /**
     * 指定された値で初期化された Variant オブジェクトについて、各 getter から値が取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getId
     * @covers ::getContent
     * @covers ::getLastModified
     */
    public function testConstructAndGetters(): void
    {
        $id      = "1234567890abcdef";
        $content = "<html><body><h1>Hello, World!</h1></body></html>";
        $mtime   = 1555555555;
        $obj     = new Variant($id, $content, $mtime);

        $this->assertSame($id, $obj->getId());
        $this->assertSame($content, $obj->getContent());
        $this->assertSame($mtime, $obj->getLastModified());
    }

    /**
     * 不正な形式の ID を指定した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @param string $invalidId テスト対象の不正な ID
     *
     * @covers ::__construct
     * @dataProvider provideInvalidIds
     */
    public function testConstructThrowsExceptionForInvalidId(string $invalidId): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid variant ID format: '{$invalidId}'");
        new Variant($invalidId, "Test Content", 1555555555);
    }

    /**
     * 不正な ID のテストデータを提供します。
     * 許可されるのは半角小文字の英数字のみ ( \A[a-z0-9]+\z ) とします。
     *
     * @return array
     */
    public function provideInvalidIds(): array
    {
        return [
            "contains-uppercase"  => ["1234567890ABCDEF"],
            "contains-hyphen"     => ["abcdef-1234"],
            "contains-underscore" => ["abcdef_1234"],
            "contains-space"      => ["abcdef 1234"],
            "empty-string"        => [""],
            "contains-slash"      => ["abc/def"],
            "contains-newline"    => ["abc\ndef"],
        ];
    }
}
