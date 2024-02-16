<?php

namespace Woof\Log;

use PHPUnit\Framework\TestCase;
use TestHelper;
use Woof\System\FixedClock;

/**
 * Logger のテストです。
 *
 * このテストクラスではテスト時に物理ファイルの入出力が発生するため、
 * setUp() で一時ディレクトリのクリーニングを行っています。
 * また、異なる日付におけるファイル名生成の挙動を確認するため、一時的にシステムのタイムゾーンを
 * "Asia/Tokyo" に変更しています。
 *
 * @coversDefaultClass Woof\Log\Logger
 */
class LoggerTest extends TestCase
{
    /**
     * テストデータが配置されるベースディレクトリです。
     *
     * @var string
     */
    const DATA_DIR = TEST_DATA_DIR . "/Log/Logger";

    /**
     * テスト用の LogStorage オブジェクトです。
     *
     * @var LogStorage
     */
    private $storage;

    /**
     * テスト用の一時ファイルを出力するディレクトリのパスです。
     *
     * @var string
     */
    private $tmpdir;

    /**
     * テスト実行前の元のタイムゾーン設定を保持します。
     *
     * @var string
     */
    private $defaultTimezone;

    /**
     * テスト用のディレクトリの準備とタイムゾーンの固定を行います。
     */
    public function setUp(): void
    {
        $datadir = self::DATA_DIR;
        $tmpdir  = "{$datadir}/tmp";
        TestHelper::cleanDirectory($tmpdir);

        $this->tmpdir          = $tmpdir;
        $this->storage         = new FileLogStorage($tmpdir);
        $this->defaultTimezone = ini_set("date.timezone", "Asia/Tokyo");
    }

    /**
     * 固定したタイムゾーンを元の状態に戻します。
     */
    public function tearDown(): void
    {
        ini_set("date.timezone", $this->defaultTimezone);
    }

    /**
     * ログ出力を行わない (NOP) Logger が生成され、メソッド呼び出しが安全に無視されることを確認します。
     *
     * @covers ::getNopLogger
     */
    public function testGetNopLogger(): void
    {
        $obj1 = Logger::getNopLogger();
        $obj2 = Logger::getNopLogger();
        $this->assertSame($obj1, $obj2);
        $this->assertSame(-1, $obj1->getLogLevel());
        $this->assertTrue($obj1->error("test"));
    }

    /**
     * ビルダーで設定されたログレベルが正しく取得できることを確認します。
     *
     * @covers ::newInstance
     * @covers ::__construct
     * @covers ::getLogLevel
     */
    public function testGetLogLevel(): void
    {
        $builder = new LoggerBuilder();
        $builder->setStorage($this->storage);
        $builder->setLogLevel(Logger::LEVEL_INFO);

        $obj = $builder->build();
        $this->assertSame(Logger::LEVEL_INFO, $obj->getLogLevel());
    }

    /**
     * 複数行処理のフラグ (multiple) が正しく設定および取得できることを確認します。
     *
     * @covers ::newInstance
     * @covers ::__construct
     * @covers ::isMultiple
     */
    public function testIsMultiple(): void
    {
        $builder = new LoggerBuilder();
        $builder->setStorage($this->storage);

        $obj1 = $builder->build();
        $this->assertFalse($obj1->isMultiple());

        $builder->setMultiple(false);
        $obj2 = $builder->build();
        $this->assertFalse($obj2->isMultiple());

        $builder->setMultiple(true);
        $obj3 = $builder->build();
        $this->assertTrue($obj3->isMultiple());
    }

    /**
     * LogFormat が正しく設定および取得できることと、未設定時はデフォルトの LogFormat オブジェクトが使われることを確認します。
     *
     * @covers ::newInstance
     * @covers ::__construct
     * @covers ::getFormat
     */
    public function testGetFormat(): void
    {
        $defaultFormat = new DefaultLogFormat();
        $customFormat  = new DefaultLogFormat("Y/m/d H:i:s");
        $builder       = new LoggerBuilder();
        $builder->setStorage($this->storage);

        $obj1 = $builder->build();
        $this->assertEquals($defaultFormat, $obj1->getFormat());

        $builder->setFormat($customFormat);
        $obj2 = $builder->build();
        $this->assertSame($customFormat, $obj2->getFormat());
    }

    /**
     * LogStorage が正しく設定および取得できることと、未設定時は NullLogStorage が使われることを確認します。
     *
     * @covers ::newInstance
     * @covers ::__construct
     * @covers ::getStorage
     */
    public function testGetStorage(): void
    {
        $builder = new LoggerBuilder();
        $obj1    = $builder->build();
        $this->assertSame(NullLogStorage::getInstance(), $obj1->getStorage());
        $builder->setStorage($this->storage);
        $obj2    = $builder->build();
        $this->assertSame($this->storage, $obj2->getStorage());
    }

