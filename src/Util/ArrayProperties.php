<?php

namespace Woof\Util;

use InvalidArgumentException;

/**
 * 連想配列を元に設定値を提供する Properties の実装です。
 */
class ArrayProperties implements Properties
{
    /**
     * @var array
     */
    private $data;

    /**
     * 読み込み元となる配列を指定してオブジェクトを生成します。
     *
     * @param array $data 設定値として使用する連想配列
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * 保持しているすべての設定値を配列として取得します。
     *
     * @return array すべての設定値を含む連想配列
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * ドット区切りのキー名を配列 (セグメント) に分割します。
     *
     * @param string $name ドット区切りのキー名
     * @return array 分割されたキーの配列
     * @throws InvalidArgumentException キー名が空か、または不正な文字が含まれている場合
     */
    private function parseSegments(string $name): array
    {
        if (!strlen($name)) {
            throw new InvalidArgumentException("Config key is not specified");
        }

        $segments = explode(".", $name);
        foreach ($segments as $s) {
            if (!preg_match("/\\A[a-zA-Z0-9_\\-]+\\z/", $s)) {
                throw new InvalidArgumentException("Invalid config key: '{$name}'");
            }
        }
        return $segments;
    }

    /**
     * 指定された名前の設定項目が存在するかどうかを調べます。
     *
     * @param string $name 確認したい設定項目のキー名
     * @return bool 指定された設定項目が存在する場合に true
     */
    public function contains(string $name): bool
    {
        $segments = $this->parseSegments($name);
        return $this->checkBySegments($this->data, $segments);
    }

    /**
     * @param array $arr 検索対象の配列
     * @param array $segments 階層をたどるためのキー配列
     * @return bool 該当する設定が存在する場合に true
     */
    private function checkBySegments(array $arr, array $segments): bool
    {
        $key = array_shift($segments);
        if (!count($segments)) {
            return array_key_exists($key, $arr);
        }

        $next = $arr[$key];
        return is_array($next) ? $this->checkBySegments($next, $segments) : false;
    }

    /**
     * 指定された名前の設定項目を取得します。
     *
     * @param string $name 取得したい設定項目のキー名
     * @param mixed $defaultValue 設定が存在しない場合に返される代替値
     * @return mixed 取得した設定値または代替値
     */
    public function get(string $name, $defaultValue = null)
    {
        $segments = $this->parseSegments($name);
        return $this->fetchBySegments($this->data, $segments, $defaultValue);
    }

    /**
     * @param array $arr 検索対象の配列
     * @param array $segments 階層をたどるためのキー配列
     * @param mixed $defaultValue 代替値
     * @return mixed 取得した設定値または代替値
     */
    private function fetchBySegments(array $arr, array $segments, $defaultValue)
    {
        $key = array_shift($segments);
        if (!array_key_exists($key, $arr)) {
            return $defaultValue;
        }

        $result = $arr[$key];
        if (!count($segments)) {
            return $result;
        }
        return is_array($result) ? $this->fetchBySegments($result, $segments, $defaultValue) : $defaultValue;
    }
}
