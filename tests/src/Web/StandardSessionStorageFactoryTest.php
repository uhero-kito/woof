<?php

namespace Woof\Web;

use PHPUnit\Framework\TestCase;
use TestHelper;
use Woof\Config;
use Woof\FileDataStorage;
use Woof\Log\FileLogStorage;
use Woof\Log\Logger;
use Woof\Log\LoggerBuilder;
use Woof\Util\ArrayProperties;
use Woof\Web\Session\FileSessionContainer;
use Woof\Web\Session\SessionStorage;
use Woof\Web\Session\SessionStorageBuilder;

/**
 * StandardSessionStorageFactory のテストです。
 *
 * このテストではファイルシステムへの書き込み (一時ディレクトリの作成など) を伴うため、
 * setUp() にてテスト環境の初期化を行っています。
 *
 * @coversDefaultClass Woof\Web\StandardSessionStorageFactory
 */
class StandardSessionStorageFactoryTest extends TestCase
{
    /**
     * テスト用の一時ディレクトリパスです。
     *
     * @var string
     */
    const TMP_DIR = TEST_DATA_DIR . "/Web/StandardSessionStorageFactory/tmp";

    /**
     * テスト実行前に一時ディレクトリをクリーンアップします。
     */
    public function setUp(): void
    {
        TestHelper::cleanDirectory(self::TMP_DIR);
    }

    /**
     * PHP のデフォルトのセッション保存先パスを取得します。
     *
     * @return string デフォルトの保存先パス
     */
    private function getDefaultPath(): string
    {
        $savePath = session_save_path();
        return strlen($savePath) ? $savePath : sys_get_temp_dir();
    }

    /**
     * PHP のデフォルトのガベージコレクション実行確率を取得します。
     *
     * @return float デフォルトの実行確率
     */
    private function getDefaultGcProbability(): float
    {
        $p = ini_get("session.gc_probability");
        $d = ini_get("session.gc_divisor");
        return (0 < $p && 0 < $d) ? (float) ($p / $d) : 0.0;
    }

    /**
     * テスト用の Logger インスタンスを生成して返します。
     *
     * @return Logger テスト用ロガー
     */
    private function getTestLogger(): Logger
    {
        $logdir = self::TMP_DIR . "/logs";
        is_dir($logdir) || mkdir($logdir, 0777, true);
        return (new LoggerBuilder())->setStorage(new FileLogStorage($logdir))->build();
    }

    /**
     * 配列データをもとに Config を作成し、ファクトリから SessionStorage を生成して返します。
     *
     * @param array $arr "session" セクションに割り当てる配列データ
     * @return SessionStorage 生成されたセッションストレージ
     */
    private function createStorageByArray(array $arr): SessionStorage
    {
        $obj  = new StandardSessionStorageFactory();
        $prop = new ArrayProperties(["session" => $arr]);
        $conf = new Config($prop);
        return $obj->create($conf, new FileDataStorage(self::TMP_DIR), $this->getTestLogger());
    }

    /**
     * 設定の dirname に応じて、正しいセッション保存先パスが解決されることを確認します。
     *
     * @param array $arr 入力となる設定配列
     * @param string $expected 期待される保存先パス
     * @covers ::create
     * @covers ::getSessionSavePath
     * @dataProvider provideTestGetSessionSavePath
     */
    public function testGetSessionSavePath(array $arr, string $expected): void
    {
        $ss = $this->createStorageByArray($arr);
        $c1 = $ss->getSessionContainer();
        $c2 = new FileSessionContainer($expected, $this->getTestLogger());
        $this->assertEquals($c2, $c1);
    }

    /**
     * testGetSessionSavePath() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetSessionSavePath(): array
    {
        $tmp  = self::TMP_DIR;
        $arr1 = [];
        $arr2 = ["dirname" => "test01"];
        $arr3 = ["dirname" => [1, 2, 3]];
        return [
            [$arr1, "{$tmp}/sessions"],
            [$arr2, "{$tmp}/test01"],
            [$arr3, "{$tmp}/sessions"],
        ];
    }

    /**
     * FileDataStorage を利用しない場合、デフォルトのセッション保存先パスが利用されることを確認します。
     *
     * @covers ::create
     * @covers ::getSessionSavePath
     */
    public function testGetSessionSavePathWithoutData(): void
    {
        $obj  = new StandardSessionStorageFactory();
        $prop = new ArrayProperties(["session" => ["dirname" => "test02"]]);
        $conf = new Config($prop);
        $ss   = $obj->create($conf);

        $c1 = $ss->getSessionContainer();
        $c2 = new FileSessionContainer($this->getDefaultPath());
        $this->assertEquals($c2, $c1);
    }