    /**
     * Clock が正しく設定および取得できることを確認します。
     *
     * @covers ::newInstance
     * @covers ::__construct
     * @covers ::getClock
     */
    public function testGetClock(): void
    {
        $builder = new LoggerBuilder();
        $clock   = new FixedClock(1555555555);
        $builder->setStorage($this->storage);
        $builder->setClock($clock);

        $obj = $builder->build();
        $this->assertSame($clock, $obj->getClock());
    }

    /**
     * 指定されたログレベルを持つテスト用の Logger インスタンスを生成して返します。
     *
     * @param int $level 設定するログレベル
     * @return Logger 生成されたテスト用 Logger インスタンス
     */
    private function getTestObjectByLogLevel(int $level): Logger
    {
        $builder = new LoggerBuilder();
        $builder->setStorage($this->storage);
        $builder->setClock(new FixedClock(1555555555));
        $builder->setLogLevel($level);
        return $builder->build();
    }

    /**
     * 期待されるログファイルが生成されているか (または生成されていないか) を検証します。
     *
     * @param bool $expected ログファイルが存在するべき場合は true
     */
    private function checkLogCreated(bool $expected): void
    {
        $logCreated = file_exists("{$this->tmpdir}/app-20190418.log");
        $this->assertSame($expected, $logCreated);
    }

    /**
     * Logger に設定されたログレベルの値に関わらず、常にログの追記が行われることを確認します。
     *
     * @param int $level テスト対象のログレベル
     * @param bool $expected ログが記録されるべきかどうか
     * @dataProvider provideTestError
     * @covers ::error
     * @covers ::<private>
     */
    public function testError(int $level, bool $expected): void
    {
        $this->getTestObjectByLogLevel($level)->error("test");
        $this->checkLogCreated($expected);
    }

    /**
     * testError() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestError(): array
    {
        return [
            [Logger::LEVEL_ERROR, true],
            [Logger::LEVEL_ALERT, true],
            [Logger::LEVEL_INFO, true],
            [Logger::LEVEL_DEBUG, true],
        ];
    }

    /**
     * Logger に設定されたログレベルが DEBUG, INFO, ALERT の場合のみログの追記が行われることを確認します。
     *
     * @param int $level テスト対象のログレベル
     * @param bool $expected ログが記録されるべきかどうか
     * @dataProvider provideTestAlert
     * @covers ::alert
     * @covers ::<private>
     */
    public function testAlert(int $level, bool $expected): void
    {
        $this->getTestObjectByLogLevel($level)->alert("test");
        $this->checkLogCreated($expected);
    }

    /**
     * testAlert() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestAlert(): array
    {
        return [
            [Logger::LEVEL_ERROR, false],
            [Logger::LEVEL_ALERT, true],
            [Logger::LEVEL_INFO, true],
            [Logger::LEVEL_DEBUG, true],
        ];
    }

    /**
     * Logger に設定されたログレベルが INFO, DEBUG の場合のみログの追記が行われることを確認します。
     *
     * @param int $level テスト対象のログレベル
     * @param bool $expected ログが記録されるべきかどうか
     * @dataProvider provideTestInfo
     * @covers ::info
     * @covers ::<private>
     */
    public function testInfo($level, $expected): void
    {
        $this->getTestObjectByLogLevel($level)->info("test");
        $this->checkLogCreated($expected);
    }

    /**
     * testInfo() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestInfo(): array
    {
        return [
            [Logger::LEVEL_ERROR, false],
            [Logger::LEVEL_ALERT, false],
            [Logger::LEVEL_INFO, true],
            [Logger::LEVEL_DEBUG, true],
        ];
    }

    /**
     * Logger に設定されたログレベルが DEBUG の場合のみログの追記が行われることを確認します。
     *
     * @param int $level テスト対象のログレベル
     * @param bool $expected ログが記録されるべきかどうか
     * @dataProvider provideTestDebug
     * @covers ::debug
     * @covers ::<private>
     */
    public function testDebug(int $level, bool $expected): void
    {
        $this->getTestObjectByLogLevel($level)->debug("test");
        $this->checkLogCreated($expected);
    }

    /**
     * testDebug() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestDebug(): array
    {
        return [
            [Logger::LEVEL_ERROR, false],
            [Logger::LEVEL_ALERT, false],
            [Logger::LEVEL_INFO, false],
            [Logger::LEVEL_DEBUG, true],
        ];
    }

    /**
     * オブジェクトをログに追記した場合は __toString() の結果が書き込まれることを確認します。
     *
     * @covers ::log
     * @covers ::<private>
     */
    public function testLogWithToString(): void
    {
        $sample = new LoggerTest_Sample1();
        $obj    = $this->getTestObjectByLogLevel(Logger::LEVEL_ERROR);
        $obj->error($sample);

        $logPath  = "{$this->tmpdir}/app-20190418.log";
        $this->assertFileExists($logPath);
        $expected = "[2019-04-18 11:45:55][ERROR] THIS_IS_TEST" . PHP_EOL;
        $this->assertSame($expected, file_get_contents($logPath));
    }

