<?php

namespace Woof;

use InvalidArgumentException;
use LogicException;

/**
 * ロケール情報を表現するイミュータブルな値オブジェクトです。
 * BCP 47 (RFC 5646) に準拠した言語タグの解析と保持を行います。
 *
 * 内部状態として以下の 4 つの要素を保持します。
 *
 * - language: 言語をあらわす ISO 639 準拠の文字列 (例: "ja", "en")
 * - script: 文字体系をあらわす ISO 15924 準拠の文字列 (例: "Jpan", "Hant")
 * - region: 地域や国をあらわす ISO 3166-1 alpha-2 または UN M.49 準拠の文字列 (例: "JP", "US")
 * - variant: 言語や方言のバリアント (変種) をあらわす文字列 (例: "macos", "1996")
 *
 * また、このクラスは複数のロケールを優先度順に取り扱う機能を備えています。
 *
 * append() メソッドを使用して他の Locale オブジェクトを連結させることで、
 * 自身のフォールバックが限界に達した後に次のロケールへ処理を移譲する「フォールバックチェーン」を構築し、
 * 複数のロケールをひとつの Locale オブジェクトとして透過的に取り回すことができます。
 */
class Locale
{
    /**
     * 言語をあらわす ISO 639 準拠の小文字アルファベット文字列です。
     *
     * @var string
     */
    private $language;

    /**
     * 文字体系をあらわす ISO 15924 準拠の先頭大文字アルファベット文字列です。
     *
     * @var string
     */
    private $script;

    /**
     * 地域・国などをあらわす ISO 3166-1 準拠の大文字アルファベットまたは数字文字列です。
     *
     * @var string
     */
    private $region;

    /**
     * バリアントをあらわす小文字の英数字文字列です。
     *
     * @var string
     */
    private $variant;

    /**
     * 自身のフォールバックが完了した後に探索する次の Locale オブジェクトです。
     *
     * @var Locale|null
     */
    private $next;

    /**
     * 外部からのインスタンス化を禁止することにより、生成経路を parseLocale() または getRoot() に限定します。
     *
     * 引数の文字列は、いずれもバリデーション済の文字列 (または空文字列) が指定されることを想定しています。
     *
     * @param string $language  言語コード文字列
     * @param string $script    文字体系コード文字列
     * @param string $region    地域コード文字列
     * @param string $variant   バリアント
     * @param Locale|null $next 次に探索する Locale オブジェクト (未指定の場合は null)
     */
    private function __construct(string $language, string $script, string $region, string $variant, Locale $next = null)
    {
        $this->language = $language;
        $this->script   = $script;
        $this->region   = $region;
        $this->variant  = $variant;
        $this->next     = $next;
    }

    /**
     * 指定されたロケール文字列を解析して Locale オブジェクトを生成します。
     *
     * ISO 639 (言語), ISO 15924 (文字体系), ISO 3166-1 (地域) の仕様に沿った
     * 各種サブタグのパースおよび形式のバリデーションを行います。
     *
     * @param string $locale 解析対象となる BCP 47 準拠のロケール文字列 (例: "ja-JP", "zh-Hant-TW")
     * @return self          解析に成功した場合、内部に各要素を保持した新しい Locale オブジェクト
     * @throws InvalidArgumentException 与えられたロケール文字列が形式に合致しない場合
     */
    public static function parseLocale(string $locale): self
    {
        if ($locale === "") {
            return self::getRoot();
        }

        $parsedTags = (function (string $rawLocale): array {
            $normalized = str_replace("_", "-", $rawLocale);
            $subtags    = explode("-", $normalized);

            $lang = array_shift($subtags);
            if (!preg_match("/^[a-zA-Z]{2,8}$/", $lang)) {
                throw new InvalidArgumentException("Invalid language subtag: {$lang}");
            }

            $parsedLang    = strtolower($lang);
            $parsedScript  = "";
            $parsedRegion  = "";
            $parsedVariant = "";

            foreach ($subtags as $subtag) {
                $length = strlen($subtag);

                if ($parsedScript === "" && $parsedRegion === "" && $parsedVariant === "" && $length === 4 && preg_match("/^[a-zA-Z]{4}$/", $subtag)) {
                    $parsedScript = ucfirst(strtolower($subtag));
                } elseif ($parsedRegion === "" && $parsedVariant === "" && preg_match("/^([a-zA-Z]{2}|[0-9]{3})$/", $subtag)) {
                    $parsedRegion = strtoupper($subtag);
                } elseif ($parsedVariant === "" && preg_match("/^([a-zA-Z0-9]{5,8}|[0-9][a-zA-Z0-9]{3})$/", $subtag)) {
                    $parsedVariant = strtolower($subtag);
                } else {
                    throw new InvalidArgumentException("Invalid or unsupported subtag: {$subtag} in locale: {$rawLocale}");
                }
            }
            return [$parsedLang, $parsedScript, $parsedRegion, $parsedVariant];
        })($locale);

        return new self($parsedTags[0], $parsedTags[1], $parsedTags[2], $parsedTags[3]);
    }

