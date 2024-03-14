<?php

namespace Woof;

/**
 * アプリケーションの実行に必要な、プログラム以外の各種リソースを取り出すためのインタフェースです。
 * 主要なリソースの例として HTML テンプレート・システムメッセージの翻訳情報・各種メディアファイルなどが挙げられます。
 */
interface Resources
{
    /**
     * 指定されたキーに該当するリソースを取得し、文字列として返します。
     *
     * @param string $key 取得したいリソースのキー名
     * @return string リソースの内容 (文字列)
     * @throws ResourceNotFoundException 指定されたリソースが存在しない場合
     */
    public function get(string $key): string;

    /**
     * 指定されたキーに相当するリソースが存在するかどうかを判定します。
     *
     * @param string $key 確認したいリソースのキー名
     * @return bool リソースが存在する場合に true
     */
    public function contains(string $key): bool;
}
