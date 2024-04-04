<?php

namespace Woof\Web\Cache;

use LogicException;
use PHPUnit\Framework\TestCase;
use TestHelper;
use Woof\FileDataStorage;
use Woof\Log\FileLogStorage;
use Woof\Log\Logger;
use Woof\Log\LoggerBuilder;
use Woof\System\FixedClock;

/**
 * DataVariantContainer のテストです。
 *
 * このテストクラスでは DataStorage の具象クラス (FileDataStorage) を通じて
 * テスト時に物理ファイルの入出力を行うため、setUp() 内でテスト用の一時ディレクトリの
 * クリーニングとテストデータのコピーを行っています。
 *
 * @coversDefaultClass Woof\Web\Cache\DataVariantContainer
 */
class DataVariantContainerTest extends TestCase
{
    /**
     * テスト用の一時ディレクトリのパスです。
     *
     * @var string
     */
    private $tmpdir;

    /**
     * テスト用のログディレクトリのパスです。
     *
     * @var string
     */
    private $logdir;

    /**
     * テストで使用する FileDataStorage です。
     *
     * @var FileDataStorage
     */
    private $storage;

    /**
     * 元のタイムゾーン設定を退避しておくための変数です。
     *
     * @var string
     */
    private $defaultTimezone;

    /**
     * 擬似的な保存領域を作成します。
     * また、テストの実行環境に依存しないようタイムゾーンを Asia/Tokyo に固定します。
     */
    protected function setUp(): void
    {
        $datadir = TEST_DATA_DIR . "/Web/Cache/DataVariantContainer";
        $tmpdir  = "{$datadir}/tmp";
        TestHelper::cleanDirectory($tmpdir);
        TestHelper::copyDirectory("{$datadir}/subjects", $tmpdir);

        $now = 1555555555;
        touch("{$tmpdir}/cache01/validvariant123.dat", $now);
        touch("{$tmpdir}/cache01/loadtarget123.dat", $now);
        touch("{$tmpdir}/cache02/validfile.dat", $now);
        touch("{$tmpdir}/cache01/expiredvariant123.dat", $now - 4000);
        touch("{$tmpdir}/cache02/expiredfile.dat", $now - 4000);
        touch("{$tmpdir}/cache02/dummy.txt", $now - 4000);

        $this->tmpdir  = $tmpdir;
        $this->logdir  = "{$tmpdir}/logs";
        $this->storage = new FileDataStorage($tmpdir);

        $this->defaultTimezone = ini_set("date.timezone", "Asia/Tokyo");
    }

    /**
     * 固定したタイムゾーンを元の状態に戻します。
     */
    protected function tearDown(): void
    {
        ini_set("date.timezone", $this->defaultTimezone);
    }

    /**
     * 生存期間の設定に応じて、有効期限切れのデータが正しく削除され、ログが出力されることを確認します。
     *
     * @covers ::__construct
     * @covers ::cleanExpiredVariants
     * @covers ::<private>
     */
    public function testCleanExpiredVariants(): void
    {
        $now       = 1555555555;
        $logger    = (new LoggerBuilder())
            ->setLogLevel(Logger::LEVEL_DEBUG)
            ->setClock(new FixedClock($now))
            ->setStorage(new FileLogStorage($this->logdir))
            ->build();
        $container = new DataVariantContainer($this->storage, "cache02", ".dat", $logger, new FixedClock($now));

        // 有効期限を 3600 秒として GC を実行し、expiredfile.dat の 1 件のみ削除されていることを検証します
        $count = $container->cleanExpiredVariants(3600);
        $this->assertSame(1, $count);
        $this->assertTrue($this->storage->contains("cache02/validfile.dat"));
        $this->assertFalse($this->storage->contains("cache02/expiredfile.dat"));
        $this->assertTrue($this->storage->contains("cache02/dummy.txt"));

        $logPath    = "{$this->logdir}/app-20190418.log";
        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString("Expired variant cache data deleted: 'cache02/expiredfile.dat'", $logContent);
    }

    /**
     * 指定された ID のバリアントが存在し、かつ有効期限内であるかが正しく判定されることを確認します。
     *
     * @covers ::__construct
     * @covers ::contains
     * @covers ::<private>
     */
    public function testContains(): void
    {
        $now       = 1555555555;
        $container = new DataVariantContainer($this->storage, "cache01", ".dat", null, new FixedClock($now));

        $this->assertTrue($container->contains("validvariant123", 3600));
        $this->assertFalse($container->contains("expiredvariant123", 3600));
        $this->assertFalse($container->contains("notfound", 3600));
    }

    /**
     * DataStorage からバリアントのデータが正しくロードされることを確認します。
     *
     * @covers ::__construct
     * @covers ::load
     * @covers ::<private>
     */
    public function testLoad(): void
    {
        $now       = 1555555555;
        $container = new DataVariantContainer($this->storage, "cache01", ".dat", null, new FixedClock($now));
        $variant   = $container->load("loadtarget123");

        $this->assertSame("loadtarget123", $variant->getId());
        $this->assertSame("load target content", trim($variant->getContent()));
        $this->assertSame($now, $variant->getLastModified());
    }

    /**
     * 存在しないバリアント ID を読み込もうとした場合に、LogicException がスローされることを確認します。
     *
     * @covers ::__construct
     * @covers ::load
     * @covers ::<private>
     */
    public function testLoadThrowsExceptionForMissingVariant(): void
    {
        $this->expectException(LogicException::class);
        $container = new DataVariantContainer($this->storage, "cache01", ".dat");
        $container->load("missing_id");
    }

    /**
     * コンテンツが DataStorage に指定したフォーマットで正しく保存されることを確認します。
     *
     * @covers ::save
     * @covers ::<private>
     */
    public function testSave(): void
    {
        $container = new DataVariantContainer($this->storage, "cache01", ".dat");
        $result    = $container->save("newsaved123", "newly saved content");

        $this->assertTrue($result);
        $this->assertTrue($this->storage->contains("cache01/newsaved123.dat"));
        $this->assertSame("newly saved content", trim($this->storage->get("cache01/newsaved123.dat")));
    }

    /**
     * suffix を空文字列とした場合でも、正しく保存や判定が行われることを確認します。
     *
     * @covers ::__construct
     * @covers ::save
     * @covers ::contains
     * @covers ::<private>
     */
    public function testWithoutSuffix(): void
    {
        $container = new DataVariantContainer($this->storage, "cache01", "");
        $result    = $container->save("nosuffixfile", "no suffix content");

        $this->assertTrue($result);
        $this->assertTrue($this->storage->contains("cache01/nosuffixfile"));
        $this->assertTrue($container->contains("nosuffixfile", 3600));
        $this->assertSame("no suffix content", trim($this->storage->get("cache01/nosuffixfile")));
    }
}
