<?php

namespace Woof\Http;

use InvalidArgumentException;
use Woof\Log\Logger;
use Woof\System\Variables;

/**
 * サーバー変数 ($_SERVER など) からデータを読み取り Request オブジェクトを構築するクラスです。
 */
class RequestLoader
{
    /**
     * ヘッダーのパース失敗時などに情報を出力するための Logger オブジェクトです。
     *
     * @var Logger
     */
    private $logger;

    /**
     * 生のヘッダー文字列を HeaderField オブジェクトに変換するためのパーサーです。
     *
     * @var HeaderParser
     */
    private $parser;

    /**
     * 新しい RequestLoader インスタンスを生成します。
     *
     * @param Logger|null $logger エラー記録用の Logger (未指定時は NopLogger)
     * @param HeaderParser|null $parser ヘッダー解析用のパーサー (未指定時はデフォルトの HeaderParser)
     */
    public function __construct(Logger $logger = null, HeaderParser $parser = null)
    {
        $this->logger = $logger ?? Logger::getNopLogger();
        $this->parser = $parser ?? new HeaderParser();
    }

    /**
     * 設定されている Logger を取得します。
     *
     * @return Logger エラー記録用の Logger オブジェクト
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * 設定されている HeaderParser を取得します。
     *
     * @return HeaderParser パーサーオブジェクト
     */
    public function getHeaderParser(): HeaderParser
    {
        return $this->parser;
    }

    /**
     * 指定された Variables オブジェクトからサーバー情報や各種パラメータを読み取り、
     * Request オブジェクトを構築して返します。
     *
     * @param Variables $var PHP のスーパーグローバル変数などを抽象化したオブジェクト
     * @return Request 構築された Request オブジェクト
     */
    public function load(Variables $var): Request
    {
        $server  = $var->getServer();
        $uri     = $server["REQUEST_URI"] ?? "";
        $builder = (new RequestBuilder())
            ->setHost($server["HTTP_HOST"] ?? "")
            ->setUri($uri)
            ->setPath($this->detectPath($uri))
            ->setScheme(isset($server["HTTPS"]) ? "https" : "http")
            ->setMethod($server["REQUEST_METHOD"] ?? "")
            ->setQueryList($var->getGet())
            ->setPostList($var->getPost())
            ->setCookieList($var->getCookie());
        foreach ($server as $key => $value) {
            if (substr($key, 0, 5) !== "HTTP_") {
                continue;
            }
            $builder->setHeader($this->parseHeader($key, $value));
        }
        foreach ($var->getFiles() as $key => $file) {
            $builder->setUploadFile($key, $this->createUploadFile($file));
        }
        return $builder->build();
    }

    /**
     * URI 文字列からクエリ文字列 (? 以降) を除外し、パス部分のみを抽出します。
     *
     * @param string $uri クエリを含む可能性がある URI
     * @return string クエリが除外されたパス文字列
     */
    private function detectPath(string $uri)
    {
        return (false === ($index = strpos($uri, "?"))) ? $uri : substr($uri, 0, $index);
    }

    /**
     * $_SERVER 配列由来の "HTTP_..." 形式のキー名と値を解析し、HeaderField オブジェクトに変換します。
     * 不正なヘッダーフォーマットが検出された場合は警告をログに出力し、EmptyField を返します。
     *
     * @param string $key サーバー変数のキー名 (例: "HTTP_ACCEPT_LANGUAGE")
     * @param string $value ヘッダーの値
     * @return HeaderField 生成されたヘッダーフィールドオブジェクト
     */
    private function parseHeader(string $key, string $value): HeaderField
    {
        $name = ucwords(strtolower(str_replace("_", "-", substr($key, 5))));
        try {
            return $this->parser->parse($name, $value);
        } catch (InvalidArgumentException $e) {
            $logger = $this->logger;
            $logger->debug("Invalid request header detected: '{$name}'");
            $logger->debug($e->getMessage());
            return EmptyField::getInstance();
        }
    }

    /**
     * $_FILES 配列由来の要素から UploadFile オブジェクトを生成します。
     *
     * @param array $file $_FILES の単一要素にあたる配列
     * @return UploadFile 構築された UploadFile オブジェクト
     */
    private function createUploadFile(array $file): UploadFile
    {
        $name      = $file["name"] ?? "";
        $path      = $file["tmp_name"] ?? "";
        $errorCode = $file["error"] ?? "";
        $size      = $file["size"] ?? "";
        return new UploadFile($name, $path, (int) $errorCode, (int) $size);
    }
}
