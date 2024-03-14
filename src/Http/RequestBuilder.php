<?php

namespace Woof\Http;

use LogicException;

/**
 * Request オブジェクトを構築するためのビルダークラスです。
 */
class RequestBuilder
{
    /**
     * 設定するホスト名です。
     *
     * @var string
     */
    private $host;

    /**
     * 設定する URI (クエリ文字列を含む) です。
     *
     * @var string
     */
    private $uri;

    /**
     * 設定する URI のパス部分です。
     *
     * @var string
     */
    private $path;

    /**
     * 設定するスキーム ("http" または "https") です。
     *
     * @var string
     */
    private $scheme;

    /**
     * 設定する HTTP メソッドです。
     *
     * @var string
     */
    private $method;

    /**
     * 登録されたヘッダーフィールドの連想配列です。ヘッダー名 (小文字) がキーとなります。
     *
     * @var HeaderField[]
     */
    private $headerList;

    /**
     * GET パラメータの連想配列です。
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
     * クッキーの連想配列です。
     *
     * @var array
     */
    private $cookieList;

    /**
     * 添付ファイルの連想配列です。パラメータ名がキーとなります。
     *
     * @var UploadFile[]
     */
    private $fileList;

    /**
     * 新しい RequestBuilder インスタンスを生成します。
     * 引数に Request オブジェクトを渡すことで、既存の Request の情報をコピーして初期化することができます。
     *
     * @param Request|null $request インポート元の Request オブジェクト
     */
    public function __construct(Request $request = null)
    {
        $this->headerList = [];
        $this->queryList  = [];
        $this->postList   = [];
        $this->cookieList = [];
        $this->fileList   = [];
        if ($request !== null) {
            $this->importRequest($request);
        }
    }

    /**
     * 指定された Request オブジェクトの状態をこのオブジェクトにインポートします。
     *
     * @param Request $request インポートする Request オブジェクト
     */
    private function importRequest(Request $request): void
    {
        $this->host       = $request->getHost();
        $this->uri        = $request->getUri();
        $this->path       = $request->getPath();
        $this->scheme     = $request->getScheme();
        $this->method     = $request->getMethod();
        $this->queryList  = $request->getQueryList();
        $this->postList   = $request->getPostList();
        $this->cookieList = $request->getCookieList();
        $this->fileList   = $request->getUploadFileList();
        foreach ($request->getHeaderList() as $header) {
            $this->setHeader($header);
        }
    }

    /**
     * ホスト名を設定します。
     *
     * @param string $host ホスト名
     * @return RequestBuilder このオブジェクト自身
     */
    public function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    /**
     * 設定されているホスト名を取得します。
     *
     * @return string ホスト名 (未設定時は空文字列)
     */
    public function getHost(): string
    {
        return $this->host ?? "";
    }

    /**
     * URI (クエリ文字列を含む) を設定します。
     *
     * @param string $uri URI 文字列
     * @return RequestBuilder このオブジェクト自身
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * 設定されている URI を取得します。
     *
     * @return string URI 文字列 (未設定時は空文字列)
     */
    public function getUri(): string
    {
        return $this->uri ?? "";
    }

    /**
     * URL のパス (クエリ文字列を含まない) を設定します。
     *
     * @param string $path パス文字列
     * @return RequestBuilder このオブジェクト自身
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * アクセスされた URL のクエリを含まない部分を返します。
     *
     * @return string パス文字列 (未設定時は空文字列)
     */
    public function getPath(): string
    {
        return $this->path ?? "";
    }

    /**
     * スキーム (http, https など) を設定します。
     *
     * @param string $scheme スキーム名 ("http" または "https")
     * @return RequestBuilder このオブジェクト自身
     */
    public function setScheme(string $scheme): self
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * 設定されているスキームを取得します。
     *
     * @return string スキーム名 (未設定時は空文字列)
     */
    public function getScheme(): string
    {
        return $this->scheme ?? "";
    }

    /**
     * HTTP メソッドを設定します。
     *
     * @param string $method HTTP メソッド名
     * @return RequestBuilder このオブジェクト自身
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 設定されている HTTP メソッドを取得します。
     *
     * @return string HTTP メソッド名 (未設定時は空文字列)
     */
    public function getMethod(): string
    {
        return $this->method ?? "";
    }

