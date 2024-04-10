<?php

namespace Woof\Web\Cache;

use LogicException;
use PHPUnit\Framework\TestCase;
use TestHelper;
use Woof\FileResources;
use Woof\Resources;
use Woof\System\ArrayRandom;
use Woof\System\FixedClock;
use Woof\Web\Context;
use Woof\Web\View;
use Woof\Web\ViewBody;

/**
 * VariantStorage のテストです。
 *
 * このテストクラスでは、キャッシュファイルの入出力の挙動を検証するため、
 * setUp() にて一時ディレクトリの初期化を行っています。
 *
 * @coversDefaultClass Woof\Web\Cache\VariantStorage
 */
class VariantStorageTest extends TestCase
{
    /**
     * View のレンダリング処理 (getOutput) が実行されたかどうかを記録するフラグです。
     *
     * @var bool
     */
    public static $renderCalled = false;

    /**
     * テスト用の一時ディレクトリのパスです。
     *
     * @var string
     */
    private $tmpdir;

    /**
     * テスト用の Resources オブジェクトです。
     *
     * @var Resources
     */
    private $resources;

    /**
     * テスト用の Context オブジェクトです。
     *
     * @var Context
     */
    private $context;

    /**
     * テスト用の一時ディレクトリと、ViewBody 生成に必要なオブジェクトの準備を行います。
     */
    protected function setUp(): void
    {
        $datadir = TEST_DATA_DIR . "/Web/Cache/VariantStorage";
        $tmpdir  = "{$datadir}/tmp";
        TestHelper::cleanDirectory($tmpdir);

        $this->tmpdir       = $tmpdir;
        $this->resources    = new FileResources("{$datadir}/resources");
        $this->context      = new Context("{$datadir}/config");
        self::$renderCalled = false;
    }

    /**
     * テスト用の ViewBody オブジェクトを構築して返します。
     *
     * @param View $view
     * @return ViewBody
     */
    private function createViewBody(View $view): ViewBody
    {
        return new ViewBody($view, $this->resources, $this->context);
    }

    /**
     * VariantContainer が設定されていないビルダーで newInstance() を呼び出した場合、
     * 例外がスローされることを確認します。
     *
     * @covers ::newInstance
     */
    public function testNewInstanceThrowsExceptionWithoutContainer(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("VariantContainer must be set before building VariantStorage.");

        $builder = new VariantStorageBuilder();
        VariantStorage::newInstance($builder);
    }

    /**
     * newInstance() と各ゲッターが正しく機能することを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getVariantContainer
     * @covers ::getMaxAge
     * @covers ::getGcProbability
     */
    public function testNewInstanceAndGetters(): void
    {
        $container = new FileVariantContainer($this->tmpdir);
        $builder   = (new VariantStorageBuilder())
            ->setVariantContainer($container)
            ->setMaxAge(1234)
            ->setGcProbability(0.75);

        $obj = VariantStorage::newInstance($builder);

        $this->assertSame($container, $obj->getVariantContainer());
        $this->assertSame(1234, $obj->getMaxAge());
        $this->assertSame(0.75, $obj->getGcProbability());
    }

    /**
     * ViewBody から正しいハッシュ ID が生成されることを確認します。
     *
     * @covers ::generateId
     */
    public function testGenerateId(): void
    {
        $view       = new TestVariantView("id_test");
        $body       = $this->createViewBody($view);
        $container  = new FileVariantContainer($this->tmpdir);
        $builder    = (new VariantStorageBuilder())->setVariantContainer($container);
        $obj        = VariantStorage::newInstance($builder);

        $expectedId = sha1(serialize($view));
        $this->assertSame($expectedId, $obj->generateId($body));
    }

    /**
     * GC の実行契機となるメソッド名のリストを提供します。
     *
     * @return array
     */
    public function provideMethodsTriggeringGc(): array
    {
        return [
            "hasVariant"   => ["hasVariant"],
            "fetchVariant" => ["fetchVariant"],
        ];
    }