    /**
     * __toString() を実装していないオブジェクトをログに追記した場合は print_r による文字列表現が書き込まれることを確認します。
     *
     * @covers ::log
     * @covers ::<private>
     */
    public function testLogWithPlainObject(): void
    {
        $sample = new LoggerTest_Sample2();
        $obj    = $this->getTestObjectByLogLevel(Logger::LEVEL_ERROR);
        $obj->error($sample);

        $logPath  = "{$this->tmpdir}/app-20190418.log";
        $this->assertFileExists($logPath);
        $lines    = [
            "[2019-04-18 11:45:55][ERROR] Woof\\Log\\LoggerTest_Sample2 Object",
            "[2019-04-18 11:45:55][ERROR] (",
            "[2019-04-18 11:45:55][ERROR] )",
        ];
        $expected = implode(PHP_EOL, $lines) . PHP_EOL;
        $this->assertSame($expected, file_get_contents($logPath));
    }

    /**
     * 配列をログに追記した場合は print_r の結果が書き込まれることを確認します。
     *
     * @covers ::log
     * @covers ::<private>
     */
    public function testLogWithArray(): void
    {
        $obj = $this->getTestObjectByLogLevel(Logger::LEVEL_ERROR);
        $obj->error(["hoge" => 1, "fuga" => "test"]);

        $logPath  = "{$this->tmpdir}/app-20190418.log";
        $this->assertFileExists($logPath);
        $lines    = [
            "[2019-04-18 11:45:55][ERROR] Array",
            "[2019-04-18 11:45:55][ERROR] (",
            "[2019-04-18 11:45:55][ERROR]     [hoge] => 1",
            "[2019-04-18 11:45:55][ERROR]     [fuga] => test",
            "[2019-04-18 11:45:55][ERROR] )",
        ];
        $expected = implode(PHP_EOL, $lines) . PHP_EOL;
        $this->assertSame($expected, file_get_contents($logPath));
    }

    /**
     * 文字列以外のスカラー値をログに追記した場合は、それぞれの型に応じた文字列表現が書き込まれることを確認します。
     *
     * @param mixed $value ログに記録するスカラー値
     * @param string $expected 期待される文字列表現
     * @dataProvider provideTestLogWithScalar
     * @covers ::log
     * @covers ::<private>
     */
    public function testLogWithScalar($value, string $expected): void
    {
        $obj = $this->getTestObjectByLogLevel(Logger::LEVEL_ERROR);
        $obj->error($value);

        $logPath   = "{$this->tmpdir}/app-20190418.log";
        $this->assertFileExists($logPath);
        $expected2 = "[2019-04-18 11:45:55][ERROR] {$expected}" . PHP_EOL;
        $this->assertSame($expected2, file_get_contents($logPath));
    }

    /**
     * testLogWithScalar() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestLogWithScalar(): array
    {
        return [
            [true, "(TRUE)"],
            [false, "(FALSE)"],
            [null, "(NULL)"],
            [1.25, "1.25"],
            [-123, "-123"],
        ];
    }

    /**
     * 複数行のメッセージを記録した際の、isMultiple フラグに応じた挙動の違いを確認します。
     *
     * @param bool $multiple 複数行を一度に処理するかどうかのフラグ
     * @param array $expected 期待される出力行の配列
     * @dataProvider provideTestLogByMultiple
     * @covers ::log
     * @covers ::<private>
     */
    public function testLogByMultiple(bool $multiple, array $expected): void
    {
        $lines = [
            "Hello",
            "World",
            "Test",
        ];
        $value = implode(PHP_EOL, $lines);

        $builder = new LoggerBuilder();
        $builder->setStorage($this->storage);
        $builder->setClock(new FixedClock(1555555555));
        $builder->setMultiple($multiple);
        $obj     = $builder->build();
        $obj->error($value);

        $logPath   = "{$this->tmpdir}/app-20190418.log";
        $this->assertFileExists($logPath);
        $expected2 = implode(PHP_EOL, $expected) . PHP_EOL;
        $this->assertSame($expected2, file_get_contents($logPath));
    }

    /**
     * testLogByMultiple() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestLogByMultiple(): array
    {
        $expected1 = [
            "[2019-04-18 11:45:55][ERROR] Hello",
            "[2019-04-18 11:45:55][ERROR] World",
            "[2019-04-18 11:45:55][ERROR] Test",
        ];
        $expected2 = [
            "[2019-04-18 11:45:55][ERROR] Hello",
            "World",
            "Test",
        ];
        return [
            [false, $expected1],
            [true, $expected2],
        ];
    }
}

class LoggerTest_Sample1
{
    /**
     * @return string
     */
    public function __toString(): string
    {
        return "THIS_IS_TEST";
    }
}

class LoggerTest_Sample2
{
}