    /**
     * ルートロケール (どのロケールにも依存しない既定ロケール) を返します。
     *
     * ルートロケールは内部データがすべて空文字列として初期化された Locale オブジェクトとして表現されます。
     * ロケールを取り扱うプログラム内で Null Object として機能します。
     *
     * @return self すべての要素が空文字列に設定された固有の Locale オブジェクト
     */
    public static function getRoot(): self
    {
        // @codeCoverageIgnoreStart
        static $instance = null;
        if ($instance === null) {
            $instance = new self("", "", "", "");
        }
        // @codeCoverageIgnoreEnd

        return $instance;
    }

    /**
     * 内部に保持している言語 (Language) を取得します。
     *
     * @return string ISO 639 準拠の言語コード文字列 (未設定の場合は空文字)
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * 内部に保持している文字体系 (Script) を取得します。
     *
     * @return string ISO 15924 準拠の文字体系コード文字列 (未設定の場合は空文字)
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * 内部に保持している地域 (Region) を取得します。
     *
     * @return string ISO 3166-1 準拠の地域コード文字列 (未設定の場合は空文字)
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * 内部に保持しているバリアント (Variant) を取得します。
     *
     * @return string 言語のバリアントをあらわす文字列 (未設定の場合は空文字)
     */
    public function getVariant(): string
    {
        return $this->variant;
    }

    /**
     * このロケールのフォールバックが完了した後に次に探索する Locale オブジェクトを末尾に連結します。
     *
     * 自身または引数がルートロケールの場合は、有効な側の Locale をそのまま返します。
     *
     * @param Locale $nextLocale 連結する Locale オブジェクト
     * @return self              次の Locale オブジェクトが連結された新しい Locale オブジェクト
     * @throws InvalidArgumentException 循環参照が発生する場合
     */
    public function append(Locale $nextLocale): self
    {
        $root = $this->getRoot();
        if ($this === $root) {
            return $nextLocale;
        }
        if ($nextLocale === $root) {
            return $this;
        }
        if ($this->contains($nextLocale)) {
            throw new InvalidArgumentException("Circular reference detected in Locale chain.");
        }

        // 引数のロケールがフォールバックチェーン内に存在する場合は、重複を避けるため自身をそのまま返します
        if ($this->hasInFallbackChain($nextLocale)) {
            // target にさらに next が連結されている場合は、target の next 以降の連結を試みます
            return ($nextLocale->next !== null) ? $this->append($nextLocale->next) : $this;
        }

        $next = ($this->next === null) ? $nextLocale : $this->next->append($nextLocale);
        return new self($this->language, $this->script, $this->region, $this->variant, $next);
    }

    /**
     * 指定された Locale オブジェクトが、自身のチェーン内に存在するかどうかを判定します。
     *
     * @param Locale $target 探索対象の Locale オブジェクト
     * @return bool          存在する場合は true
     */
    private function contains(Locale $target): bool
    {
        if ($this === $target) {
            return true;
        }
        if ($this->next !== null) {
            return $this->next->contains($target);
        }

        return false;
    }

    /**
     * 指定された Locale が、自身のフォールバックチェーン内にすでに存在するかどうかを判定します。
     *
     * @param Locale $target 判定対象の Locale
     * @return bool          存在する場合は true
     */
    private function hasInFallbackChain(Locale $target): bool
    {
        $targetStr = (string) $target;
        $current   = $this;
        do {
            if ((string) $current === $targetStr) {
                return true;
            }
        } while ($current->canFallback() && ($current = $current->fallback()));

        return false;
    }

    /**
     * 現在のロケールから、一段階抽象度の高いロケールまたは次に連結されたロケールへフォールバック可能かどうかを判定します。
     *
     * @return bool フォールバック可能な場合 (言語以外の要素が存在するか、次のロケールが連結されている場合) は true
     */
    public function canFallback(): bool
    {
        return $this->script !== "" || $this->region !== "" || $this->variant !== "" || $this->next !== null;
    }

    /**
     * 現在のロケールから一段階抽象度の高いロケールへフォールバックした、新しい Locale オブジェクトを生成します。
     *
     * バリアント, 地域, 文字体系の順により詳細な要素から順番に削除されます。
     * 自身の要素が言語のみとなった場合は、連結されている次の Locale オブジェクトを返します。
     *
     * @return self           一段階フォールバックされた新しい Locale オブジェクト
     * @throws LogicException これ以上フォールバックできない (言語のみであり、かつ次のロケールが存在しない) 場合
     */
    public function fallback(): self
    {
        if (!$this->canFallback()) {
            throw new LogicException("Cannot fallback any further.");
        }

        if ($this->variant !== "") {
            return new self($this->language, $this->script, $this->region, "", $this->next);
        }
        if ($this->region !== "") {
            return new self($this->language, $this->script, "", "", $this->next);
        }
        if ($this->script !== "") {
            return new self($this->language, "", "", "", $this->next);
        }

        // 自身の要素が言語のみとなった場合は、次の Locale へと処理を移譲します
        return $this->next;
    }

    /**
     * オブジェクトが文字列としてキャストされた際の振る舞いを定義します。
     *
     * 内部で保持している各要素をハイフン (-) で連結し、BCP 47 準拠のロケール文字列として再構築します。
     *
     * @return string BCP 47 準拠のフォーマットで結合されたロケール文字列
     */
    public function __toString(): string
    {
        $filter = function (string $val): bool {
            return $val !== "";
        };
        $parts  = array_filter([$this->language, $this->script, $this->region, $this->variant], $filter);
        return implode("-", $parts);
    }
}
