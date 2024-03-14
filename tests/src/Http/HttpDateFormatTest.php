<?php

namespace Woof\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Woof\System\FixedClock;

/**
 * HttpDateFormat のテストです。
 *
 * このテストクラスでは時刻解析の正確性を担保するため
 * setUp() および tearDown() で一時的にシステムのデフォルトタイムゾーンを変更しています。
 *
 * @coversDefaultClass Woof\Http\HttpDateFormat
 */
class HttpDateFormatTest extends TestCase
{
    /**
     * テスト実行前の元のタイムゾーン設定を保持します。
     *
     * @var string
     */
    private $defaultTimezone;

    /**
     * テストの実行環境に依存しないよう、システムのタイムゾーンを一時的に Asia/Tokyo に固定します。
     */
    public function setUp(): void
    {
        $this->defaultTimezone = ini_set("timezone", "Asia/Tokyo");
    }

    /**
     * 固定したタイムゾーンを元の状態に戻します。
     */
    public function tearDown(): void
    {
        ini_set("timezone", $this->defaultTimezone);
    }

    /**
     * テスト用の HttpDateFormat インスタンスを生成して返します。
     *
     * @return HttpDateFormat テスト用のインスタンス
     */
    private function createTestObject(): HttpDateFormat
    {
        return new HttpDateFormat(new FixedClock(1600000000));
    }

    /**
     * 様々な HTTP-date 形式の文字列が、正しく同一の Unix time に変換されることを確認します。
     *
     * @param string $format 入力となる HTTP-date 文字列
     * @covers ::__construct
     * @covers ::parse
     * @covers ::<private>
     * @dataProvider provideTestParse
     */
    public function testParse(string $format): void
    {
        $obj = $this->createTestObject();
        $this->assertSame(1555555555, $obj->parse($format));
    }

    /**
     * testParse() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestParse(): array
    {
        return [
            ["Thu, 18 Apr 2019 02:45:55 GMT"],
            ["Thursday, 18-Apr-19 02:45:55 GMT"],
            ["Thu Apr 18 02:45:55 2019"],
        ];
    }

    /**
     * サポートされていない形式の文字列を渡した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::__construct
     * @covers ::parse
     * @covers ::<private>
     */
    public function testParseFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = $this->createTestObject();
        $obj->parse("hogehoge");
    }

    /**
     * Unix time が RFC 822 準拠の HTTP-date 文字列に正しく変換されることを確認します。
     *
     * @param int $time 入力となる Unix time
     * @param string $expected 期待される HTTP-date 文字列
     * @covers ::__construct
     * @covers ::format
     * @dataProvider provideTestFormat
     */
    public function testFormat(int $time, string $expected): void
    {
        $obj = $this->createTestObject();
        $this->assertSame($expected, $obj->format($time));
    }

    /**
     * testFormat() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestFormat(): array
    {
        return [
            [1515283200, "Sun, 07 Jan 2018 00:00:00 GMT"],
            [1518393600, "Mon, 12 Feb 2018 00:00:00 GMT"],
            [1521504000, "Tue, 20 Mar 2018 00:00:00 GMT"],
            [1524614400, "Wed, 25 Apr 2018 00:00:00 GMT"],
            [1527724800, "Thu, 31 May 2018 00:00:00 GMT"],
            [1527811200, "Fri, 01 Jun 2018 00:00:00 GMT"],
            [1531526400, "Sat, 14 Jul 2018 00:00:00 GMT"],
            [1534636800, "Sun, 19 Aug 2018 00:00:00 GMT"],
            [1537747200, "Mon, 24 Sep 2018 00:00:00 GMT"],
            [1540857600, "Tue, 30 Oct 2018 00:00:00 GMT"],
            [1541548800, "Wed, 07 Nov 2018 00:00:00 GMT"],
            [1544659200, "Thu, 13 Dec 2018 00:00:00 GMT"],
        ];
    }
}