    /**
     * GC 確率が 0.0 の場合、対象メソッドを呼び出しても古いファイルが削除されないことを確認します。
     *
     * @param string $method
     *
     * @covers ::hasVariant
     * @covers ::fetchVariant
     * @covers ::determineGC
     * @dataProvider provideMethodsTriggeringGc
     */
    public function testMethodWithoutGc(string $method): void
    {
        $view = new TestVariantView("no_gc");
        $body = $this->createViewBody($view);

        $expiredFile = "{$this->tmpdir}/expired.dat";
        file_put_contents($expiredFile, "old data");
        touch($expiredFile, 1555550000);

        $clock     = new FixedClock(1555555555);
        $container = new FileVariantContainer($this->tmpdir, null, $clock);
        $builder   = (new VariantStorageBuilder())
            ->setVariantContainer($container)
            ->setMaxAge(3600)
            ->setGcProbability(0.0)
            ->setClock($clock);

        $obj = VariantStorage::newInstance($builder);
        $obj->$method($body);

        $this->assertFileExists($expiredFile);
    }

    /**
     * GC 確率が 1.0 の場合、対象メソッドを呼び出すと古いファイルが削除されることを確認します。
     *
     * @param string $method
     *
     * @covers ::hasVariant
     * @covers ::fetchVariant
     * @covers ::determineGC
     * @dataProvider provideMethodsTriggeringGc
     */
    public function testMethodWithGcTriggered(string $method): void
    {
        $view = new TestVariantView("gc_100");
        $body = $this->createViewBody($view);

        $expiredFile = "{$this->tmpdir}/expired.dat";
        file_put_contents($expiredFile, "old data");
        touch($expiredFile, 1555550000);

        $clock     = new FixedClock(1555555555);
        $container = new FileVariantContainer($this->tmpdir, null, $clock);
        $builder   = (new VariantStorageBuilder())
            ->setVariantContainer($container)
            ->setMaxAge(3600)
            ->setGcProbability(1.0)
            ->setClock($clock);

        $obj = VariantStorage::newInstance($builder);
        $obj->$method($body);

        $this->assertFileDoesNotExist($expiredFile);
    }

    /**
     * determineGC() で乱数を用いた確率判定が正しく行われ、
     * 確率に応じたタイミングで GC が実行されることを確認します。
     *
     * @param string $method
     *
     * @covers ::hasVariant
     * @covers ::fetchVariant
     * @covers ::determineGC
     * @dataProvider provideMethodsTriggeringGc
     */
    public function testDetermineGCRandomness(string $method): void
    {
        $max            = mt_getrandmax();
        $getRandomValue = function (float $v) use ($max): int {
            return (int) ($max * $v);
        };

        $random    = new ArrayRandom(array_map($getRandomValue, [0.1, 0.9, 0.4]));
        $clock     = new FixedClock(1555555555);
        $tmpdir    = $this->tmpdir;
        $container = new FileVariantContainer($tmpdir, null, $clock);

        $builder = (new VariantStorageBuilder())
            ->setVariantContainer($container)
            ->setMaxAge(3600)
            ->setGcProbability(0.5)
            ->setClock($clock)
            ->setRandom($random);

        $obj = VariantStorage::newInstance($builder);

        $body1        = $this->createViewBody(new TestVariantView("random_gc_1"));
        $expiredFile1 = "{$tmpdir}/expired1.dat";
        touch($expiredFile1, 1555550000);
        $obj->$method($body1);
        $this->assertFileDoesNotExist($expiredFile1, "0.1 should trigger GC");

        $body2        = $this->createViewBody(new TestVariantView("random_gc_2"));
        $expiredFile2 = "{$tmpdir}/expired2.dat";
        touch($expiredFile2, 1555550000);
        $obj->$method($body2);
        $this->assertFileExists($expiredFile2, "0.9 should NOT trigger GC");

        $body3 = $this->createViewBody(new TestVariantView("random_gc_3"));
        $obj->$method($body3);
        $this->assertFileDoesNotExist($expiredFile2, "0.4 should trigger GC and delete remaining file");
    }

