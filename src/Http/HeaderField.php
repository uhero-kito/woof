<?php

namespace Woof\Http;

/**
 * HTTP リクエストおよびレスポンスにおける各種ヘッダーフィールドを表現するインタフェースです。
 */
interface HeaderField
{
    /**
     * このヘッダーフィールドのヘッダー名 (キー) を取得します。
     *
     * @return string ヘッダー名 (例: "Content-Type", "Accept-Language" など)
     */
    public function getName(): string;

    /**
     * このヘッダーフィールドが保持している実際の値を取得します。
     *
     * 実装クラスによって返り値の型 (文字列や配列など) が異なります。
     *
     * @return mixed ヘッダーの値
     */
    public function getValue();

    /**
     * このヘッダーフィールドの値を、HTTP メッセージのヘッダー行として出力可能な文字列形式にフォーマットして返します。
     *
     * @return string フォーマットされたヘッダー値の文字列
     */
    public function format(): string;
}
