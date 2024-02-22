<?php

namespace Woof;

/**
 * アプリケーション内の動的なデータの読み書きを行うためのインタフェースです。
 *
 * キャッシュデータ・生成されたファイル・ユーザーのアップロードデータなど、
 * システム稼働中に発生する様々なデータを保存・取得する機能を提供します。
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
}
