<?php

namespace Woof\Web\Cache;

use LogicException;
use PHPUnit\Framework\TestCase;
use TestHelper;
use Woof\Log\FileLogStorage;
use Woof\Log\Logger;
use Woof\Log\LoggerBuilder;
use Woof\System\FileSystemException;
use Woof\System\FixedClock;

/**
 * FileVariantContainer のテストです。
 *
 * このテストクラスでは、テスト時に物理ファイルの入出力を行うため
 * setUp() 内でテスト用の一時ディレクトリのクリーニングとテストデータのコピーを行います。
 *
 * @coversDefaultClass Woof\Web\Cache\FileVariantContainer
 */
class FileVariantContainerTest extends TestCase
{
    /**
     * テスト用の一時ディレクトリのパスです。
     *
     * @var string
     */
    private $tmpdir;

    /**
     * テスト用の一時ディレクトリの準備とテストデータのコピーを行います。
     */
    protected function setUp(): void
    {
        $datadir = TEST_DATA_DIR . "/Web/Cache/FileVariantContainer";
        $tmpdir  = "{$datadir}/tmp";
        TestHelper::cleanDirectory($tmpdir);
        TestHelper::copyDirectory("{$datadir}/subjects", $tmpdir);

        $this->tmpdir = $tmpdir;
    }

    /**
     * コンストラクタに存在しないディレクトリを指定した場合、FileSystemException がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructThrowsExceptionForMissingDirectory(): void
    {
        $this->expectException(FileSystemException::class);
        new FileVariantContainer("{$this->tmpdir}/not_found_directory");
    }

    /**
     * 有効期限内のバリアントが存在する場合、contains() が true を返すことを確認します。
     *
     * @covers ::__construct
     * @covers ::contains
     * @covers ::formatFilename
     */
    public function testContainsReturnsTrueForValidVariant(): void
    {
        $cachedir = "{$this->tmpdir}/cache01";
        $id       = "validvariant123";
        $file     = "{$cachedir}/{$id}.dat";
        $mtime    = 1555555000;
        touch($file, $mtime);

        // 更新日時から 1000 秒経過した現在時刻をシミュレートします (有効期限の 3600 秒以内)
        $clock     = new FixedClock($mtime + 1000);
        $container = new FileVariantContainer($cachedir, null, $clock);
        $this->assertTrue($container->contains($id, 3600));
    }

    /**
     * 有効期限切れのバリアントの場合、ファイルが存在していても contains() が false を返すことを確認します。
     *
     * @covers ::__construct
     * @covers ::contains
     * @covers ::formatFilename
     */
    public function testContainsReturnsFalseForExpiredVariant(): void
    {
        $cachedir = "{$this->tmpdir}/cache01";
        $id       = "expiredvariant123";
        $file     = "{$cachedir}/{$id}.dat";
        $mtime    = 1555555000;
        touch($file, $mtime);

        // 更新日時から 4000 秒経過した現在時刻をシミュレートします (有効期限の 3600 秒を超過)
        $clock     = new FixedClock($mtime + 4000);
        $container = new FileVariantContainer($cachedir, null, $clock);
        $this->assertFalse($container->contains($id, 3600));
    }

    /**
     * バリアントファイルが存在しない場合、contains() が false を返すことを確認します。
     *
     * @covers ::__construct
     * @covers ::contains
     * @covers ::formatFilename
     */
    public function testContainsReturnsFalseForMissingVariant(): void
    {
        $cachedir  = "{$this->tmpdir}/cache01";
        $container = new FileVariantContainer($cachedir);
        $this->assertFalse($container->contains("missingvariant123", 3600));
    }

