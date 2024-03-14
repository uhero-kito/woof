<?php

namespace Woof\Http;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\ContentDisposition
 */
class ContentDispositionTest extends TestCase
{
    /**
     * 常に "Content-Disposition" というヘッダー名が取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getName
     */
    public function testGetName(): void
    {
        $obj = new ContentDisposition("sample.png");
        $this->assertSame("Content-Disposition", $obj->getName());
    }

    /**
     * 設定したファイル名 (未指定の場合は空文字列) が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getValue
     */
    public function testGetValue(): void
    {
        $obj1 = new ContentDisposition();
        $this->assertSame("", $obj1->getValue());
        $obj2 = new ContentDisposition("test image.jpg");
        $this->assertSame("test image.jpg", $obj2->getValue());
    }

    /**
     * ファイル名の有無に応じて適切にフォーマットされることと、ファイル名が正しく URL エンコードされることを確認します。
     *
     * @covers ::__construct
     * @covers ::format
     */
    public function testFormat(): void
    {
        $obj1 = new ContentDisposition();
        $this->assertSame("attachment", $obj1->format());
        $obj2 = new ContentDisposition("sample image.jpg");
        $this->assertSame("attachment; filename=\"sample%20image.jpg\"", $obj2->format());
    }
}
