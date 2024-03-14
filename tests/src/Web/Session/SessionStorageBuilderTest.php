<?php

namespace Woof\Web\Session;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Woof\System\ArrayRandom;
use Woof\System\DefaultClock;
use Woof\System\DefaultRandom;
use Woof\System\FixedClock;

/**
 * @coversDefaultClass Woof\Web\Session\SessionStorageBuilder
 */
class SessionStorageBuilderTest extends TestCase
{
    /**
     * SessionContainer の設定・存在確認・取得が正しく機能することを確認します。
     *
     * @covers ::setSessionContainer
     * @covers ::hasSessionContainer
     * @covers ::getSessionContainer
     */
    public function testSessionContainer(): void
    {
        $container = new FileSessionContainer(TEST_DATA_DIR);
        $obj       = new SessionStorageBuilder();
        $this->assertFalse($obj->hasSessionContainer());
        $this->assertSame($obj, $obj->setSessionContainer($container));
        $this->assertTrue($obj->hasSessionContainer());
        $this->assertSame($container, $obj->getSessionContainer());
    }

    /**
     * セッションキー (Cookie 名) の設定と取得が正しく機能することを確認します。
     *
     * @covers ::setKey
     * @covers ::getKey
     */
    public function testGetKeyAndSetKey(): void
    {
        $obj = new SessionStorageBuilder();
        $this->assertSame("", $obj->getKey());
        $this->assertSame($obj, $obj->setKey("sess_id"));
        $this->assertSame("sess_id", $obj->getKey());
    }

    /**
     * 不正な形式のセッションキーを設定しようとした場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::setKey
     */
    public function testSetKeyFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new SessionStorageBuilder();
        $obj->setKey("invali session/key");
    }

    /**
     * 有効期間の設定と取得が正しく機能することを確認します。
     *
     * @covers ::setMaxAge
     * @covers ::getMaxAge
     */
    public function testGetMaxAgeAndSetMaxAge(): void
    {
        $obj = new SessionStorageBuilder();
        $this->assertSame(1800, $obj->getMaxAge());
        $this->assertSame($obj, $obj->setMaxAge(900));
        $this->assertSame(900, $obj->getMaxAge());
    }

    /**
     * 有効期間に 0 以下の値を設定しようとした場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::setMaxAge
     */
    public function testSetMaxAgeFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new SessionStorageBuilder();
        $obj->setMaxAge(-300);
    }

    /**
     * ガベージコレクションの実行確率の設定と取得が正しく機能することを確認します。
     *
     * @param float $p 設定する確率
     * @covers ::setGcProbability
     * @covers ::getGcProbability
     * @dataProvider provideTestSetGcProbabilityAndGetGcProbability
     */
    public function testSetGcProbabilityAndGetGcProbability(float $p): void
    {
        $obj = new SessionStorageBuilder();
        $this->assertSame(0.0, $obj->getGcProbability());
        $this->assertSame($obj, $obj->setGcProbability($p));
        $this->assertSame($p, $obj->getGcProbability());
    }

    /**
     * testSetGcProbabilityAndGetGcProbability() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestSetGcProbabilityAndGetGcProbability(): array
    {
        return [
            [0.25],
            [1.0],
            [0.0],
        ];
    }

    /**
     * ガベージコレクションの実行確率に範囲外 (0 未満、1 超) の値を設定しようとした場合に InvalidArgumentException がスローされることを確認します。
     *
     * @param float $p 範囲外の確率
     * @covers ::setGcProbability
     * @dataProvider provideTestSetGcProbabilityFail
     */
    public function testSetGcProbabilityFail(float $p): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SessionStorageBuilder())->setGcProbability($p);
    }

    /**
     * testSetGcProbabilityFail() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestSetGcProbabilityFail(): array
    {
        return [
            [-0.001],
            [1.001],
        ];
    }

    /**
     * Clock オブジェクトの設定と取得が正しく機能することを確認します。
     *
     * @covers ::setClock
     * @covers ::getClock
     */
    public function testSetClockAndGetClock(): void
    {
        $clock = new FixedClock(1555555555);
        $obj   = new SessionStorageBuilder();
        $this->assertSame(DefaultClock::getInstance(), $obj->getClock());
        $this->assertSame($obj, $obj->setClock($clock));
        $this->assertSame($clock, $obj->getClock());
    }

    /**
     * Random オブジェクトの設定と取得が正しく機能することを確認します。
     *
     * @covers ::setRandom
     * @covers ::getRandom
     */
    public function testSetRandomAndGetRandom(): void
    {
        $random = new ArrayRandom([1, 2, 3]);
        $obj    = new SessionStorageBuilder();
        $this->assertSame(DefaultRandom::getInstance(), $obj->getRandom());
        $this->assertSame($obj, $obj->setRandom($random));
        $this->assertSame($random, $obj->getRandom());
    }

    /**
     * 必要な情報が設定された状態で、正しく SessionStorage インスタンスが構築されることを確認します。
     *
     * @covers ::build
     */
    public function testBuild(): void
    {
        $ss = (new SessionStorageBuilder())
            ->setKey("sess_id")
            ->setSessionContainer(new FileSessionContainer(TEST_DATA_DIR))
            ->build();
        $this->assertInstanceOf(SessionStorage::class, $ss);
    }
}
