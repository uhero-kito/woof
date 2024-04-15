<?php

namespace Woof;

use InvalidArgumentException;

/**
 * ロケール情報に基づいて対応するリソースのキーを探索・取得する Resources の実装です。
 *
 * 内部に保持した Locale オブジェクトとフォールバックの仕組みを利用して、ローカライズされたリソースを優先的に探索します。
 *
 * ローカライズされたリソースのキーは、下記の要領で管理されます。
 *
 * - ファイル名の拡張子の手前にハイフンおよびロケールに基づく文字列を加えたものがそのロケールのリソースとなります。 (例: `logo.png` => `logo-ja_JP.png`)
 * - 拡張子がない場合は、ファイル名の末尾にハイフンおよびロケールに基づく文字列を加えたものがそのロケールのリソースとなります。 (例: `filename` => `filename-ja_JP`)
 * - ファイル名がドットから始まり、その他のドットがない場合は拡張子がない場合と同等になります。 (例: `.config` => `.config-ja_JP`)
 *
 * ローカライズされたリソースは、下記の要領で探索が行われます。
 *
 * - 保持しているロケールについて、フォールバックしながら探索します。
 * - すべてのロケールの探索が失敗した場合はデフォルトのリソース名に解決します。
 *
 * 例えば保持しているロケールが `ja-JP` であり、かつ `en-US` が連結されている場合は、下記の順番で探索を行います。
 * `index-ja_JP.html` => `index-ja.html` => `index-en_US.html` => `index-en.html` => `index.html`
 */
class LocalizedResources implements Resources
{
    /**
     * 実際のデータ取得を行う Resources です。
     *
     * @var Resources
     */
    private $base;

    /**
     * 探索の優先順位とフォールバックチェーンを持つ Locale オブジェクトです。
     *
     * @var Locale
     */
    private $locale;

    /**
     * ベースとなる Resources オブジェクトおよび Locale オブジェクトから LocalizedResources インスタンスを構築します。
     *
     * @param Resources $base   実際のデータ取得を行う Resources オブジェクト
     * @param Locale    $locale 探索の起点となる Locale オブジェクト
     * @throws InvalidArgumentException 指定された Locale がルートロケールの場合
     */
    public function __construct(Resources $base, Locale $locale)
    {
        if ($locale === Locale::getRoot()) {
            throw new InvalidArgumentException("Locale cannot be the root locale.");
        }

        $this->base   = $base;
        $this->locale = $locale;
    }

    /**
     * ロケールに基づくキーの探索を行い、リソースが存在するかどうかを判定します。
     *
     * @param string $key リソースを特定するためのベースとなるキー文字列
     * @return bool       ロケール対応されたキーあるいは元のキーでリソースが存在する場合
     */
    public function contains(string $key): bool
    {
        $localizedKey = $this->findLocalizedKey($key);
        return strlen($localizedKey) || $this->base->contains($key);
    }

    /**
     * ロケールに基づくキーの探索を行い、リソースの内容を取得します。
     *
     * @param string $key リソースを特定するためのベースとなるキー文字列
     * @return string     ロケール対応されたキーあるいは元のキーに基づくリソースの内容文字列
     */
    public function get(string $key): string
    {
        $base         = $this->base;
        $localizedKey = $this->findLocalizedKey($key);
        if (strlen($localizedKey)) {
            return $base->get($localizedKey);
        }

        return $base->get($key);
    }

    /**
     * 保持している Locale からフォールバックを行いながら、実際に存在するキーを探索します。
     *
     * @param string $key ベースとなるキー文字列
     * @return string     存在するロケール対応のキー文字列 (見つからなかった場合は空文字列)
     */
    private function findLocalizedKey(string $key): string
    {
        // キーをボディ部分と拡張子に分割します
        $parsed = (function (string $k): array {
            $info = pathinfo($k);
            if (!isset($info["extension"]) || $info["filename"] === "") {
                return [$k, ""];
            }

            $ext  = "." . $info["extension"];
            $body = substr($k, 0, -strlen($ext));
            return [$body, $ext];
        })($key);

        $body = $parsed[0];
        $ext  = $parsed[1];

        $currentLocale = $this->locale;
        do {
            $nextKey = $this->formatLocalizedKey($currentLocale, $body, $ext);
            if ($this->base->contains($nextKey)) {
                return $nextKey;
            }
        } while ($currentLocale->canFallback() && ($currentLocale = $currentLocale->fallback()));

        return "";
    }

    /**
     * Locale オブジェクトとキーの構成要素から、ロケール対応された探索用キーを構築します。
     *
     * @param Locale $locale 対象の Locale オブジェクト
     * @param string $body   拡張子を除くベースキー文字列
     * @param string $ext    拡張子文字列 (存在しない場合は空文字)
     * @return string        ロケール対応された探索用キー文字列
     */
    private function formatLocalizedKey(Locale $locale, string $body, string $ext): string
    {
        $underscoredLocale = str_replace("-", "_", (string) $locale);
        return $body . "-" . $underscoredLocale . $ext;
    }
}
