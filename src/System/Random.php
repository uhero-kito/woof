<?php

namespace Woof\System;

/**
 * 乱数生成器を抽象化するインタフェースです。
 *
 * 実行環境 (Environment) から乱数を取得する際に利用します。
 * 本番環境では基本的に `DefaultEnvironment` インスタンスを使用する想定です。
 * テスト時には代わりにこのインタフェースを実装したモックを使用することで、予測可能な (固定された) 乱数列をシミュレートできます。
 */
interface Random
{
    /**
     * 乱数の結果として 0 以上 mt_getrandmax() 以下の整数を返します。
     *
     * @return int 0 以上 mt_getrandmax() 以下の整数
     */
    public function next(): int;
}
