<?php

namespace Woof\Test;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Woof\Locale;

/**
 * @coversDefaultClass Woof\Locale
 */
class LocaleTest extends TestCase
{
    /**
     * ロケール文字列が正しく解析され、各 getter および文字列表現が期待通りになることを確認します。
     *
     * @param string $input          入力となるロケール文字列
     * @param string $expectedLang   期待される言語コード
     * @param string $expectedScript 期待される文字体系コード
     * @param string $expectedRegion 期待される地域コード
     * @param string $expectedVar    期待されるバリアント
     * @param string $expectedString 期待される文字列表現
     * @dataProvider validLocaleProvider
     * @covers ::__construct
     * @covers ::parseLocale
     * @covers ::getLanguage
     * @covers ::getScript
     * @covers ::getRegion
     * @covers ::getVariant
     * @covers ::__toString
     */
    public function testParseLocale(string $input, string $expectedLang, string $expectedScript, string $expectedRegion, string $expectedVar, string $expectedString): void
    {
        $locale = Locale::parseLocale($input);

        $this->assertSame($expectedLang, $locale->getLanguage());
        $this->assertSame($expectedScript, $locale->getScript());
        $this->assertSame($expectedRegion, $locale->getRegion());
        $this->assertSame($expectedVar, $locale->getVariant());
        $this->assertSame($expectedString, (string) $locale);
    }

    /**
     * 正常なロケール文字列を解析した場合の各要素と文字列化の期待値を返すデータプロバイダです。
     *
     * @return array
     */
    public function validLocaleProvider(): array
    {
        return [
            // [入力値, language, script, region, variant, __toStringの期待値]
            "Language only"                => ["ja", "ja", "", "", "", "ja"],
            "Language and region"          => ["en-US", "en", "", "US", "", "en-US"],
            "Language, script, and region" => ["zh-Hant-TW", "zh", "Hant", "TW", "", "zh-Hant-TW"],
            "POSIX style (underscore)"     => ["ja_JP", "ja", "", "JP", "", "ja-JP"],
            "Language and variant"         => ["sl-nedis", "sl", "", "", "nedis", "sl-nedis"],
            "All elements included"        => ["zh-Hans-CN-1996", "zh", "Hans", "CN", "1996", "zh-Hans-CN-1996"],
            "Case normalization"           => ["EN-us", "en", "", "US", "", "en-US"],
            "3-digit region code"          => ["es-419", "es", "", "419", "", "es-419"],
        ];
    }

    /**
     * ルートロケールの振る舞いおよび Null Object としての同一性を確認します。
     *
     * @covers ::__construct
     * @covers ::getRoot
     * @covers ::parseLocale
     * @covers ::getLanguage
     * @covers ::getScript
     * @covers ::getRegion
     * @covers ::getVariant
     * @covers ::__toString
     */
    public function testGetRoot(): void
    {
        $obj1 = Locale::getRoot();
        $obj2 = Locale::getRoot();

        // 複数回呼び出しても同一 (same) インスタンスが返ることを確認します
        $this->assertSame($obj1, $obj2);

        // 各要素が空文字列であることを確認します
        $this->assertSame("", $obj1->getLanguage());
        $this->assertSame("", $obj1->getScript());
        $this->assertSame("", $obj1->getRegion());
        $this->assertSame("", $obj1->getVariant());
        $this->assertSame("", (string) $obj1);

        // 空文字列を parseLocale に渡した場合もルートロケールと同一インスタンスになることを確認します
        $parsedEmpty = Locale::parseLocale("");
        $this->assertSame($obj1, $parsedEmpty);
    }

    /**
     * 不正なロケール文字列を解析しようとした際に InvalidArgumentException がスローされることを確認します。
     *
     * @param string $input 入力となる不正なロケール文字列
     * @dataProvider invalidLocaleProvider
     * @covers ::parseLocale
     */
    public function testParseLocaleThrowsException(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        Locale::parseLocale($input);
    }