    /**
     * 設定の keyname に対応したセッションキーが設定されることを確認します。
     *
     * @param array $arr 入力となる設定配列
     * @param string $expected 期待されるセッションキー
     * @covers ::create
     * @covers ::getSessionKey
     * @dataProvider provideTestGetSessionKey
     */
    public function testGetSessionKey(array $arr, string $expected): void
    {
        $ss = $this->createStorageByArray($arr);
        $this->assertSame($expected, $ss->getKey());
    }

    /**
     * testGetSessionKey() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetSessionKey(): array
    {
        $def  = session_name();
        $arr1 = [];
        $arr2 = ["keyname" => "test_sess_id"];
        $arr3 = ["keyname" => ["a" => 1]];
        return [
            [$arr1, $def],
            [$arr2, "test_sess_id"],
            [$arr3, $def],
        ];
    }

    /**
     * 設定の max-age に応じて、正しい有効期間が解決されること、および最小値・最大値で制限されることを確認します。
     *
     * @param array $arr 入力となる設定配列
     * @param int $expected 期待される有効期間 (秒)
     * @covers ::create
     * @covers ::getMaxAge
     * @dataProvider provideTestGetMaxAge
     */
    public function testGetMaxAge(array $arr, int $expected): void
    {
        $ss = $this->createStorageByArray($arr);
        $this->assertSame($expected, $ss->getMaxAge());
    }

    /**
     * testGetMaxAge() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetMaxAge(): array
    {
        $def  = (int) ini_get("session.gc_maxlifetime");
        $arr1 = [];
        $arr2 = ["max-age" => 1800];
        $arr3 = ["max-age" => "asdf"];
        $arr4 = ["max-age" => 30];   // 最小値 60 より小さいため丸められる
        $arr5 = ["max-age" => 9000]; // 最大値 7200 より大きいため丸められる
        return [
            [$arr1, $def],
            [$arr2, 1800],
            [$arr3, $def],
            [$arr4, 60],
            [$arr5, 7200],
        ];
    }

    /**
     * 設定の gc-probability に応じて、正しい GC 実行確率が設定されることと、
     * 値が 0.0 〜 1.0 の範囲に制限されることを確認します。
     *
     * @param array $arr 入力となる設定配列
     * @param float $expected 期待される GC 実行確率
     * @covers ::create
     * @covers ::getGcProbability
     * @dataProvider provideTestGetGcProbability
     */
    public function testGetGcProbability(array $arr, float $expected): void
    {
        $ss = $this->createStorageByArray($arr);
        $this->assertSame($expected, $ss->getGcProbability());
    }

    /**
     * testGetGcProbability() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetGcProbability(): array
    {
        $def  = $this->getDefaultGcProbability();
        $arr1 = [];
        $arr2 = ["gc-probability" => 0.125];
        $arr3 = ["gc-probability" => "asdf"];
        $arr4 = ["gc-probability" => -0.25]; // 0.0 未満のため丸められる
        $arr5 = ["gc-probability" => 1.0675]; // 1.0 より大きいため丸められる
        return [
            [$arr1, $def],
            [$arr2, 0.125],
            [$arr3, $def],
            [$arr4, 0.0],
            [$arr5, 1.0],
        ];
    }

    /**
     * 正常な設定配列を与えた場合に、各パラメータが適用された SessionStorage インスタンスが正しく構築されることを確認します。
     *
     * @covers ::create
     */
    public function testCreate(): void
    {
        $testdir = self::TMP_DIR . "/test/dir1";
        mkdir($testdir, 0777, true);

        $expected = (new SessionStorageBuilder())
            ->setSessionContainer(new FileSessionContainer($testdir, $this->getTestLogger()))
            ->setKey("testkey")
            ->setMaxAge(900)
            ->setGcProbability(0.125)
            ->build();

        $arr = [
            "dirname"        => "/test/dir1",
            "keyname"        => "testkey",
            "max-age"        => 900,
            "gc-probability" => 0.125,
        ];
        $this->assertEquals($expected, $this->createStorageByArray($arr));
    }
}
