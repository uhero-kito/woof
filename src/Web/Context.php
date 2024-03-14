<?php

namespace Woof\Web;

/**
 * Web アプリケーション内で出力されるリンク先 URL を書式化するためのクラスです。
 */
class Context
{
    /**
     * Web アプリケーションの基底パスです。
     *
     * @var string
     */
    private $rootPath;

    /**
     * クエリパラメータの区切り文字です。
     *
     * @var string
     */
    private $separator;

    /**
     * Web アプリケーションの基底パスと区切り文字を指定してインスタンスを生成します。
     *
     * @param string $rootPath Web アプリケーションの基底パス
     * @param string $separator クエリパラメータの区切り文字 (デフォルトは "&")
     */
    public function __construct(string $rootPath, string $separator = "")
    {
        $subject         = trim($rootPath, "/");
        $this->rootPath  = strlen($subject) ? "/{$subject}" : "";
        $this->separator = strlen($separator) ? $separator : "&";
    }

    /**
     * Web アプリケーションの基底パスを返します。
     * 基底パスがルートの場合は "/" を返します。
     *
     * @return string 基底パス
     */
    public function getRootPath(): string
    {
        return strlen($this->rootPath) ? $this->rootPath : "/";
    }

    /**
     * 指定されたパスからリンク先 URL を書式化します。
     *
     * Web アプリケーションの基底パスに第 1 引数のパスを繋げた結果を返します。
     * 第 1 引数が "http://{FQDN}", "https://{FQDN}", "//{FQDN}"
     * のいずれかで始まる文字列の場合は絶対 URL とみなし、引数をそのまま返します。
     *
     * 第 2 引数にクエリパラメータが指定された場合は "?" に続くクエリパラメータを URL の末尾に付与します。
     * ただし第 1 引数のパスに "?" が含まれている場合は第 1 引数のクエリを優先し、第 2 引数は無視されます。
     *
     * @param string $appPath リンク先のパスまたは絶対 URL
     * @param array $query クエリパラメータの一覧 (配列のキーと値がパラメータの名前と値に対応します)
     * @return string 書式化された URL
     */
    public function formatHref(string $appPath, array $query = []): string
    {
        $qPart = $this->formatQuery($appPath, $query);
        if (preg_match("/\\A(https?:)?\\/\\//", $appPath)) {
            return "{$appPath}{$qPart}";
        }

        $subject = ltrim($appPath, "/");
        return "{$this->rootPath}/{$subject}{$qPart}";
    }

    /**
     * 連想配列からクエリ文字列を構築します。
     *
     * @param string $appPath リンク先のパス
     * @param array $query クエリパラメータの一覧
     * @return string 構築されたクエリ文字列 (例: "?key=value")
     */
    private function formatQuery(string $appPath, array $query): string
    {
        if (strpos($appPath, "?") !== false || !count($query)) {
            return "";
        }

        return "?" . http_build_query($query, "", $this->separator, PHP_QUERY_RFC3986);
    }
}