    /**
     * 形式が不正なロケール文字列のパターンを返すデータプロバイダです。
     *
     * @return array
     */
    public function invalidLocaleProvider(): array
    {
        return [
            "Language code too short"         => ["a"],
            "Language code too long"          => ["abcdefghi"],
            "Language code contains numbers"  => ["123"],
            "Script code too short"           => ["en-Han"],
            "Region code too short"           => ["ja-J"],
            "Region code too long"            => ["ja-JPN"],
            "Unsupported and invalid sub-tag" => ["en-US-invalidsubtagwaytoolong"],
        ];
    }

    /**
     * ロケールのフォールバックが正しく行われることを確認します。
     *
     * @param string $input    入力となるロケール文字列
     * @param string $expected 期待されるフォールバック後のロケール文字列
     * @dataProvider validFallbackProvider
     * @covers ::__construct
     * @covers ::canFallback
     * @covers ::fallback
     */
    public function testFallback(string $input, string $expected): void
    {
        $locale = Locale::parseLocale($input);
        $this->assertTrue($locale->canFallback());

        $fallbackLocale = $locale->fallback();
        $this->assertSame($expected, (string) $fallbackLocale);
    }

    /**
     * 正常にフォールバック可能なロケール文字列と、その期待値を返すデータプロバイダです。
     *
     * @return array
     */
    public function validFallbackProvider(): array
    {
        return [
            "Drop variant"            => ["zh-Hans-CN-1996", "zh-Hans-CN"],
            "Drop region"             => ["zh-Hant-TW", "zh-Hant"],
            "Drop script"             => ["zh-Hant", "zh"],
            "Drop region (no script)" => ["ja-JP", "ja"],
            "Drop variant (no region)"=> ["sl-nedis", "sl"],
        ];
    }

    /**
     * これ以上フォールバックできないロケールで例外がスローされることを確認します。
     *
     * @param string $input 入力となるロケール文字列
     * @dataProvider invalidFallbackProvider
     * @covers ::canFallback
     * @covers ::fallback
     */
    public function testFallbackThrowsException(string $input): void
    {
        $locale = Locale::parseLocale($input);
        $this->assertFalse($locale->canFallback());

        $this->expectException(LogicException::class);
        $locale->fallback();
    }

    /**
     * フォールバック不可能なロケール文字列のパターンを返すデータプロバイダです。
     *
     * @return array
     */
    public function invalidFallbackProvider(): array
    {
        return [
            "Language only" => ["ja"],
            "Root locale"   => [""],
        ];
    }

    /**
     * 複数のロケールを連結 (append) した場合に、
     * 自身のフォールバック限界を超えて次のロケールへ処理が移譲 (チェーン) されることを確認します。
     *
     * @covers ::__construct
     * @covers ::append
     * @covers ::canFallback
     * @covers ::fallback
     * @covers ::<private>
     */
    public function testAppendAndFallbackChain(): void
    {
        $primary   = Locale::parseLocale("zh-Hant-TW");
        $secondary = Locale::parseLocale("en-US");
        $tertiary  = Locale::parseLocale("ja");

        // zh-Hant-TW -> en-US -> ja の順で連結します
        $chained = $primary->append($secondary)->append($tertiary);
        $this->assertSame("zh-Hant-TW", (string) $chained);
        $this->assertTrue($chained->canFallback());

        $fallback1 = $this->assertFallbackStep($chained, "zh-Hant", true);
        $fallback2 = $this->assertFallbackStep($fallback1, "zh", true);
        $fallback3 = $this->assertFallbackStep($fallback2, "en-US", true);
        $fallback4 = $this->assertFallbackStep($fallback3, "en", true);
        $fallback5 = $this->assertFallbackStep($fallback4, "ja", false);

        // これ以上フォールバックできず、最終的に LogicException がスローされることを確認します
        $this->expectException(LogicException::class);
        $fallback5->fallback();
    }

