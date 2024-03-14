<?php

namespace Woof\Util;

/**
 * アプリケーションの設定値やプロパティを取得するためのインタフェースです。
 */
interface Properties
{
    /**
     * 指定された名前の設定項目を取得します。
     * 設定がツリー構造となっている場合、上位階層と下位階層の設定名を "." でつなげることで、下位階層の値を取得することができます。
     *
     * 設定項目が存在しない場合は第 2 引数に指定された代替値を返します。
     * 第 2 引数を指定しない場合は代替値として null を返します。
     *
     * @param string $name 取得したい設定項目のキー名
     * @param mixed $defaultValue 指定された項目名が見つからなかった場合に返される代替値
     * @return mixed 取得した設定値または代替値
     */
    public function get(string $name, $defaultValue = null);

    /**
     * 指定された名前の設定項目が存在するかどうかを調べます。
     *
     * @param string $name 確認したい設定項目のキー名
     * @return bool 指定された設定項目が存在する場合に true
     */
    public function contains(string $name): bool;
}
