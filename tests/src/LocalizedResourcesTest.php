<?php

namespace Woof\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Woof\FileResources;
use Woof\Locale;
use Woof\LocalizedResources;

/**
 * @coversDefaultClass Woof\LocalizedResources
 */
class LocalizedResourcesTest extends TestCase
{
    /**
     * テストデータが配置されているディレクトリのパスです。
     *
     * @var string
     */
    const TEST_DIR = TEST_DATA_DIR . "/LocalizedResources/subjects";

    /**
     * テスト用の LocalizedResources オブジェクトを生成して返します。
     *
     * @param Locale $locale 探索の起点となる Locale オブジェクト
     * @return LocalizedResources
     */
    public function createTestObject(Locale $locale): LocalizedResources
    {
        return new LocalizedResources(new FileResources(self::TEST_DIR), $locale);
    }

    /**
     * ローカライズされたリソースの解決 (contains および get) が正しく行われることを確認します。
     *
     * @param string $key      要求するベースキー文字列
     * @param Locale $locale   起点となる Locale オブジェクト
     * @param string $expected 最終的に取得されるファイルの内容文字列
     * @dataProvider validResourceProvider
     * @covers ::__construct
     * @covers ::contains
     * @covers ::get
     * @covers ::<private>
     */
    public function testContainsAndGet(string $key, Locale $locale, string $expected): void
    {
        $obj = $this->createTestObject($locale);
        $this->assertTrue($obj->contains($key));
        $this->assertSame($expected, trim($obj->get($key)));
    }

    /**
     * 正常にリソースが解決されるパターンのデータプロバイダです。
     *
     * @return array
     */
    public function validResourceProvider(): array
    {
        $jaJp = Locale::parseLocale("ja-JP");
        $enUs = Locale::parseLocale("en-US");

        // テスト用に Locale を連結します
        $jaJpFirst = $jaJp->append($enUs);
        $enUsFirst = $enUs->append($jaJp);

        return [
            "Exact match with extension"               => ["sitename.txt", $jaJpFirst, "sitename ja_JP"],
            "Fallback (ja-JP -> ja) without extension" => ["filename", $jaJpFirst, "filename ja"],
            "Skip first and match second (dotfile)"    => [".config", $jaJpFirst, "config en_US"],
            "Fallback (en-US -> en) with extension"    => ["sitename.txt", $enUsFirst, "sitename en"],
            "Fallback to default resource"             => ["only-default.txt", $jaJpFirst, "only default"],
            "Path with dot in directory"               => ["views/v1.2/index.txt", $jaJpFirst, "views v1.2 index ja_JP"],
            "Path with dot in directory (no ext)"      => ["views/v1.2/filename", $jaJpFirst, "views v1.2 filename ja"],
            "Path with dot in directory (dotfile)"     => ["views/v1.2/.config", $jaJpFirst, "views v1.2 config en_US"],
        ];
    }

    /**
     * ロケールにもデフォルトにも該当リソースが存在しない場合の振る舞いを確認します。
     *
     * @covers ::__construct
     * @covers ::contains
     * @covers ::<private>
     */
    public function testContainsReturnsFalseForMissingResource(): void
    {
        $obj = $this->createTestObject(Locale::parseLocale("ja-JP"));
        $this->assertFalse($obj->contains("missing-file.txt"));
    }

    /**
     * ルートロケールを指定してインスタンス化しようとした際に例外がスローされることを確認します。
     *
     * @covers ::__construct
     */
    public function testConstructorThrowsExceptionForRootLocale(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createTestObject(Locale::getRoot());
    }
}