    /**
     * ロケールのフォールバックを実行し、結果の文字列表現とフォールバック可能状態を検証して返します。
     *
     * @param Locale $locale              フォールバックを実行する起点となる Locale オブジェクト
     * @param string $expectedString      フォールバック後に期待される文字列表現
     * @param bool   $expectedCanFallback フォールバック後に期待される canFallback の結果
     * @return Locale                     フォールバックされた新しい Locale オブジェクト
     */
    private function assertFallbackStep(Locale $locale, string $expectedString, bool $expectedCanFallback): Locale
    {
        $fallbackLocale = $locale->fallback();
        $this->assertSame($expectedString, (string) $fallbackLocale);
        $this->assertSame($expectedCanFallback, $fallbackLocale->canFallback());
        return $fallbackLocale;
    }

    /**
     * ルートロケールに対して連結 (append) を行った場合、引数の Locale オブジェクトがそのまま返されることを確認します。
     *
     * @covers ::append
     */
    public function testAppendToRootLocaleReturnsNextLocale(): void
    {
        $root     = Locale::getRoot();
        $target   = Locale::parseLocale("en-US");
        $appended = $root->append($target);

        $this->assertSame($target, $appended);
        $this->assertSame("en-US", (string) $appended);
    }

    /**
     * 任意のロケールにルートロケールを連結 (append) しようとした場合、自身がそのまま返されることを確認します。
     *
     * @covers ::append
     */
    public function testAppendRootLocaleReturnsSelf(): void
    {
        $locale   = Locale::parseLocale("ja-JP");
        $root     = Locale::getRoot();
        $appended = $locale->append($root);

        $this->assertSame($locale, $appended);
        $this->assertSame("ja-JP", (string) $appended);
    }

    /**
     * 連結によって循環参照が発生する場合に InvalidArgumentException がスローされることを確認します。
     *
     * @covers ::append
     * @covers ::<private>
     */
    public function testAppendThrowsExceptionOnCircularReference(): void
    {
        $primary   = Locale::parseLocale("ja-JP");
        $secondary = Locale::parseLocale("en-US");
        $tertiary  = Locale::parseLocale("zh-CN");
        $chained1  = $secondary->append($tertiary);
        $chained2  = $primary->append($chained1);

        $this->expectException(InvalidArgumentException::class);
        $chained2->append($chained1);
    }

    /**
     * 自身のフォールバックチェーンにすでに含まれているロケールを連結 (append) しようとした場合、
     * 重複を避けるために自身がそのまま返されることを確認します。
     *
     * @covers ::append
     * @covers ::<private>
     */
    public function testAppendIgnoresDuplicateLocaleInFallbackChain(): void
    {
        // primary は en-US -> ja-JP になります
        $primary = Locale::parseLocale("en-US")->append(Locale::parseLocale("ja-JP"));

        // "en" は "en-US" のフォールバックチェーンに含まれるため無視され、自身が返ることを確認します
        $appended1 = $primary->append(Locale::parseLocale("en"));
        $this->assertSame($primary, $appended1);

        // "ja" は "ja-JP" のフォールバックチェーンに含まれるため無視され、自身が返ることを確認します
        $appended2 = $primary->append(Locale::parseLocale("ja"));
        $this->assertSame($primary, $appended2);

        // "es" はチェーンに含まれないため、新たに連結されることを確認します
        $appended3 = $primary->append(Locale::parseLocale("es"));
        $this->assertNotSame($primary, $appended3);

        // 期待されるフォールバックの順序が en-US -> en -> ja-JP -> ja -> es になることを確認します
        $chain   = [];
        $current = $appended3;
        do {
            $chain[] = (string) $current;
        } while ($current->canFallback() && ($current = $current->fallback()));

        $this->assertSame(["en-US", "en", "ja-JP", "ja", "es"], $chain);
    }
}
