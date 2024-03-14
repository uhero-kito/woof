<?php

namespace Woof\Web\Session;

use PHPUnit\Framework\TestCase;
use TestHelper;
use Woof\Log\FileLogStorage;
use Woof\Log\Logger;
use Woof\Log\LoggerBuilder;
use Woof\System\FileSystemException;
use Woof\System\FixedClock;

/**
 * FileSessionContainer のテストです。
 *
 * このテストクラスではファイルシステムの操作 (ディレクトリ作成・ファイル書き込み) およびタイムゾーンの変更を伴うため、
 * setUp() と tearDown() にて環境の初期化と復元を行っています。
 *
 * @coversDefaultClass Woof\Web\Session\FileSessionContainer
 */
class FileSessionContainerTest extends TestCase
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
     * 元のタイムゾーン設定を退避しておくための変数です。
     *
     * @var string
     */
    private $defaultTimezone;

    /**
     * 擬似的なセッション保存領域を作成します。
     * また、テストの実行環境に依存しないようタイムゾーンを Asia/Tokyo に固定します。
     */
    protected function setUp(): void
    {
        $datadir = TEST_DATA_DIR . "/Web/Session/FileSessionContainer";
        $tmpdir  = "{$datadir}/tmp";
        $logdir  = "{$datadir}/logs";
        TestHelper::cleanDirectory($tmpdir);
        TestHelper::cleanDirectory($logdir);
        TestHelper::copyDirectory("{$datadir}/subjects", $tmpdir);
        touch("{$tmpdir}/sess_1234567890abcdef", 1500009000);
        touch("{$tmpdir}/sess_1357924680bbbbbb", 1500005000);
        touch("{$tmpdir}/sess_9876543210aaaaaa", 1500008000);

        $this->tmpdir = $tmpdir;
        $this->logdir = $logdir;

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
     * テスト用の Logger を生成して返します。
     *
     * @return Logger テスト用の Logger オブジェクト
     */
    private function getLogger(): Logger
    {
        return (new LoggerBuilder())
            ->setClock(new FixedClock(1500010000))
            ->setStorage(new FileLogStorage($this->logdir))
            ->setLogLevel(Logger::LEVEL_ALERT)
            ->build();
    }

    /**
     * 存在しないディレクトリを指定した場合に FileSystemException がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructFailByInvalidDirectory(): void
    {
        $this->expectException(FileSystemException::class);
        new FileSessionContainer("{$this->tmpdir}/notfound");
    }

    /**
     * 生存期間の設定に応じて、有効期限切れのセッションファイルが正しく削除されることを確認します。
     *
     * @param int $maxAge セッションの生存期間 (秒)
     * @param int $expected 削除されるファイルの期待件数
     * @covers ::__construct
     * @covers ::cleanExpiredSessions
     * @dataProvider provideTestCleanExpiredSessions
     */
    public function testCleanExpiredSessions(int $maxAge, int $expected): void
    {
        $obj = new FileSessionContainer($this->tmpdir, null, new FixedClock(1500010000));
        $this->assertSame($expected, $obj->cleanExpiredSessions($maxAge));
    }

    /**
     * testCleanExpiredSessions() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestCleanExpiredSessions(): array
    {
        return [
            [7200, 0],
            [1800, 2],
            [3600, 1],
        ];
    }

    /**
     * セッション ID と生存期間に基づき、有効なセッションが存在するかどうかが正しく判定されることを確認します。
     *
     * @param string $id セッション ID
     * @param int $maxAge セッションの生存期間 (秒)
     * @param bool $expected 期待される判定結果
     * @dataProvider provideTestContains
     */
    public function testContains(string $id, int $maxAge, bool $expected): void
    {
        $obj = new FileSessionContainer($this->tmpdir, null, new FixedClock(1500010000));
        $this->assertSame($expected, $obj->contains($id, $maxAge));
    }

    /**
     * testContains() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestContains(): array
    {
        return [
            ["1234567890abcdef", 1800, true],
            ["9876543210aaaaaa", 1800, false],
            ["9876543210aaaaaa", 3600, true],
            ["xxxxxxxxxxxxxxxx", 1800, false],
        ];
    }

    /**
     * 有効なセッションファイルが正しくロードされ、ロード後にファイルの更新日時が現在時刻に更新されることを確認します。
     *
     * @param string $id セッション ID
     * @param array $expected 期待されるセッションデータ
     * @covers ::__construct
     * @covers ::load
     * @dataProvider provideTestLoadSuccess
     */
    public function testLoadSuccess(string $id, array $expected): void
    {
        $obj = new FileSessionContainer($this->tmpdir, null, new FixedClock(1500010000));
        $this->assertSame($expected, $obj->load($id));

        $filename = "{$this->tmpdir}/sess_{$id}";
        clearstatcache(true, $filename);
        $this->assertSame(1500010000, filemtime($filename));
    }

    /**
     * testLoadSuccess() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestLoadSuccess(): array
    {
        return [
            ["1234567890abcdef", ["hoge" => 123, "fuga" => "asdf"]],
            ["1357924680bbbbbb", ["a" => ["x", "yy", "zzz"], "b" => ["hoge" => 12, "fuga" => 345]]],
        ];
    }

    /**
     * 存在しないセッション ID を読み込もうとした場合に、空の配列が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::load
     */
    public function testLoadFailByNotExistingId(): void
    {
        $obj = new FileSessionContainer($this->tmpdir);
        $this->assertSame([], $obj->load("xxxxxxxxxxxxxxxx"));
    }

    /**
     * フォーマットが不正なセッションファイルを読み込もうとした場合に、エラーログが記録され空の配列が返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::load
     */
    public function testLoadFailByInvalidFormat(): void
    {
        $obj = new FileSessionContainer($this->tmpdir, $this->getLogger());
        $this->assertSame([], $obj->load("9876543210aaaaaa"));

        $expectedLog = "[2017-07-14 14:26:40][ALERT] Failed to parse session for ID '9876543210aaaaaa'";
        $file        = file("{$this->logdir}/app-20170714.log", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertSame($expectedLog, $file[0]);
    }

    /**
     * セッションデータが指定したフォーマットで正しくファイルに保存されることを確認します。
     *
     * @covers ::save
     * @covers ::<private>
     */
    public function testSave(): void
    {
        $obj      = new FileSessionContainer($this->tmpdir);
        $expected = 'hoge|i:456;fuga|s:4:"asdf";piyo|b:1;';
        $result   = $obj->save("1234567890abcdef", ["hoge" => 456, "fuga" => "asdf", "piyo" => true]);
        $this->assertTrue($result);
        $this->assertSame($expected, trim(file_get_contents("{$this->tmpdir}/sess_1234567890abcdef")));
    }

    /**
     * ディレクトリ削除等により保存に失敗した場合、false が返されエラーログが記録されることを確認します。
     *
     * @covers ::save
     * @covers ::<private>
     */
    public function testSaveFail(): void
    {
        set_error_handler(function () {});

        // 存在しないディレクトリをセッションの保存先とする FileSessionContainer を作成します
        $delDir = "{$this->tmpdir}/deldir";
        mkdir($delDir);
        $obj    = new FileSessionContainer($delDir, $this->getLogger());
        rmdir($delDir);

        $result      = $obj->save("1234567890abcdef", ["hoge" => 456, "fuga" => "asdf", "piyo" => true]);
        $this->assertFalse($result);
        $expectedLog = "[2017-07-14 14:26:40][ALERT] Failed to save session to '{$delDir}/sess_1234567890abcdef'";
        $this->assertSame($expectedLog, trim(file_get_contents("{$this->logdir}/app-20170714.log")));

        restore_error_handler();
    }
}
