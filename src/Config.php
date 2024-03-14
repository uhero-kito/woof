<?php

namespace Woof;

use Woof\Util\ArrayProperties;
use Woof\Util\Properties;

/**
 * アプリケーションの設定値を、指定したデータ型 (int, string, bool など) で安全に取得するためのクラスです。
 *
 * Properties オブジェクトをラップし、設定値が存在しない場合や型が異なる場合の代替値 (デフォルト値) の処理や、
 * 数値の最小値・最大値の制限などを簡潔に記述できます。
 */
class Config
{
    /**
     * @var Properties
     */
    private $properties;

    /**
     * 読み込み元となる Properties オブジェクトを指定して Config オブジェクトを生成します。
     *
     * @param Properties $properties 設定値を提供する Properties オブジェクト
     */
    public function __construct(Properties $properties)
    {
        $this->properties = $properties;
    }

    /**
     * @param mixed $value 判定する値
     * @return bool スカラー値または null の場合に true
     */
    private function checkScalar($value): bool
    {
        return is_scalar($value) || ($value === null);
    }

    /**
     * @param mixed $value 判定する値
     * @return bool 数値として扱える値、または null の場合に true
     */
    private function checkNumber($value): bool
    {
        return is_numeric($value) || is_bool($value) || ($value === null);
    }

    /**
     * @param mixed $value 判定する値
     * @param mixed $min 最小値
     * @param mixed $max 最大値
     * @return mixed 範囲内に収まるように補正された値
     */
    private function getMinMax($value, $min = null, $max = null)
    {
        if ($min !== null && $value < $min) {
            return $min;
        }
        if ($max !== null && $max < $value) {
            return $max;
        }
        return $value;
    }

    /**
     * 指定された名前の設定値を整数 (int) として取得します。
     *
     * 設定値が存在しない場合や数値として扱えない場合は、第 2 引数に指定したデフォルト値を返します。
     * さらに、第 3 引数 (最小値) と第 4 引数 (最大値) を指定することで、取得する値がその範囲内に収まるよう制限を加えることができます。
     *
     * @param string $name 取得したい設定項目のキー名
     * @param int $defaultValue 設定が存在しないか、または数値として扱えない場合の代替値 (デフォルトは 0)
     * @param int|null $min 許容される最小値 (下回った場合はこの値が返されます)
     * @param int|null $max 許容される最大値 (上回った場合はこの値が返されます)
     * @return int 取得した設定値 (整数)
     */
    public function getInt(string $name, int $defaultValue = 0, int $min = null, int $max = null): int
    {
        $result = $this->properties->get($name, $defaultValue);
        $value  = $this->checkNumber($result) ? (int) $result : $defaultValue;
        return $this->getMinMax($value, $min, $max);
    }

    /**
     * 指定された名前の設定値を浮動小数点数 (float) として取得します。
     *
     * 設定値が存在しない場合や数値として扱えない場合は、第 2 引数に指定したデフォルト値を返します。
     * さらに、第 3 引数 (最小値) と第 4 引数 (最大値) を指定することで、取得する値がその範囲内に収まるよう制限を加えることができます。
     *
     * @param string $name 取得したい設定項目のキー名
     * @param float $defaultValue 設定が存在しないか、または数値として扱えない場合の代替値 (デフォルトは 0.0)
     * @param float|null $min 許容される最小値 (下回った場合はこの値が返されます)
     * @param float|null $max 許容される最大値 (上回った場合はこの値が返されます)
     * @return float 取得した設定値 (浮動小数点数)
     */
    public function getFloat(string $name, float $defaultValue = 0.0, float $min = null, float $max = null): float
    {
        $result = $this->properties->get($name, $defaultValue);
        $value  = $this->checkNumber($result) ? (float) $result : $defaultValue;
        return $this->getMinMax($value, $min, $max);
    }

    /**
     * 指定された名前の設定値を文字列 (string) として取得します。
     *
     * @param string $name 取得したい設定項目のキー名
     * @param string $defaultValue 設定が存在しないか、またはスカラー値でない場合の代替値 (デフォルトは空文字列)
     * @return string 取得した設定値 (文字列)
     */
    public function getString(string $name, string $defaultValue = ""): string
    {
        $result = $this->properties->get($name, $defaultValue);
        return $this->checkScalar($result) ? $this->scalarToString($result) : $defaultValue;
    }

    /**
     * @param mixed $value 文字列に変換するスカラー値
     * @return string 変換された文字列
     */
    private function scalarToString($value): string
    {
        if ($value === true) {
            return "true";
        }
        if ($value === false) {
            return "false";
        }
        if ($value === null) {
            return "null";
        }
        return (string) $value;
    }

    /**
     * 指定された名前の設定値を配列 (array) として取得します。
     *
     * @param string $name 取得したい設定項目のキー名
     * @param array $defaultValue 設定が存在しない、または配列でない場合の代替値 (デフォルトは空の配列)
     * @return array 取得した設定値 (配列)
     */
    public function getArray(string $name, array $defaultValue = []): array
    {
        $result = $this->properties->get($name, $defaultValue);
        return is_array($result) ? $result : $defaultValue;
    }

    /**
     * 指定された名前の設定値 (配列) をラップした新しい Config オブジェクトを取得します。
     *
     * 基本的な挙動は getArray() と同じですが、特定の階層以下を独立した Config オブジェクトとして取り回したい場合に使用します。
     *
     * @param string $name 取得したい設定項目のキー名
     * @param array $defaultValue 設定が存在しない場合の代替値となる配列 (デフォルトは空の配列)
     * @return Config 取得した設定値をラップした新しい Config オブジェクト
     */
    public function getSubConfig(string $name, array $defaultValue = []): Config
    {
        return new Config(new ArrayProperties($this->getArray($name, $defaultValue)));
    }

    /**
     * 指定された名前の設定値を論理値 (bool) として取得します。
     *
     * 文字列の "true", "yes", "on" は true に、"false", "no", "off" は false に変換されます (大文字小文字は区別しません)。
     *
     * @param string $name 取得したい設定項目のキー名
     * @param bool $defaultValue 設定が存在しない、または論理値として解釈できない場合の代替値 (デフォルトは false)
     * @return bool 取得した設定値 (論理値)
     */
    public function getBool(string $name, bool $defaultValue = false): bool
    {
        $result = $this->properties->get($name, $defaultValue);
        if (is_bool($result)) {
            return $result;
        }
        if (is_string($result)) {
            return $this->stringToBool($result, $defaultValue);
        }
        return $defaultValue;
    }

    /**
     * @param string $value 変換対象の文字列
     * @param bool $defaultValue 変換できない場合の代替値
     * @return bool 変換された論理値
     */
    private function stringToBool(string $value, bool $defaultValue): bool
    {
        $str   = strtolower($value);
        $words = [
            "true"  => true,
            "false" => false,
            "yes"   => true,
            "no"    => false,
            "on"    => true,
            "off"   => false,
        ];
        return $words[$str] ?? $defaultValue;
    }

    /**
     * 指定された名前の設定項目が存在するかどうかを調べます。
     *
     * @param string $name 確認したい設定項目のキー名
     * @return bool 指定された設定項目が存在する場合に true
     */
    public function contains(string $name): bool
    {
        return $this->properties->contains($name);
    }
}
