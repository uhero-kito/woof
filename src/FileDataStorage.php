<?php

namespace Woof;

use InvalidArgumentException;
use Woof\System\FileHandler;
use Woof\System\FileSystemException;

/**
 * ファイルシステム上のディレクトリを利用してデータの読み書きを行う DataStorage の実装です。
 *
 * ローカルディスク上の特定のディレクトリをベースとして、ファイル単位でデータを保存・取得します。
 */
class FileDataStorage implements DataStorage
{
    /**
     * @var FileHandler
     */
    private $handler;

    /**
     * データを保存するベースディレクトリを指定してオブジェクトを生成します。
     *
     * @param string $dirname ベースとなるディレクトリのパス
     * @throws InvalidArgumentException ディレクトリ名が空文字列の場合
     * @throws FileSystemException 指定されたディレクトリが存在しない場合
     */
    public function __construct(string $dirname)
    {
        $this->handler = new FileHandler($dirname);
    }

    /**
     * 指定されたパスのファイル内容を返します。
     * ファイルが存在しない場合は第 2 引数の値を返します。
     *
     * @param string $key 取得したいファイルの相対パス (キー)
     * @param string $defaultValue ファイルが見つからなかった場合に使用される代替値
     * @return string ファイルの内容、または代替値
     */
    public function get(string $key, string $defaultValue = ""): string
    {
        return $this->handler->contains($key) ? $this->handler->get($key) : $defaultValue;
    }

    /**
     * 指定されたパスのファイルが存在するかどうかを調べます。
     *
     * @param string $path 確認したいファイルの相対パス
     * @return bool ファイルが存在する場合に true
     */
    public function contains(string $path): bool
    {
        return $this->handler->contains($path);
    }

    /**
     * 指定されたパスのファイルに内容を書き込みます。
     *
     * @param string $path 書き込み先の相対パス
     * @param string $contents 書き込む内容
     * @return bool 書き込みに成功した場合に true
     */
    public function put(string $path, string $contents): bool
    {
        return $this->handler->put($path, $contents);
    }

    /**
     * 指定されたパスのファイルの末尾に内容を追記します。
     *
     * @param string $path 追記先の相対パス
     * @param string $contents 追記する内容
     * @return bool 追記に成功した場合に true
     */
    public function append(string $path, string $contents): bool
    {
        return $this->handler->append($path, $contents);
    }

    /**
     * ベースディレクトリを基準として、指定された相対パスを絶対パスに変換します。
     *
     * @param string $path 変換元の相対パス
     * @return string 解決された絶対パス
     */
    public function formatPath(string $path): string
    {
        return $this->handler->formatFullpath($path);
    }
}
