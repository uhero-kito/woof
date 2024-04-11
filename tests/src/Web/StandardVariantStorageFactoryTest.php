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
use Woof\Web\Cache\DataVariantContainer;
use Woof\Web\Cache\NullVariantContainer;
use Woof\Web\Cache\VariantStorage;
use Woof\Web\Cache\VariantStorageBuilder;

/**
 * StandardVariantStorageFactory のテストです。
 *
 * このテストではファイルシステムへの書き込み (一時ディレクトリの作成など) を伴うため、
 * setUp() にてテスト環境の初期化を行っています。
 *
 * @coversDefaultClass Woof\Web\StandardVariantStorageFactory
 */
class StandardVariantStorageFactoryTest extends TestCase
{
    /**
     * テスト用の一時ディレクトリパスです。
     *
     * @var string
     */
    const TMP_DIR = TEST_DATA_DIR . "/Web/StandardVariantStorageFactory/tmp";

    /**
     * テスト実行前に一時ディレクトリをクリーンアップします。
     */
    public function setUp(): void
    {
        TestHelper::cleanDirectory(self::TMP_DIR);
    }

    /**
     * テスト用の Logger インスタンスを生成して返します。
     *
     * @return Logger テスト用の Logger
     */
    private function getTestLogger(): Logger
    {
        $logdir = self::TMP_DIR . "/logs";
        is_dir($logdir) || mkdir($logdir, 0777, true);
        return (new LoggerBuilder())->setStorage(new FileLogStorage($logdir))->build();
    }

    /**
     * 配列データをもとに Config を作成し、ファクトリから VariantStorage を生成して返します。
     *
     * @param array $arr "cache" セクションに割り当てる配列データ
     * @return VariantStorage 生成された VariantStorage
     */
    private function createStorageByArray(array $arr): VariantStorage
    {
        $obj  = new StandardVariantStorageFactory();
        $prop = new ArrayProperties(["cache" => $arr]);
        $conf = new Config($prop);
        return $obj->create($conf, new FileDataStorage(self::TMP_DIR), $this->getTestLogger());
    }

    /**
     * DataStorage に null を渡した場合、ダミーの NullVariantContainer を持つストレージが生成されることを確認します。
     *
     * @covers ::create
     */
    public function testCreateWithoutDataStorage(): void
    {
        $obj  = new StandardVariantStorageFactory();
        $prop = new ArrayProperties(["cache" => ["max-age" => 9999]]);
        $conf = new Config($prop);

        // DataStorage に null を渡して実行します
        $storage = $obj->create($conf, null);

        $this->assertInstanceOf(VariantStorage::class, $storage);
        $this->assertInstanceOf(NullVariantContainer::class, $storage->getVariantContainer());

        // NullVariantContainer 用のダミー設定が適用されていることを確認します (max-age はデフォルトの 3600, GC は 0.0)
        $this->assertSame(3600, $storage->getMaxAge());
        $this->assertSame(0.0, $storage->getGcProbability());
    }

    /**
     * 設定の dirname に応じて、正しいイニシャル・セグメントが解決されることを確認します。
     *
     * @param array $arr 入力となる設定配列
     * @param string $expected 期待されるイニシャル・セグメント
     * @covers ::create
     * @dataProvider provideTestGetCachePrefix
     */
    public function testGetCachePrefix(array $arr, string $expected): void
    {
        $ss = $this->createStorageByArray($arr);
        $c1 = $ss->getVariantContainer();
        $c2 = new DataVariantContainer(new FileDataStorage(self::TMP_DIR), $expected, ".dat", $this->getTestLogger());
        $this->assertEquals($c2, $c1);
    }

    /**
     * testGetCachePrefix() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetCachePrefix(): array
    {
        $arr1 = [];
        $arr2 = ["dirname" => "custom_cache"];
        $arr3 = ["dirname" => [1, 2, 3]]; // 不正な型 (配列など) の場合はデフォルト
        return [
            [$arr1, "cache"],
            [$arr2, "custom_cache"],
            [$arr3, "cache"],
        ];
    }

    /**
     * 設定の suffix に応じて、正しい末尾文字列が解決されることを確認します。
     *
     * @param array $arr 入力となる設定配列
     * @param string $expected 期待される末尾文字列
     * @covers ::create
     * @dataProvider provideTestGetCacheSuffix
     */
    public function testGetCacheSuffix(array $arr, string $expected): void
    {
        $ss = $this->createStorageByArray($arr);
        $c1 = $ss->getVariantContainer();
        $c2 = new DataVariantContainer(new FileDataStorage(self::TMP_DIR), "cache", $expected, $this->getTestLogger());
        $this->assertEquals($c2, $c1);
    }

    /**
     * testGetCacheSuffix() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGetCacheSuffix(): array
    {
        $arr1 = [];
        $arr2 = ["suffix" => ".cache"];
        $arr3 = ["suffix" => ""];
        $arr4 = ["suffix" => [1, 2]]; // 不正な型 (配列など) の場合はデフォルト
        return [
            [$arr1, ".dat"],
            [$arr2, ".cache"],
            [$arr3, ""],
            [$arr4, ".dat"],
        ];
    }

    /**
     * 設定の max-age に応じて、正しい有効期間が解決されることを確認します。
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
        $arr1 = [];
        $arr2 = ["max-age" => 7200];
        $arr3 = ["max-age" => "invalid"]; // 数値として解釈できない場合はデフォルト
        return [
            [$arr1, 3600],
            [$arr2, 7200],
            [$arr3, 3600],
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
        $arr1 = [];
        $arr2 = ["gc-probability" => 0.5];
        $arr3 = ["gc-probability" => "invalid"];
        $arr4 = ["gc-probability" => -0.1]; // 0.0 未満のため丸められる
        $arr5 = ["gc-probability" => 1.5];  // 1.0 より大きいため丸められる
        return [
            [$arr1, 0.01],
            [$arr2, 0.5],
            [$arr3, 0.01],
            [$arr4, 0.0],
            [$arr5, 1.0],
        ];
    }

    /**
     * 正常な設定配列を与えた場合に、各パラメータが適用された VariantStorage インスタンスが正しく構築されることを確認します。
     *
     * @covers ::create
     */
    public function testCreate(): void
    {
        $expected = (new VariantStorageBuilder())
            ->setVariantContainer(new DataVariantContainer(new FileDataStorage(self::TMP_DIR), "/test/cache1", ".bin", $this->getTestLogger()))
            ->setMaxAge(1800)
            ->setGcProbability(0.2)
            ->build();

        $arr = [
            "dirname"        => "/test/cache1",
            "suffix"         => ".bin",
            "max-age"        => 1800,
            "gc-probability" => 0.2,
        ];
        $this->assertEquals($expected, $this->createStorageByArray($arr));
    }
}