    /**
     * 存在するバリアントファイルを指定した場合、正しく Variant オブジェクトとしてロードされることを確認します。
     *
     * @covers ::__construct
     * @covers ::load
     * @covers ::formatFilename
     */
    public function testLoadReturnsVariant(): void
    {
        $cachedir = "{$this->tmpdir}/cache01";
        $id       = "loadtarget123";
        $file     = "{$cachedir}/{$id}.dat";
        $content  = "Lorem Ipsum Cache Content";
        $mtime    = 1555555555;
        touch($file, $mtime);

        $container = new FileVariantContainer($cachedir);
        $variant   = $container->load($id);

        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertSame($id, $variant->getId());
        $this->assertSame($content, trim($variant->getContent()));
        $this->assertSame($mtime, $variant->getLastModified());
    }

    /**
     * 存在しないバリアントファイルをロードしようとした場合、LogicException がスローされることを確認します。
     *
     * @covers ::__construct
     * @covers ::load
     * @covers ::formatFilename
     */
    public function testLoadThrowsExceptionForMissingVariant(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Variant file not found for ID: 'notfound123'");

        $cachedir  = "{$this->tmpdir}/cache01";
        $container = new FileVariantContainer($cachedir);
        $container->load("notfound123");
    }

    /**
     * save() メソッドによってファイルシステムに正しくデータが書き込まれることを確認します。
     *
     * @covers ::__construct
     * @covers ::save
     * @covers ::formatFilename
     */
    public function testSaveWritesContentToDisk(): void
    {
        $cachedir = "{$this->tmpdir}/cache01";
        $id       = "savetarget123";
        $content  = "New Cached Data";

        $container = new FileVariantContainer($cachedir);
        $this->assertTrue($container->save($id, $content));

        $file = "{$cachedir}/{$id}.dat";
        $this->assertFileExists($file);
        $this->assertSame($content, file_get_contents($file));
    }

    /**
     * cleanExpiredVariants() によって有効期限切れのファイルのみが削除され、
     * ロガーにデバッグ出力が送られることを確認します。
     *
     * @covers ::__construct
     * @covers ::cleanExpiredVariants
     */
    public function testCleanExpiredVariantsRemovesOldFiles(): void
    {
        $cachedir = "{$this->tmpdir}/cache02";
        $logdir   = "{$this->tmpdir}/logs";
        $now      = 1555555000;

        // 1. 有効期限内のファイル ($now と同じ時刻に作成)
        $validId = "validfile";
        touch("{$cachedir}/{$validId}.dat", $now);

        // 2. 有効期限切れのファイル ($now より 4000 秒古い)
        $expiredId = "expiredfile";
        touch("{$cachedir}/{$expiredId}.dat", $now - 4000);

        // 3. 拡張子が異なる古いファイル (削除対象外であるべき)
        $dummyFile = "dummy.txt";
        touch("{$cachedir}/{$dummyFile}", $now - 4000);

        // テスト用の Logger
        $logTime = 1555555555; // 2019-04-18 02:45:55 (UTC)
        $logger  = (new LoggerBuilder())
            ->setLogLevel(Logger::LEVEL_DEBUG)
            ->setClock(new FixedClock($logTime))
            ->setStorage(new FileLogStorage($logdir))
            ->build();

        $container = new FileVariantContainer($cachedir, $logger, new FixedClock($now));

        // 有効期限を 3600 秒として GC を実行し、expiredfile.dat の 1 件のみ削除されていることを検証します
        $count = $container->cleanExpiredVariants(3600);
        $this->assertSame(1, $count);
        $this->assertFileExists("{$cachedir}/{$validId}.dat");
        $this->assertFileDoesNotExist("{$cachedir}/{$expiredId}.dat");
        $this->assertFileExists("{$cachedir}/{$dummyFile}");

        // ログファイルが生成され、意図したメッセージが書き込まれているかを検証します
        $datePart = date("Ymd", $logTime);
        $logFile  = "{$logdir}/app-{$datePart}.log";
        $this->assertFileExists($logFile);

        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString("Expired variant cache file deleted: '{$cachedir}/{$expiredId}.dat'", $logContent);
    }
}
