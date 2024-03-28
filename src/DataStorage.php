<?php

namespace Woof;

/**
 * アプリケーション内の動的なデータの読み書きを行うためのインタフェースです。
 *
 * キャッシュデータ・生成されたファイル・ユーザーのアップロードデータなど、
 * システム稼働中に発生する様々なデータを保存・取得する機能を提供します。
 *
 * このインタフェースの各メソッドで指定される「キー」は、以下の仕様に基づき解釈および正規化されます。
 *
 * - セグメントとセパレーター: スラッシュ ("/", 0x2F) をセパレーターとし、スラッシュで区切られた各文字列を「セグメント」と呼びます
 * - イニシャル・セグメント: キーが複数のセグメントから成り立つとき、末尾のセグメントを除く、冒頭から任意のセグメントまでの部分文字列を「イニシャル・セグメント」と定義します
 * - 正規化の基本要件: キーを文字列で指定する際、先頭および末尾に存在する 1 文字以上のセパレーターは無視され、ないものとして扱われます。また、セパレーターが 2 文字以上連続している場合は単独のセパレーターとして扱われます (空のセグメントは許容されません)
 * - 実装固有の正規化: 各具象クラスは上記の基本要件を満たす範囲内で、独自の正規化 (例: RFC 3986 の Remove Dot Segments アルゴリズムによるパスの解決など) やバリデーションを行っても良いものとします
 */
interface DataStorage
{
    /**
     * 指定されたキーに相当するデータを返します。
     * 引数のキーが存在しない場合は第 2 引数の値を返します。
     *
     * @param string $key 取得したいデータのキー
     * @param string $defaultValue 指定されたキーが見つからなかった場合に使用される代替値
     * @return string 取得したデータの内容または代替値
     */
    public function get(string $key, string $defaultValue = ""): string;

    /**
     * 指定されたキーに相当するデータが存在するかどうかを調べます。
     *
     * @param string $key 確認したいデータのキー
     * @return bool データが存在する場合に true
     */
    public function contains(string $key): bool;

    /**
     * 指定されたキーに新しいデータを書き込みます。
     * 既存のデータがある場合は上書きされます。
     *
     * @param string $key 書き込み先のキー
     * @param string $contents 書き込む内容
     * @return bool 書き込みに成功した場合に true
     */
    public function put(string $key, string $contents): bool;

    /**
     * 指定されたキーに相当するデータの末尾に追記します。
     *
     * @param string $key 追記先のキー
     * @param string $contents 追記する内容
     * @return bool 追記に成功した場合に true
     */
    public function append(string $key, string $contents): bool;

    /**
     * 引数で指定されたイニシャル・セグメントに属するすべてのキーを取得します。
     * 引数に空文字列を指定した場合 (または引数を省略した場合) は、この DataStorage が保持するすべてのキーを取得します。
     *
     * @param string $prefix イニシャル・セグメント (デフォルトは空文字列)
     * @return string[] 該当するすべてのキーの配列。指定されたイニシャル・セグメントを持つキーが存在しないか、指定された文字列自体がキーだった場合は空の配列を返します
     */
    public function getKeys(string $prefix = ""): array;

    /**
     * 指定されたキーに相当するデータの最終更新日時を取得します。
     *
     * 指定されたキーが存在しない場合や、
     * この DataStorage 実装が最終更新日時をサポートしていない場合は 0 を返します。
     *
     * @param string $key 取得したいデータのキー
     * @return int 最終更新日時の Unix time (存在しないか、サポートしていない場合は 0)
     */
    public function getModifiedTime(string $key): int;

    /**
     * 指定されたキーに相当するデータの最終更新日時を設定 (上書き) します。
     * 成功した場合は true、下記の理由などにより失敗した場合は false を返します。
     *
     * - 指定されたキーのデータが存在しない場合
     * - この DataStorage 実装が最終更新日時をサポートしていない場合
     * - ファイルのアクセス権限などの問題により更新できない場合
     *
     * @param string $key 対象となるデータのキー
     * @param int $time 設定する最終更新日時 (Unix time)
     * @return bool 更新に成功した場合のみ true
     */
    public function setModifiedTime(string $key, int $time): bool;

    /**
     * 指定されたキーに相当するデータを削除します。
     * 成功した場合は true、下記の理由などにより失敗した場合は false を返します。
     *
     * - 指定されたキーのデータが存在しない場合
     * - この DataStorage 実装がデータの削除をサポートしていない場合
     * - アクセス権限などの問題により削除できない場合
     *
     * @param string $key 対象となるデータのキー
     * @return bool 削除に成功した場合に true
     */
    public function remove(string $key): bool;
}
