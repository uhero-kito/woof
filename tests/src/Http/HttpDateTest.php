<?php

namespace Woof\Http;

use PHPUnit\Framework\TestCase;
use Woof\System\FixedClock;

/**
 * HttpDate のテストです。
 *
 * このテストクラスでは時刻の書式化の正確性を担保するため、
 * setUp() および tearDown() で一時的にシステムのデフォルトタイムゾーンを変更しています。
 *
 * @coversDefaultClass Woof\Http\HttpDate
 */
class HttpDateTest extends TestCase
{
    /**
     * テスト実行前の元のタイムゾーン設定を保持します。
     *
     * @var string
     */
    private $defaultTimezone;

    /**
     * テスト用の HttpDateFormat です。
     *
     * @var HttpDateFormat
     */
    private $format;

    /**
     * テストの実行環境に依存しないよう、システムのタイムゾーンを一時的に Asia/Tokyo に固定し、
     * テスト用の HttpDateFormat を準備します。
     */
    public function setUp(): void
    {
        $this->format          = new HttpDateFormat(new FixedClock(1600000000));
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
     * 保持している時刻が正しく HTTP-date 文字列としてフォーマットされることを確認します。
     *
     * @covers ::__construct
     * @covers ::format
     */
    public function testFormat(): void
    {
        $obj = new HttpDate("Last-Modified", 1555555555, $this->format);
        $this->assertSame("Thu, 18 Apr 2019 02:45:55 GMT", $obj->format());
    }

    /**
     * 設定したヘッダー名が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getName
     */
    public function testGetName(): void
    {
        $obj = new HttpDate("Last-Modified", 1555555555, $this->format);
        $this->assertSame("Last-Modified", $obj->getName());
    }

    /**
     * 設定した値 (Unix time) が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::getValue
     */
    public function testGetValue(): void
    {
        $obj = new HttpDate("Last-Modified", 1555555555, $this->format);
        $this->assertSame(1555555555, $obj->getValue());
    }
}
