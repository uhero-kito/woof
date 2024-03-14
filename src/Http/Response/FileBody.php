<?php

namespace Woof\Http\Response;

use Woof\System\FileSystemException;

/**
 * 指定されたファイルのデータをそのままレスポンスボディとして送信するクラスです。
 */
class FileBody implements Body
{
    /**
     * 読み込み対象となるファイルのパスです。
     *
     * @var string
     */
    private $filename;

    /**
     * 出力する Content-Type の値です。
     *
     * @var string
     */
    private $contentType;

    /**
     * 送信するファイルパスと Content-Type を指定して FileBody インスタンスを生成します。
     *
     * @param string $filename 送信するファイルのパス
     * @param string $contentType 出力する Content-Type の値
     * @throws FileSystemException 対象のファイルが存在しないか、または読み込めない場合
     */
    public function __construct(string $filename, string $contentType)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new FileSystemException("File not found: {$filename}");
        }
        $this->filename    = $filename;
        $this->contentType = $contentType;
    }

    /**
     * ファイルの内容を文字列として読み込んで返します。
     *
     * @return string ファイルのコンテンツ
     */
    public function getOutput(): string
    {
        return file_get_contents($this->filename);
    }

    /**
     * ファイルの内容を直接クライアントに送信します。
     * 内部的には readfile() を使用することで、メモリ効率を考慮して出力を行います。
     *
     * @return bool 送信に成功した場合は true
     */
    public function sendOutput(): bool
    {
        return (readfile($this->filename) !== false);
    }

    /**
     * 設定された Content-Type の値を返します。
     *
     * @return string Content-Type の値
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * 送信するファイルのサイズ (バイト数) を返します。
     *
     * @return int ファイルサイズ
     */
    public function getContentLength(): int
    {
        return filesize($this->filename);
    }
}
