<?php

namespace Woof\Web\Session;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use TestHelper;
use Woof\Http\Request;
use Woof\Http\RequestBuilder;
use Woof\System\ArrayRandom;
use Woof\System\FixedClock;

/**
 * SessionStorage のテストです。
 *
 * このテストクラスでは、セッションデータのファイル入出力の挙動を検証するため、
 * setUp() にて一時ディレクトリの作成とテスト用ファイルの配置を行っています。
 *
 * @coversDefaultClass Woof\Web\Session\SessionStorage
 */
class SessionStorageTest extends TestCase
{
    /**
     * テスト用の一時ディレクトリのパスです。
     *
     * @var string
     */
    private $tmpdir;

    /**
     * 擬似的なセッション保存領域を作成し、テスト用ファイルを配置します。
     */
    protected function setUp(): void
    {
        $datadir = TEST_DATA_DIR . "/Web/Session/SessionStorage";
        $tmpdir  = "{$datadir}/tmp";
        TestHelper::cleanDirectory($tmpdir);
        TestHelper::copyDirectory("{$datadir}/subjects", $tmpdir);
        touch("{$tmpdir}/sess_1234567890abcdef", 1555555000);
        touch("{$tmpdir}/sess_1357924680bbbbbb", 1555550000);
        touch("{$tmpdir}/sess_9876543210aaaaaa", 1555555123);
        clearstatcache();

        $this->tmpdir = $tmpdir;
    }

    /**
     * テスト用の SessionStorageBuilder を生成して返します。
     *
     * @return SessionStorageBuilder テスト用の SessionStorageBuilder
     */
    private function createTestBuilder(): SessionStorageBuilder
    {
        $clock = new FixedClock(1555555555);
        return (new SessionStorageBuilder())
            ->setSessionContainer(new FileSessionContainer($this->tmpdir, null, $clock))
            ->setKey("sess_id")
            ->setMaxAge(900)
            ->setClock($clock);
    }

    /**
     * テスト用の SessionStorage を生成して返します。
     *
     * @return SessionStorage テスト用の SessionStorage オブジェクト
     */
    private function createTestObject(): SessionStorage
    {
        return $this->createTestBuilder()->build();
    }

    /**
     * テスト用の Request オブジェクトを生成して返します。
     *
     * @param string $sessionId リクエストに付与するセッション ID
     * @return Request テスト用の Request オブジェクト
     */
    private function createTestRequest(string $sessionId = ""): Request
    {
        $builder = (new RequestBuilder())->setHost("example.com");
        if (strlen($sessionId)) {
            $builder->setCookie("sess_id", $sessionId);
        }
        return $builder->build();
    }

    /**
     * SessionContainer またはセッションキーが未設定の状態で build() を実行した場合に
     * LogicException がスローされることを確認します。
     *
     * @param SessionStorageBuilder $builder 不完全なビルダー
     * @dataProvider provideTestNewInstanceFail
     * @covers ::__construct
     * @covers ::newInstance
     */
    public function testNewInstanceFail(SessionStorageBuilder $builder): void
    {
        $this->expectException(LogicException::class);
        $builder->build();
    }

    /**
     * testNewInstanceFail() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestNewInstanceFail(): array
    {
        return [
            [(new SessionStorageBuilder())->setKey("session_id")],
            [(new SessionStorageBuilder())->setSessionContainer(new FileSessionContainer(TEST_DATA_DIR))],
        ];
    }

    /**
     * 設定された SessionContainer が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSessionContainer
     */
    public function testGetSessionContainer(): void
    {
        $expected = new FileSessionContainer($this->tmpdir, null, new FixedClock(1555555555));
        $this->assertEquals($expected, $this->createTestObject()->getSessionContainer());
    }

    /**
     * 設定されたセッションキーが正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getKey
     */
    public function testGetKey(): void
    {
        $this->assertSame("sess_id", $this->createTestObject()->getKey());
    }

    /**
     * 設定された有効期間が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getMaxAge
     */
    public function testGetMaxAge(): void
    {
        $this->assertSame(900, $this->createTestObject()->getMaxAge());
    }

    /**
     * 設定されたガベージコレクションの実行確率が正しく取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getGcProbability
     */
    public function testGetGcProbability(): void
    {
        $obj = $this->createTestBuilder()->setGcProbability(0.125)->build();
        $this->assertSame(0.125, $obj->getGcProbability());
    }

    /**
     * 存在しないセッション ID を持つリクエストを受け取った場合、
     * 新規フラグが true となり、新たな ID が割り当てられた Session オブジェクトが返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSession
     * @covers ::<private>
     */
    public function testGetSessionByUnknownId(): void
    {
        $request = $this->createTestRequest("1234567890ffffff");
        $obj     = $this->createTestObject();
        $session = $obj->getSession($request);
        $this->assertTrue($session->isNew());
        $this->assertNotSame("1234567890ffffff", $session->getId());
    }

    /**
     * 不正な形式のセッション ID を持つリクエストを受け取った場合、新規セッションを生成して返すことを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSession
     * @covers ::<private>
     */
    public function testGetSessionByInvalidRequest(): void
    {
        $request = $this->createTestRequest("this is invalid/session/key");
        $obj     = $this->createTestObject();
        $session = $obj->getSession($request);
        $this->assertTrue($session->isNew());
        $this->assertNotSame("this is invalid/session/key", $session->getId());
    }

    /**
     * 破損したセッションファイルの ID を受け取った場合、データが空の Session オブジェクトが返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSession
     * @covers ::<private>
     */
    public function testGetSessionByMalformedSession(): void
    {
        $request = $this->createTestRequest("9876543210aaaaaa");
        $obj     = $this->createTestObject();
        $session = $obj->getSession($request);
        $this->assertFalse($session->isNew());
        $this->assertSame([], $session->getAll());
    }