    /**
     * ヘッダーフィールドを設定します。
     *
     * 指定された値が EmptyField の場合は無視されます。
     * 既存の同名ヘッダーがある場合は上書きされます。
     *
     * @param HeaderField $header 設定するヘッダーフィールド
     * @return RequestBuilder このオブジェクト自身
     */
    public function setHeader(HeaderField $header): self
    {
        if ($header === EmptyField::getInstance()) {
            return $this;
        }

        $name = strtolower($header->getName());

        $this->headerList[$name] = $header;
        return $this;
    }

    /**
     * 設定されているすべてのヘッダーを取得します。
     *
     * @return HeaderField[] ヘッダー名をキー (小文字) とした連想配列
     */
    public function getHeaderList(): array
    {
        return $this->headerList;
    }

    /**
     * 単一の GET パラメータ (クエリ) を設定します。
     *
     * @param string $name パラメータ名
     * @param string|array $value パラメータの値
     * @return RequestBuilder このオブジェクト自身
     */
    public function setQuery(string $name, $value): self
    {
        $this->queryList[$name] = $value;
        return $this;
    }

    /**
     * 複数の GET パラメータを配列でまとめて設定 (マージ) します。
     *
     * @param array $queryList 設定するパラメータの連想配列
     * @return RequestBuilder このオブジェクト自身
     */
    public function setQueryList(array $queryList): self
    {
        $this->queryList = array_merge($this->queryList, $queryList);
        return $this;
    }

    /**
     * 設定されているすべての GET パラメータを取得します。
     *
     * @return array GET パラメータの連想配列
     */
    public function getQueryList(): array
    {
        return $this->queryList;
    }

    /**
     * 単一の POST パラメータを設定します。
     *
     * @param string $name パラメータ名
     * @param string|array $value パラメータの値
     * @return RequestBuilder このオブジェクト自身
     */
    public function setPost(string $name, $value): self
    {
        $this->postList[$name] = $value;
        return $this;
    }

    /**
     * 複数の POST パラメータを配列でまとめて設定 (マージ) します。
     *
     * @param array $postList 設定するパラメータの連想配列
     * @return RequestBuilder このオブジェクト自身
     */
    public function setPostList(array $postList): self
    {
        $this->postList = array_merge($this->postList, $postList);
        return $this;
    }

    /**
     * 設定されているすべての POST パラメータを取得します。
     *
     * @return array POST パラメータの連想配列
     */
    public function getPostList(): array
    {
        return $this->postList;
    }

    /**
     * 単一の Cookie を設定します。
     *
     * @param string $name Cookie 名
     * @param string $value Cookie の値
     * @return RequestBuilder このオブジェクト自身
     */
    public function setCookie(string $name, string $value): self
    {
        $this->cookieList[$name] = $value;
        return $this;
    }

    /**
     * 複数の Cookie を配列でまとめて設定 (マージ) します。
     *
     * @param array $cookieList 設定する Cookie の連想配列
     * @return RequestBuilder このオブジェクト自身
     */
    public function setCookieList(array $cookieList): self
    {
        $this->cookieList = array_merge($this->cookieList, $cookieList);
        return $this;
    }

    /**
     * 設定されているすべての Cookie を取得します。
     *
     * @return array Cookie の連想配列
     */
    public function getCookieList(): array
    {
        return $this->cookieList;
    }

    /**
     * 添付ファイルを設定します。
     *
     * @param string $name パラメータ名
     * @param UploadFile $file 設定する添付ファイルオブジェクト
     * @return RequestBuilder このオブジェクト自身
     */
    public function setUploadFile(string $name, UploadFile $file): self
    {
        $this->fileList[$name] = $file;
        return $this;
    }

    /**
     * 設定されているすべての添付ファイルを取得します。
     *
     * @return UploadFile[] 添付ファイルの連想配列
     */
    public function getUploadFileList(): array
    {
        return $this->fileList;
    }

    /**
     * このオブジェクトの設定内容に基づいて Request インスタンスを生成します。
     *
     * method が設定されていない場合は "get" として扱われます。
     * scheme が設定されていない場合は "http" として扱われます。
     * host については明示的に指定する必要があります。設定されていない場合は LogicException をスローします。
     *
     * @return Request 構築された Request オブジェクト
     * @throws LogicException host が設定されていない場合
     */
    public function build(): Request
    {
        return Request::newInstance($this);
    }
}
