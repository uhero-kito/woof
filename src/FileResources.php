<?php

namespace Woof;

use InvalidArgumentException;
use Woof\System\FileHandler;
use Woof\System\FileSystemException;

/**
 * 特定のディレクトリに格納されている各種リソースファイルを取得するための Resources の実装です。
 * このクラスは、指定されたベースディレクトリからの相対パスをキーとして扱い、ファイルの内容を取得します。
 */
class FileResources implements Resources
{
    /**
     * @var FileHandler
     */
    private $handler;

    /**
     * リソースが格納されているベースディレクトリを指定してオブジェクトを生成します。
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
     * 指定されたキー (相対パス) に該当するファイルの内容を取得します。
     *
     * @param string $key 取得したいファイルの相対パス
     * @return string ファイルの内容
     * @throws ResourceNotFoundException 指定されたファイルが存在しない場合
     */
    public function get(string $key): string
    {
        if (!$this->handler->contains($key)) {
            throw new ResourceNotFoundException("Resource not found: '{$key}'");
        }
        return $this->handler->get($key);
    }

    /**
     * 指定されたキー (相対パス) のファイルが存在するかどうかを判定します。
     *
     * @param string $key 確認したいファイルの相対パス
     * @return bool ファイルが存在する場合に true
     */
    public function contains(string $key): bool
    {
        return $this->handler->contains($key);
    }

    /**
     * 指定されたキー (相対パス) を、ファイルシステム上の絶対パスに変換します。
     *
     * @param string $key 変換元の相対パス
     * @return string 解決された絶対パス
     */
    public function formatPath(string $key): string
    {
        return $this->handler->formatFullpath($key);
    }
}