    /**
     * fetchVariant() でキャッシュが存在しない場合、新規にレンダリング・保存して返すことを確認します。
     *
     * @covers ::fetchVariant
     */
    public function testFetchVariantCacheMiss(): void
    {
        $view      = new TestVariantView("miss_test");
        $body      = $this->createViewBody($view);
        $clock     = new FixedClock(1555555555);
        $container = new FileVariantContainer($this->tmpdir, null, $clock);

        $builder = (new VariantStorageBuilder())
            ->setVariantContainer($container)
            ->setMaxAge(3600)
            ->setGcProbability(0.0)
            ->setClock($clock);

        $obj = VariantStorage::newInstance($builder);
        $id  = $obj->generateId($body);
        $this->assertFileDoesNotExist("{$this->tmpdir}/{$id}.dat");

        $variant = $obj->fetchVariant($body);
        $this->assertTrue(self::$renderCalled);
        $this->assertSame($id, $variant->getId());
        $this->assertSame("Rendered Content: miss_test", $variant->getContent());
        $this->assertSame(1555555555, $variant->getLastModified());
        $this->assertFileExists("{$this->tmpdir}/{$id}.dat");
    }

    /**
     * fetchVariant() でキャッシュが存在する場合にコンテナから読み込んで返すことと、
     * レンダリング処理が完全にスキップされることを確認します。
     *
     * @covers ::fetchVariant
     */
    public function testFetchVariantCacheHit(): void
    {
        $view = new TestVariantView("hit_test");
        $body = $this->createViewBody($view);

        $id            = sha1(serialize($view));
        $cachedContent = "Already Cached Content";
        $file          = "{$this->tmpdir}/{$id}.dat";
        file_put_contents($file, $cachedContent);
        touch($file, 1555555000);

        $clock     = new FixedClock(1555555555);
        $container = new FileVariantContainer($this->tmpdir, null, $clock);

        $builder = (new VariantStorageBuilder())
            ->setVariantContainer($container)
            ->setMaxAge(3600)
            ->setGcProbability(0.0)
            ->setClock($clock);
        $obj     = VariantStorage::newInstance($builder);
        $variant = $obj->fetchVariant($body);

        $this->assertSame($id, $variant->getId());
        $this->assertSame($cachedContent, $variant->getContent());
        $this->assertFalse(self::$renderCalled);
    }

    /**
     * fetchVariant() で一度ロードされたバリアントはメモリにキャッシュされることにより、
     * 二度目の呼び出しで同一のインスタンスが返ることや、ファイルシステムへアクセスしないことを確認します。
     *　
     * @covers ::fetchVariant
     */
    public function testFetchVariantMemoryCache(): void
    {
        $view      = new TestVariantView("memory_test");
        $body      = $this->createViewBody($view);
        $clock     = new FixedClock(1555555555);
        $container = new FileVariantContainer($this->tmpdir, null, $clock);

        $builder = (new VariantStorageBuilder())
            ->setVariantContainer($container)
            ->setMaxAge(3600)
            ->setGcProbability(0.0)
            ->setClock($clock);

        $obj      = VariantStorage::newInstance($builder);
        $variant1 = $obj->fetchVariant($body);
        unlink("{$this->tmpdir}/{$variant1->getId()}.dat");
        self::$renderCalled = false;
        $variant2 = $obj->fetchVariant($body);
        $this->assertSame($variant1, $variant2);
        $this->assertFalse(self::$renderCalled);
    }
}

/**
 * 状態をシリアライズしてバリアント ID を生成し、
 * レンダリングが実行されたかどうかを観測するためのテスト用 View クラスです。
 */
class TestVariantView implements View
{
    public $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function getContentType(): string
    {
        return "text/plain";
    }

    /**
     * ViewBody の getOutput() から呼び出されるレンダリング処理です。
     */
    public function render(Resources $resources, Context $context): string
    {
        VariantStorageTest::$renderCalled = true;
        return "Rendered Content: {$this->data}";
    }
}
