<?php

namespace Woof\Http;

use LogicException;

/**
 * HTTP リクエストの情報を保持するデータクラスです。
 *
 * URL, HTTP メソッド, ヘッダー, クエリパラメータ, POST データ, アップロードファイルなどの
 * クライアントから送信されたリクエスト全体の状態をカプセル化します。
 */
class Request
{
    /**
     * アクセスされたホスト名 (例: "example.com") です。
     *
     * @var string
     */
    private $host;

    /**
     * アクセスされた URI (クエリ文字列を含む) です。
     *
     * @var string
     */
    private $uri;

    /**
     * アクセスされた URI のうち、クエリ文字列を除外したパス部分です。
     *
     * @var string
     */
    private $path;

    /**
     * アクセスされたスキーム ("http", "https" など) です。
     *
     * @var string
     */
    private $scheme;

    /**
     * HTTP リクエストメソッド ("get", "post" など) を小文字で保持します。
     *
     * @var string
     */
    private $method;

    /**
     * ヘッダー名 (小文字) をキー, HeaderField オブジェクトを値とする連想配列です。
     *
     * @var HeaderField[]
     */
    private $headerList;

    /**
     * GET パラメータ (クエリ文字列) の連想配列です。
     *
     * @var array
     */
    private $queryList;

    /**
     * POST パラメータの連想配列です。
     *
     * @var array
     */
    private $postList;

    /**
     * クッキー情報の連想配列です。
     *
     * @var array
     */
    private $cookieList;

    /**
     * 添付ファイル名 (パラメータ名) をキー, UploadFile オブジェクトを値とする連想配列です。
     *
     * @var array
     */
    private $fileList;

    /**
     * このクラスは RequestBuilder を使用して初期化します。
     */
    private function __construct()
    {
        $this->headerList = [];
        $this->queryList  = [];
        $this->postList   = [];
        $this->cookieList = [];
        $this->fileList   = [];
    }

    /**
     * RequestBuilder の状態を元に、新しい Request インスタンスを生成します。
     * このメソッドは RequestBuilder::build() から参照されます。
     *
     * @param RequestBuilder $builder 構築済みのビルダーオブジェクト
     * @return Request 生成されたリクエストオブジェクト
     * @throws LogicException ホスト名 (host) が指定されていない場合
     * @ignore
     */
    public static function newInstance(RequestBuilder $builder): self
    {
        if (!strlen($host = $builder->getHost())) {
            throw new LogicException("Host is not specified");
        }
        $scheme = strtolower($builder->getScheme());
        $method = strtolower($builder->getMethod());

        $req             = new self();
        $req->host       = $host;
        $req->scheme     = strlen($scheme) ? $scheme : "http";
        $req->method     = strlen($method) ? $method : "get";
        $req->uri        = $builder->getUri();
        $req->path       = $builder->getPath();
        $req->headerList = $builder->getHeaderList();
        $req->queryList  = $builder->getQueryList();
        $req->postList   = $builder->getPostList();
        $req->cookieList = $builder->getCookieList();
        $req->fileList   = $builder->getUploadFileList();
        return $req;
    }

    /**
     * ホスト名を取得します。
     *
     * @return string ホスト名
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * アクセスされた URL そのものを返します。返り値はクエリ以降の文字列を含みます。
     *
     * @return string クエリ文字列を含む URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * アクセスされた URL のクエリを含まないパス部分を返します。
     *
     * @return string パス文字列
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * アクセスされたスキーム (通常は "http" または "https") を取得します。
     *
     * @return string スキーム名
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * HTTP リクエストメソッドを取得します。返り値は常に小文字となります。
     *
     * @return string HTTP メソッド ("get", "post", "put" など)
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * 指定された名前のヘッダーが存在するかどうか調べます。
     * ヘッダー名の大文字・小文字は区別されません。
     *
     * @param string $name ヘッダー名
     * @return bool 存在する場合のみ true
     */
    public function hasHeader(string $name): bool
    {
        $key = strtolower($name);
        return array_key_exists($key, $this->headerList);
    }

    /**
     * 指定された名前のヘッダーを返します。
     * もしも指定されたヘッダーが存在しない場合は EmptyField を返します。
     * ヘッダー名の大文字・小文字は区別されません。
     *
     * @param string $name 取得したいヘッダー名
     * @return HeaderField HeadField オブジェクト
     */
    public function getHeader(string $name): HeaderField
    {
        $key = strtolower($name);
        return $this->headerList[$key] ?? EmptyField::getInstance();
    }

    /**
     * 設定されているすべてのヘッダーフィールドを配列で取得します。
     *
     * @return HeaderField[] HeaderField の配列 (インデックス配列)
     */
    public function getHeaderList(): array
    {
        return array_values($this->headerList);
    }

    /**
     * 指定された GET パラメータ (クエリ) の値を取得します。
     *
     * 存在しない場合は第 2 引数に指定された代替値を返します。
     *
     * @param string $name パラメータ名
     * @param string|array|null $defaultValue 存在しない場合の代替値
     * @return string|array 指定されたパラメータの値または代替値
     */
    public function getQuery(string $name, $defaultValue = null)
    {
        return $this->queryList[$name] ?? $defaultValue;
    }

    /**
     * すべての GET パラメータを取得します。
     *
     * @return array GET パラメータの連想配列
     */
    public function getQueryList(): array
    {
        return $this->queryList;
    }

    /**
     * 指定された POST パラメータの値を取得します。
     *
     * 存在しない場合は第 2 引数に指定された代替値を返します。
     *
     * @param string $name パラメータ名
     * @param string|array|null $defaultValue 存在しない場合の代替値
     * @return string|array 指定されたパラメータの値または代替値
     */
    public function getPost(string $name, $defaultValue = null)
    {
        return $this->postList[$name] ?? $defaultValue;
    }

    /**
     * すべての POST パラメータを取得します。
     *
     * @return array POST パラメータの連想配列
     */
    public function getPostList(): array
    {
        return $this->postList;
    }

    /**
     * 指定された名前の Cookie の値を取得します。
     *
     * @param string $name Cookie 名
     * @param string|null $defaultValue 存在しない場合の代替値
     * @return string|null Cookie の値、または代替値
     */
    public function getCookie(string $name, string $defaultValue = null)
    {
        return $this->cookieList[$name] ?? $defaultValue;
    }

    /**
     * すべての Cookie を取得します。
     *
     * @return array Cookie の連想配列
     */
    public function getCookieList(): array
    {
        return $this->cookieList;
    }

    /**
     * 指定されたパラメータ名の添付ファイルが存在するかどうか調べます。
     *
     * @param string $name パラメータ名 (フォームの input 要素の name 属性)
     * @return bool 存在する場合に true
     */
    public function hasUploadFile(string $name): bool
    {
        return array_key_exists($name, $this->fileList);
    }

    /**
     * 指定されたパラメータ名の添付ファイルを取得します。
     *
     * @param string $name パラメータ名
     * @return UploadFile 添付ファイルをあらわす UploadFile オブジェクト
     * @throws UploadFileNotFoundException 添付ファイルが存在しない場合
     */
    public function getUploadFile($name): UploadFile
    {
        if (!$this->hasUploadFile($name)) {
            throw new UploadFileNotFoundException("File not uploaded: {$name}");
        }

        return $this->fileList[$name];
    }

    /**
     * すべての添付ファイルを取得します。
     *
     * @return UploadFile[] パラメータ名をキーとした UploadFile の連想配列
     */
    public function getUploadFileList(): array
    {
        return $this->fileList;
    }
}
