<?php

namespace Woof\Http;

/**
 * HTTP ヘッダーの生の文字列を解析し、適切な HeaderField オブジェクトに変換するクラスです。
 */
class HeaderParser
{
    /**
     * 値が Quality Values 形式 (q-value) となるヘッダー名 (小文字) の一覧です。
     *
     * @var array
     */
    private $qNames;

    /**
     * 値が HTTP-date 形式となるヘッダー名 (小文字) の一覧です。
     *
     * @var array
     */
    private $dNames;

    /**
     * HTTP-date 形式の文字列を解析するための HttpDateFormat です。
     *
     * @var HttpDateFormat
     */
    private $format;

    /**
     * パースの対象とするヘッダー名のリストと、日付解析用のフォーマッタを指定してインスタンスを生成します。
     *
     * @param string[] $qNames Quality Values として扱うヘッダー名の配列 (未指定時はデフォルト値が使用されます)
     * @param string[] $dNames HTTP-date として扱うヘッダー名の配列 (未指定時はデフォルト値が使用されます)
     * @param HttpDateFormat|null $format 日付解析用のフォーマッタ
     */
    public function __construct(array $qNames = [], array $dNames = [], HttpDateFormat $format = null)
    {
        $rawQNames = count($qNames) ? $qNames : self::getDefaultQualityValuesNames();
        $rawDNames = count($dNames) ? $dNames : self::getDefaultHttpDateNames();

        $this->qNames = array_map("strtolower", $rawQNames);
        $this->dNames = array_map("strtolower", $rawDNames);
        $this->format = $format ?? new HttpDateFormat();
    }

    /**
     * Quality Values 形式として扱うヘッダー名のデフォルトリストを取得します。
     *
     * @return array デフォルトのヘッダー名配列
     */
    public static function getDefaultQualityValuesNames(): array
    {
        return [
            "accept",
            "accept-charset",
            "accept-encoding",
            "accept-language",
        ];
    }

    /**
     * HTTP-date 形式として扱うヘッダー名のデフォルトリストを取得します。
     *
     * @return array デフォルトのヘッダー名配列
     */
    public static function getDefaultHttpDateNames(): array
    {
        return [
            "date",
            "if-modified-since",
            "last-modified",
        ];
    }

    /**
     * ヘッダー名と値を解析し、適切な HeaderField インスタンス (QualityValues, HttpDate, TextField のいずれか) を返します。
     *
     * @param string $name ヘッダー名
     * @param string $value ヘッダーの生の値
     * @return HeaderField 解析結果の HeaderField オブジェクト
     */
    public function parse(string $name, string $value): HeaderField
    {
        $lName = strtolower($name);
        $uName = ucwords($name, " -");
        if (in_array($lName, $this->qNames)) {
            return new QualityValues($uName, $this->parseQualityValues($value));
        }
        if (in_array($lName, $this->dNames)) {
            $format = $this->format;
            $time   = $format->parse($value);
            return new HttpDate($uName, $time, $format);
        }

        return new TextField($uName, $value);
    }

    /**
     * Quality Values 形式の生文字列を解析し、項目名をキー, q-value を値とする連想配列に変換します。
     *
     * @param  string $str Quality Values の生文字列
     * @return array 変換された連想配列
     */
    private function parseQualityValues(string $str)
    {
        $values  = preg_split("/\\s*,\\s*/", $str);
        $matched = [];
        $qvList  = [];
        foreach ($values as $item) {
            if (preg_match("/\\A([^;]+)\\s*;\\s*(.+)\\z/", $item, $matched)) {
                $key    = $matched[1];
                $qvalue = self::parseQvalue($matched[2]);
            } else {
                $key    = $item;
                $qvalue = 1.0;
            }
            $qvList[$key] = $qvalue;
        }
        return $qvList;
    }

    /**
     * q-value 形式の文字列 ("q=0.9" など) に含まれる小数部分を float に変換します。
     *
     * @param string $qvalue "q=0.9" のような形式の文字列
     * @return float 変換後の q-value の値。もしも変換に失敗した場合は 1.0
     */
    private function parseQvalue(string $qvalue): float
    {
        $matched = [];
        if (preg_match("/\\Aq\\s*=\\s*([0-9\\.]+)\\z/", $qvalue, $matched)) {
            $val = (float) $matched[1];
            return (0.0 < $val && $val <= 1.0) ? $val : 1.0;
        }
        return 1.0;
    }
}