    /**
     * 有効なセッション ID を受け取った場合、保存されているデータを保持する Session オブジェクトが返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSession
     * @covers ::<private>
     */
    public function testGetSessionReturnsSavedSession(): void
    {
        $expected = [
            "hoge" => 123,
            "fuga" => "asdf",
        ];

        $request = $this->createTestRequest("1234567890abcdef");
        $obj     = $this->createTestObject();
        $session = $obj->getSession($request);
        $this->assertFalse($session->isNew());
        $this->assertSame("1234567890abcdef", $session->getId());
        $this->assertSame($expected, $session->getAll());
    }

    /**
     * 有効期限切れのセッション ID を受け取った場合、新たな ID が割り当てられた新規セッションが返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSession
     * @covers ::<private>
     */
    public function testGetSessionByExpiredSession(): void
    {
        $request = $this->createTestRequest("1357924680bbbbbb");
        $obj     = $this->createTestObject();
        $session = $obj->getSession($request);
        $this->assertTrue($session->isNew());
        $this->assertNotSame("1357924680bbbbbb", $session->getId());
    }

    /**
     * 同一リクエスト (あるいは同一の SessionStorage インスタンス) に対して getSession() を複数回呼び出した場合、
     * キャッシュされた同一の Session オブジェクトが返されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSession
     * @covers ::<private>
     */
    public function testGetSessionReturnsCache(): void
    {
        $req1 = $this->createTestRequest();
        $obj  = $this->createTestObject();
        $s1   = $obj->getSession($req1);
        $id   = $s1->getId();

        $req2 = $this->createTestRequest($id);
        $s2   = $obj->getSession($req2);

        $this->assertSame($s1, $s2);
    }

    /**
     * 指定した ID の有効なセッションが存在する場合、そのデータを持つ Session オブジェクトが取得できることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSessionById
     */
    public function testGetSessionById(): void
    {
        $expected = [
            "hoge" => 123,
            "fuga" => "asdf",
        ];

        $obj     = $this->createTestObject();
        $session = $obj->getSessionById("1234567890abcdef");
        $this->assertFalse($session->isNew());
        $this->assertSame("1234567890abcdef", $session->getId());
        $this->assertSame($expected, $session->getAll());
    }

    /**
     * 指定した ID のセッションが存在しない場合、その ID を持つ新規セッションが作成されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSessionById
     * @covers ::<private>
     */
    public function testGetSessionByIdByUnknownId(): void
    {
        $obj     = $this->createTestObject();
        $session = $obj->getSessionById("1234567890ffffff");
        $this->assertTrue($session->isNew());
        $this->assertSame("1234567890ffffff", $session->getId());
        $this->assertTrue($session->isEmpty());
    }

    /**
     * 指定した ID のセッションが有効期限切れの場合、その ID を持つ新規セッションが作成されることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSessionById
     * @covers ::<private>
     */
    public function testGetSessionByIdByExpiredId(): void
    {
        $obj     = $this->createTestObject();
        $session = $obj->getSessionById("1357924680bbbbbb");
        $this->assertTrue($session->isNew());
        $this->assertSame("1357924680bbbbbb", $session->getId());
        $this->assertTrue($session->isEmpty());
    }

    /**
     * 不正な形式のセッション ID を指定した場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSessionById
     */
    public function testGetSessionByIdFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = $this->createTestObject();
        $obj->getSessionById("invalid session id");
    }

    /**
     * セッションデータの保存処理が正しく行われ、ファイルに書き込まれることを確認します。
     *
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::save
     * @covers ::<private>
     */
    public function testSave()
    {
        $request  = $this->createTestRequest("1234567890abcdef");
        $expected = 'hoge|i:456;fuga|s:4:"asdf";piyo|b:1;';
        $obj      = $this->createTestObject();
        $session  = $obj->getSession($request);
        $session->set("hoge", 456);
        $session->set("piyo", true);
        $obj->save($session);
        $this->assertSame($expected, file_get_contents("{$this->tmpdir}/sess_1234567890abcdef"));
    }

    /**
     * セッション取得時に、設定された確率に従って期限切れセッションのガベージコレクションが実行されることを確認します。
     *
     * @param float $gcProbability ガベージコレクションの実行確率
     * @param bool $expected 削除されるかどうかの期待値
     * @dataProvider provideTestGarbageCorrectionExecuted
     * @covers ::__construct
     * @covers ::newInstance
     * @covers ::getSession
     * @covers ::<private>
     */
    public function testGarbageCorrectionExecuted(float $gcProbability, bool $expected): void
    {
        $rand = new ArrayRandom([(int) (mt_getrandmax() * 0.5)]);
        $obj  = $this->createTestBuilder()
            ->setRandom($rand)
            ->setGcProbability($gcProbability)
            ->build();
        $req  = $this->createTestRequest("xxxxxxxxxxxxxxxx");
        $obj->getSession($req);
        $this->assertFileExists("{$this->tmpdir}/sess_1234567890abcdef");
        $this->assertSame($expected, file_exists("{$this->tmpdir}/sess_1357924680bbbbbb"));
    }

    /**
     * testGarbageCorrectionExecuted() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGarbageCorrectionExecuted(): array
    {
        return [
            [1.0, false], // 確実に実行されるので、期限切れセッションは存在しなくなる (false)
            [0.0, true],  // 実行されないので、期限切れセッションは残ったままになる (true)
            [0.25, true],
            [0.75, false],
        ];
    }
}
