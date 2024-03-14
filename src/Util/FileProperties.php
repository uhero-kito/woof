<?php

namespace Woof\Util;

use InvalidArgumentException;
use Woof\System\FileHandler;
use Woof\Util\IniDecoder;
use Woof\Util\JsonDecoder;
use Woof\Util\StringDecoder;

/**
 * 特定のディレクトリ内に配置されたファイル群から設定値を取得する Properties の実装です。
 *
 * INI や JSON などのファイルを読み込み、ファイル名 (拡張子を除く) を第 1 階層のキー名として扱います。
 * 例えば "config/database.json" に保管されている値は "database.xxx" というキー名でアクセスできます。
 */
class FileProperties implements Properties
{
    /**
     * @var FileHandler
     */
    private $handler;

    /**
     * キーが文字列, 値が ArrayProperties となる配列です。
     *
     * @var array
     */
    private $data;

    /**
     * ファイルの有無をチェックするための変数です。
     * contains() 内で、該当ファイルが存在しないことがあらかじめわかっている場合に、
     * 続きの処理を打ち切って即座に false を返すために使用します。
     *
     * @var array
     */
    private $files;

    /**
     * 拡張子と対応する StringDecoder の連想配列です。
     *
     * @var StringDecoder[]
     */
    private $decList;

    /**
     * 読み込み対象のディレクトリを指定してオブジェクトを生成します。
     *
     * @param string $dirname 設定ファイルが保管されているディレクトリのパス
     * @param array $decoderList 拡張子と対応する StringDecoder の連想配列 (省略時は INI および JSON 用のデコーダが適用されます)
     */
    public function __construct(string $dirname, array $decoderList = [])
    {
        $this->handler = new FileHandler($dirname);
        $this->data    = [];
        $this->files   = [];
        $this->decList = $this->initDecoderList($decoderList);
    }

    /**
     * デフォルトで適用されるファイル拡張子とデコーダの組み合わせを返します。
     * 返り値の配列は "ini" および "json" をキーに持ち、それぞれ IniDecoder および JsonDecoder
     * インスタンスを値として持ちます。
     *
     * @return array 拡張子をキーとする StringDecoder の連想配列
     */
    public static function getDefaultStringDecoderList(): array
    {
        return [
            "ini"  => IniDecoder::getInstance(),
            "json" => JsonDecoder::getInstance(),
        ];
    }

    /**
     * @param array $decoderList 指定されたデコーダリスト
     * @return array フィルタリングされた有効なデコーダリスト (無効な場合はデフォルトの StringDecoder のリスト)
     */
    private function initDecoderList(array $decoderList): array
    {
        $callback = function ($value, string $key): bool {
            if (!preg_match("/\\A[a-zA-Z0-9]+\\z/", $key)) {
                return false;
            }
            return ($value instanceof StringDecoder);
        };

        $result = array_filter($decoderList, $callback, ARRAY_FILTER_USE_BOTH);
        return count($result) ? $result : self::getDefaultStringDecoderList();
    }

    /**
     * @param string $basename 拡張子を除いたファイル名
     * @return ArrayProperties 該当するファイルの設定値を保持する ArrayProperties
     */
    private function getProperties(string $basename): ArrayProperties
    {
        return $this->data[$basename];
    }

    /**
     * 指定された名前の設定項目が存在するかどうかを調べます。
     *
     * @param string $key 確認したい設定項目のキー名
     * @return bool 指定された設定項目が存在する場合に true
     */
    public function contains(string $key): bool
    {
        list($basename, $sub) = $this->parseSegments($key);
        $this->initBasename($basename);
        if (!$this->files[$basename]) {
            return false;
        }
        return strlen($sub) ? $this->getProperties($basename)->contains($sub) : true;
    }

    /**
     * 指定された名前の設定項目を取得します。
     *
     * @param string $key 取得したい設定項目のキー名
     * @param mixed $defaultValue 設定が存在しない場合に返される代替値
     * @return mixed 取得した設定値または代替値
     */
    public function get(string $key, $defaultValue = null)
    {
        list($basename, $sub) = $this->parseSegments($key);
        $this->initBasename($basename);
        if (!$this->files[$basename]) {
            return $defaultValue;
        }

        $prop = $this->getProperties($basename);
        return strlen($sub) ? $prop->get($sub, $defaultValue) : $prop->getData();
    }

    /**
     * キー名をファイルベース名と階層キーに分割します。
     *
     * @param string $name ドット区切りのキー名
     * @return array [ファイルベース名, 階層キー] の配列
     * @throws InvalidArgumentException キー名が不正な場合
     */
    private function parseSegments(string $name): array
    {
        if (!strlen($name)) {
            throw new InvalidArgumentException("Config key is not specified");
        }

        $matched = [];
        $seg     = "[a-zA-Z0-9_\\-]+";
        if (!preg_match("/\\A({$seg})(\\.{$seg})*\\z/", $name, $matched)) {
            throw new InvalidArgumentException("Invalid config key: '{$name}'");
        }
        $basename = $matched[1];
        $suffix   = substr($name, strlen($basename) + 1);
        return [$basename, $suffix];
    }

    /**
     * 対象のファイルを読み込み、内部にキャッシュします。
     *
     * @param string $basename 拡張子を除いたファイル名
     */
    private function initBasename(string $basename): void
    {
        if (array_key_exists($basename, $this->files)) {
            return;
        }

        $handler = $this->handler;
        $result  = [];
        $exists  = false;
        foreach ($this->decList as $ext => $decoder) {
            $filename = "{$basename}.{$ext}";
            if (!$handler->contains($filename)) {
                continue;
            }
            $arr    = $decoder->parse($handler->get($filename));
            $result = array_merge($result, $arr);
            $exists = true;
        }

        $this->files[$basename] = $exists;
        if ($exists) {
            $this->data[$basename] = new ArrayProperties($result);
        }
    }
}
