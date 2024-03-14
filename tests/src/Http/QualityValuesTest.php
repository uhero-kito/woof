<?php

namespace Woof\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\Http\QualityValues
 */
class QualityValuesTest extends TestCase
{
    /**
     * 無効な q-value 配列が渡された場合に InvalidArgumentException がスローされることを確認します。
     *
     * @param array $qvalueList テスト用の無効な配列
     * @covers ::__construct
     * @covers ::<private>
     * @dataProvider provideTestConstructFailByInvalidQvalueList
     */
    public function testConstructFailByInvalidQvalueList(array $qvalueList): void
    {
        $this->expectException(InvalidArgumentException::class);
        new QualityValues("Accept-Language", $qvalueList);
    }

    /**
     * testConstructFailByInvalidQvalueList() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestConstructFailByInvalidQvalueList(): array
    {
        return [
            [[]],
            [["a,b,c" => 1.0]],
            [["ja" => 1.5, "en" => 0.5]],
            [["ja" => 1.0, "en" => -1]],
            [["ja" => "asdf"]],
        ];
    }

    /**
     * 配列データが、カンマ区切りおよび q= の形式で正しく文字列化されることを確認します。
     *
     * @param array $qvalueList 入力となる配列
     * @param string $expected 期待される出力文字列
     * @covers ::__construct
     * @covers ::format
     * @dataProvider provideTestFormat
     */
    public function testFormat(array $qvalueList, string $expected): void
    {
        $obj = new QualityValues("Accept-Language", $qvalueList);
        $this->assertSame($expected, $obj->format());
    }

    /**
     * testFormat() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestFormat(): array
    {
        return [
            [["en" => 0.2, "ja" => 1.0, "en-GB" => 0.5, "en-US" => 0.7], "ja,en-US;q=0.7,en-GB;q=0.5,en;q=0.2"],
            [["ja" => "1.0", "en" => "0.75"], "ja,en;q=0.75"],
        ];
    }

    /**
     * 設定したヘッダー名が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getName
     */
    public function testGetName(): void
    {
        $obj = new QualityValues("Accept-Language", ["ja" => 1.0]);
        $this->assertSame("Accept-Language", $obj->getName());
    }

    /**
     * q-value の降順でソートされ、かつ正しく丸められた値が配列として取得できることを確認します。
     *
     * @param array $qvalueList 入力となる配列
     * @param array $expected ソート・整形済みの期待される配列
     * @covers ::__construct
     * @covers ::<private>
     * @covers ::getValue
     * @dataProvider provideTestGetValue
     */
    public function testGetValue(array $qvalueList, array $expected): void
    {
        $obj = new QualityValues("Accept-Language", $qvalueList);
        $this->assertSame($expected, $obj->getValue());
    }

    /**
     * testGetValue() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetValue(): array
    {
        return [
            [
                ["en" => 0.7, "ja" => 1.0, "en-GB" => 0.8, "en-US" => 0.9],
                ["ja" => "1", "en-US" => "0.9", "en-GB" => "0.8", "en" => "0.7"],
            ],
            [
                ["ja" => 6 / 7, "en" => 3 / 4, "de" => 1 / 5, "fr" => 1 / 8],
                ["ja" => "0.857", "en" => "0.75", "de" => "0.2", "fr" => "0.125"],
            ],
            [
                ["ja" => "1.000", "en" => ".9", "de" => "0"],
                ["ja" => "1.000", "en" => ".9", "de" => "0"],
            ]
        ];
    }
}
