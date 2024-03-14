<?php

namespace Woof\Web;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Web\Context
 */
class ContextTest extends TestCase
{
    /**
     * 指定されたパスが正しく基底パスとして取得・整形されることを確認します。
     *
     * @param string $path 入力するパス
     * @param string $expected 期待される基底パス
     * @covers ::__construct
     * @covers ::getRootPath
     * @dataProvider provideTestGetRootPath
     */
    public function testGetRootPath(string $path, string $expected): void
    {
        $obj = new Context($path);
        $this->assertSame($expected, $obj->getRootPath());
    }

    /**
     * testGetRootPath() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetRootPath(): array
    {
        return [
            ["", "/"],
            ["/", "/"],
            ["hoge/fuga", "/hoge/fuga"],
            ["/hoge/fuga/", "/hoge/fuga"],
        ];
    }

    /**
     * クエリパラメータがない場合、リンク先 URL が正しく書式化されることを確認します。
     *
     * @param string $path リンク先のパス
     * @param string $expected 期待される URL 文字列
     * @covers ::__construct
     * @covers ::formatHref
     * @dataProvider provideTestFormatPathWithoutQuery
     */
    public function testFormatPathWithoutQuery(string $path, string $expected): void
    {
        $obj = new Context("/base");
        $this->assertSame($expected, $obj->formatHref($path));
    }

    /**
     * testFormatPathWithoutQuery() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestFormatPathWithoutQuery(): array
    {
        return [
            ["", "/base/"],
            ["/", "/base/"],
            ["asdf/", "/base/asdf/"],
            ["/asdf/index.html", "/base/asdf/index.html"],
            ["https://www.example.com/xxxx/a.html", "https://www.example.com/xxxx/a.html"],
            ["//www.example.com/xxxx/a.html", "//www.example.com/xxxx/a.html"],
        ];
    }

    /**
     * クエリパラメータが指定された場合、それが正しく URL エンコードされて末尾に付与されることを確認します。
     *
     * @param array $query クエリパラメータの配列
     * @param string $expected 期待される URL 文字列
     * @covers ::__construct
     * @covers ::formatHref
     * @covers ::<private>
     * @dataProvider provideTestFormatPathWithQuery
     */
    public function testFormatPathWithQuery(array $query, string $expected): void
    {
        $obj = new Context("/base");
        $this->assertSame($expected, $obj->formatHref("search", $query));
    }

    /**
     * testFormatPathWithQuery() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestFormatPathWithQuery(): array
    {
        return [
            [[], "/base/search"],
            [["q" => "test"], "/base/search?q=test"],
            [["q" => "foo bar"], "/base/search?q=foo%20bar"],
            [["q" => "test", "category" => 1], "/base/search?q=test&category=1"],
            [["q" => "test", "cat" => [1, 2, 3]], "/base/search?q=test&cat%5B0%5D=1&cat%5B1%5D=2&cat%5B2%5D=3"],
        ];
    }

    /**
     * 独自の区切り文字を指定した場合に、クエリパラメータがその文字で結合されることを確認します。
     *
     * @covers ::__construct
     * @covers ::formatHref
     * @covers ::<private>
     */
    public function testFormatPathByCustomSeparator(): void
    {
        $obj = new Context("/base", ";");
        $this->assertSame("/base/search?q=test;cat=1", $obj->formatHref("search", ["q" => "test", "cat" => 1]));
    }

    /**
     * 第 1 引数のパスに既にクエリ文字 ("?") が含まれている場合、第 2 引数の配列が無視されることを確認します。
     *
     * @covers ::__construct
     * @covers ::formatHref
     * @covers ::<private>
     */
    public function testFormatPathWithRawQuery(): void
    {
        $obj = new Context("/base");
        $this->assertSame("/base/inquiry?step=confirm", $obj->formatHref("/inquiry?step=confirm", ["var1" => "ignore", "var2" => "asdf"]));
    }
}
