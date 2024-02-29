<?php

namespace Woof\Http;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\HeaderParser
 */
class HeaderParserTest extends TestCase
{
    /**
     * 引数を省略して生成したインスタンスが、デフォルト値を用いたインスタンスと等価であることを確認します。
     *
     * @covers ::__construct
     * @covers ::getDefaultQualityValuesNames
     * @covers ::getDefaultHttpDateNames
     */
    public function testConstructByDefault(): void
    {
        $qNames = [
            "accept",
            "accept-charset",
            "accept-encoding",
            "accept-language",
        ];
        $dNames = [
            "date",
            "if-modified-since",
            "last-modified",
        ];
        $format = new HttpDateFormat();
        $obj1   = new HeaderParser($qNames, $dNames, $format);
        $obj2   = new HeaderParser();
        $this->assertEquals($obj1, $obj2);
    }

    /**
     * ヘッダー名と値の組み合わせから、適切な HeaderField オブジェクトが生成されることを確認します。
     *
     * @param string $name ヘッダー名
     * @param string $value ヘッダーの値
     * @param HeaderField $expected 期待される HeaderField オブジェクト
     * @covers ::__construct
     * @covers ::parse
     * @covers ::<private>
     * @dataProvider provideTestParse
     */
    public function testParse(string $name, string $value, HeaderField $expected): void
    {
        $obj = new HeaderParser();
        $this->assertEquals($expected, $obj->parse($name, $value));
    }

    /**
     * testParse() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestParse(): array
    {
        $f1 = new HttpDate("Date", 1555555555);
        $f2 = new HttpDate("If-Modified-Since", 1500000000);
        $f3 = new QualityValues("Accept-Encoding", ["gzip" => 1.0, "deflate" => 1.0, "br" => 1.0]);
        $f4 = new QualityValues("Accept-Language", ["ja" => 1.0, "en-US" => 0.9, "en" => 0.8]);
        $f5 = new TextField("Connection", "keep-alive");
        $f6 = new TextField("Content-Length", "123");
        return [
            ["Date", "Thu, 18 Apr 2019 02:45:55 GMT", $f1],
            ["If-Modified-Since", "Fri, 14 Jul 2017 02:40:00 GMT", $f2],
            ["Accept-Encoding", "gzip, deflate, br;q=invalid", $f3],
            ["Accept-Language", "ja,en-US;q=0.9,en;q=0.8", $f4],
            ["Connection", "keep-alive", $f5],
            ["Content-Length", "123", $f6],
        ];
    }
}
