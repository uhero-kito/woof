<?php

namespace Woof\Http;

/**
 * HTTP リクエストでアップロードされた添付ファイルを表現するクラスです。
 *
 * アップロードされたファイルの一時パス・元のファイル名・エラーコードなどの情報へアクセスする機能を提供します。
 */
class UploadFile
{
    /**
     * クライアントから送信された、添付ファイルの元のファイル名です。
     *
     * @var string
     */
    private $name;

    /**
     * サーバー上に一時的に保管されている添付ファイルの物理パスです。
     *
     * @var string
     */
    private $path;

    /**
     * アップロードの成否をあらわすエラーコードです。 (UPLOAD_ERR_OK など)
     *
     * @var int
     */
    private $errorCode;

    /**
     * 添付ファイルのサイズ (バイト数) です。
     *
     * @var int
     */
    private $size;

    /**
     * 添付ファイルの情報を指定してインスタンスを生成します。
     *
     * @param string $name 添付されたファイルの元のファイル名
     * @param string $path サーバー上に保管されている添付ファイルのパス
     * @param int $errorCode アップロードの成否をあらわすエラーコード
     * @param int $size 添付ファイルのサイズ
     */
    public function __construct(string $name, string $path, int $errorCode, int $size)
    {
        $this->name      = $name;
        $this->path      = $path;
        $this->errorCode = $errorCode;
        $this->size      = $size;
    }

    /**
     * 添付ファイルの元のファイル名を取得します。
     *
     * @return string ファイル名
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * サーバー上に保管されている添付ファイルのパスを取得します。
     *
     * @return string ファイルの保存先パス
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * アップロードの成否をあらわすエラーコードを取得します。
     *
     * @return int エラーコード
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * 添付ファイルのサイズ (バイト数) を取得します。
     *
     * @return int ファイルサイズ
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * 添付ファイルの中身 (コンテンツ) を文字列として読み込んで返します。
     *
     * @return string ファイルのコンテンツ (ファイルが存在しない場合は空文字列)
     */
    public function getContents(): string
    {
        return file_exists($this->path) ? file_get_contents($this->path) : "";
    }
}
