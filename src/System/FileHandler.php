<?php

namespace Woof\System;

use InvalidArgumentException;

/**
 * 指定されたディレクトリ内のファイルの読み書きを行うためのクラスです。
 *
 * ファイル操作を特定のベースディレクトリ配下に制限することで、
 * アプリケーションが意図しない領域のファイルを操作してしまうリスクを防ぎます。
 */
class FileHandler
{
    /**
     * ベースとなるディレクトリ名です。
     *
     * @var string
     */
    private $dirname;

    /**
     * 指定されたディレクトリをベースとする FileHandler オブジェクトを生成します。
     *
     * @param string $dirname ベースディレクトリ名
     * @throws InvalidArgumentException ディレクトリ名が指定されなかった場合
     * @throws FileSystemException 指定されたディレクトリが存在しない場合
     */
    public function __construct(string $dirname)
    {
        if (!strlen($dirname)) {
            throw new InvalidArgumentException("Directory name required");
        }
        if (!is_dir($dirname)) {
            throw new FileSystemException("Directory not found: '{$dirname}'");
        }

        $this->dirname = rtrim($dirname, "/");
    }

    /**
     * 指定された相対パスを絶対パスに変換します。
     * このメソッドの挙動は、指定されたパスがファイルシステム上に実際に存在するかどうかとは無関係です。
     *
     * @param string $path ベースディレクトリからの相対パス
     * @return string 解決された絶対パス
     */
    public function formatFullpath(string $path): string
    {
        if (!strlen($path)) {
            throw new InvalidArgumentException("Path required");
        }
        $fixedPath = $this->cleanPath($path);
        if (!strlen($fixedPath)) {
            throw new InvalidArgumentException("Invalid path: '{$path}'");
        }
        return "{$this->dirname}/{$fixedPath}";
    }

    /**
     * パス文字列から不要なスラッシュや階層移動 (".", "..") を取り除いて正規化します。
     *
     * @param string $path 正規化する対象の相対パス
     * @return string 正規化されたパス文字列
     */
    private function cleanPath(string $path): string
    {
        $segments = explode("/", $path);
        $filter   = function (string $str): bool {
            return strlen($str) && ($str !== ".");
        };
        $tmpList  = array_filter($segments, $filter);
        while (true) {
            $index = array_search("..", $tmpList, true);
            if ($index === false) {
                break;
            }
            if ($index === 0) {
                array_shift($tmpList);
            } else {
                array_splice($tmpList, $index -1, 2);
            }
        }
        return implode("/", $tmpList);
    }

    /**
     * 書き込み対象のファイルの親ディレクトリが存在することを保証します。
     * 存在しない場合はディレクトリを再帰的に作成します。
     *
     * @param string $path 書き込み対象のファイルの絶対パス
     * @return bool ディレクトリが存在する、または作成に成功した場合に true
     */
    private function prepareDir(string $path): bool
    {
        $dirname = dirname($path);
        return is_dir($dirname) || mkdir($dirname, 0777, true);
    }

    /**
     * 指定された相対パスに書き込みます。
     *
     * @param string $path 書き込み先の相対パス
     * @param string $contents 書き込む内容
     * @return bool 書き込みに成功した場合に true
     */
    public function put(string $path, string $contents): bool
    {
        $fullpath = $this->formatFullpath($path);
        $this->prepareDir($fullpath);
        return file_put_contents($fullpath, $contents);
    }

    /**
     * 指定された相対パスに引数の内容を追記します。
     * 改行文字の付与はされないため、行を追加したい場合は手動で改行文字を加える必要があります。
     *
     * @param string $path 追記先の相対パス
     * @param string $contents 追記する内容
     * @return bool 追記に成功した場合に true
     */
    public function append(string $path, string $contents): bool
    {
        $fullpath = $this->formatFullpath($path);
        $this->prepareDir($fullpath);
        return file_put_contents($fullpath, $contents, FILE_APPEND);
    }

    /**
     * 指定されたファイルの中身を取得します。
     * ファイルが存在しない場合は空文字列を返します。
     * このメソッドは、ファイルが存在するかどうかを判定することはできません。
     * ファイルの有無を判定する場合は contains() を使用してください。
     *
     * @param string $path 読み込むファイルの相対パス
     * @return string 取得したファイルの内容 (ファイルが存在しない場合は空文字列)
     */
    public function get(string $path): string
    {
        $fullpath = $this->formatFullpath($path);
        return is_file($fullpath) ? file_get_contents($fullpath) : "";
    }

    /**
     * 指定された相対パスのファイルが存在する場合のみ true を返します。
     *
     * @param string $path 存在を確認するファイルの相対パス
     * @return bool ファイルが存在する場合に true
     */
    public function contains(string $path): bool
    {
        return is_file($this->formatFullpath($path));
    }
}
