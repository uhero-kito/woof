<?php

namespace Woof\Http;

use InvalidArgumentException;

/**
 * 品質係数 (q-value) による複数項目の優先順位を指定するための HeaderField の実装です。
 *
 * Accept-Language や Accept-Encoding のように、カンマ区切りで複数の値を持ち、それぞれに
 * `q=0.8` のような優先度が付与されるヘッダーを取り扱うために使用します。
 */
class QualityValues implements HeaderField
{
    /**
     * ヘッダー名をあらわします。
     *
     * @var string
     */
    private $name;

    /**
     * 項目名をキー, q-value を値とする連想配列です。
     *
     * @var array
     */
    private $qvalueList;

    /**
     * ヘッダー名と、優先順位を定義した配列を指定してインスタンスを生成します。
     *
     * 指定された配列は、生成時に q-value (値) の降順で自動的にソートされます。
     *
     * @param string $name ヘッダー名
     * @param array $qvalueList 項目名をキー, q-value (0.0 - 1.0) を値とする連想配列
     * @throws InvalidArgumentException 空の配列が指定された場合、またはキーや値の形式が不正な場合
     */
    public function __construct(string $name, array $qvalueList)
    {
        if (!count($qvalueList)) {
            throw new InvalidArgumentException("Empty array specified");
        }
        foreach ($qvalueList as $key => $value) {
            $this->validateQvalue($key, $value);
            $qvalueList[$key] = $this->fixQvalue($value);
        }
        arsort($qvalueList);
        $this->name       = $name;
        $this->qvalueList = $qvalueList;
    }

    /**
     * それぞれの項目名と q-value が正しいフォーマット (0 以上 1 以下の数値など) であるか検証します。
     *
     * @param string $key 検証する項目名 (キー)
     * @param mixed $value 検証する q-value
     * @throws InvalidArgumentException フォーマットが不正な場合
     */
    private function validateQvalue(string $key, $value): void
    {
        if (!preg_match("/\\A[a-zA-Z0-9_\\-\\/\\+\\*]+\\z/", $key)) {
            throw new InvalidArgumentException("Invalid key: {$key}");
        }
        if (!$this->checkValue($value)) {
            throw new InvalidArgumentException("Invalid value: {$value}");
        }
    }

    /**
     * 値が 0 から 1 までの範囲に収まる妥当な数値 (またはその文字列表現) であるか判定します。
     *
     * @param mixed $value 判定する値
     * @return bool 妥当な値である場合に true
     */
    private function checkValue($value)
    {
        if (is_float($value) && 0 <= $value && $value <= 1.0) {
            return true;
        }
        $str = (string) $value;
        if ($str === "0" || $str === "1") {
            return true;
        }
        if (preg_match("/\\A1\\.0{1,3}\\z/", $str)) {
            return true;
        }
        if (preg_match("/\\A0?\\.[0-9]{1,3}\\z/", $str)) {
            return true;
        }

        return false;
    }

    /**
     * 数値を小数点以下 3 桁までに丸めるなど, q-value として適切なフォーマットに調整します。
     *
     * @param mixed $value 調整元の q-value
     * @return string 調整済みの q-value (文字列)
     */
    private function fixQvalue($value): string
    {
        $rounded = is_float($value) ? round($value, 3) : $value;
        return (string) $rounded;
    }

    /**
     * 保持している配列データを HTTP ヘッダーとして出力可能な `item;q=0.8, item2;q=0.5` のような文字列形式にフォーマットします。
     *
     * @return string フォーマットされたヘッダー値の文字列
     */
    public function format(): string
    {
        $callback = function ($key, $value) {
            $v = (float) $value;
            return $v === 1.0 ? $key : "{$key};q={$value}";
        };
        $qvalueList = $this->qvalueList;
        return implode(",", array_map($callback, array_keys($qvalueList), array_values($qvalueList)));
    }

    /**
     * 設定されたヘッダー名を返します。
     *
     * @return string ヘッダー名
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * q-value の降順にソートされた連想配列を返します。
     *
     * @return array 項目名をキー、q-value を値とする連想配列
     */
    public function getValue(): array
    {
        return $this->qvalueList;
    }
}
