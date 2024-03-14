<?php

namespace Woof;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Woof\FallbackResources
 */
class FallbackResourcesTest extends TestCase
{
    /**
     * テストデータが配置されているディレクトリのパスです。
     *
     * @var string
     */
    const TEST_DIR = TEST_DATA_DIR . "/FallbackResources/subjects";

    /**
     * テスト用の FallbackResources インスタンスを生成して返します。
     *
     * @return FallbackResources テスト用のインスタンス
     */
    private function createTestObject(): FallbackResources
    {
        $tmpdir = self::TEST_DIR;
        $pri    = new FileResources("{$tmpdir}/test01");
        $sec    = new FileResources("{$tmpdir}/test02");
        return new FallbackResources($pri, $sec);
    }

    /**
     * プライマリまたはセカンダリからリソースが正しく取得できること (フォールバックが機能すること) を確認します。
     *
     * @param string $key 取得するリソースのキー名
     * @param string $expected 期待されるリソース内容 (文字列)
     * @dataProvider provideTestGet
     * @covers ::__construct
     * @covers ::get
     */
    public function testGet(string $key, string $expected): void
    {
        $this->assertSame($expected, trim($this->createTestObject()->get($key)));
    }

    /**
     * testGet() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestGet(): array
    {
        return [
            ["apple.txt", "THIS IS AN APPLE"],
            ["banana.txt", "This is a banana"],
            ["cherry.txt", "THIS IS A CHERRY"],
        ];
    }

    /**
     * プライマリ・セカンダリのどちらにも存在しないリソースを指定した場合に ResourceNotFoundException がスローされることを確認します。
     *
     * @covers ::__construct
     * @covers ::get
     */
    public function testGetFail(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->createTestObject()->get("grape.txt");
    }

    /**
     * プライマリまたはセカンダリにリソースが存在するかどうかを正しく判定できることを確認します。
     *
     * @param string $key 確認するリソースのキー名
     * @param bool $expected 期待される判定結果
     * @dataProvider provideTestContains
     */
    public function testContains(string $key, bool $expected): void
    {
        $this->assertSame($expected, $this->createTestObject()->contains($key));
    }

    /**
     * testContains() のためのテストデータを提供します。
     *
     * @return array テストデータの配列
     */
    public function provideTestContains(): array
    {
        return [
            ["apple.txt", true],
            ["banana.txt", true],
            ["cherry.txt", true],
            ["pineapple.txt", false],
        ];
    }
}
