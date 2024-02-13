<?php

namespace Woof\System;

use InvalidArgumentException;
use LogicException;

/**
 * 乱数列を直接指定する Random の実装です。
 * コンストラクタ引数に指定した整数配列の各要素を、次の乱数として順番に出力します。
 * すべての要素を出力した後は再びはじめの要素に戻ります。
 *
 * このクラスは、乱数に依存した処理のエッジケースのテストで使用されることを想定しています。
 */
class ArrayRandom implements Random
{
    /**
     * 乱数列として順番に出力する整数の配列です。
     *
     * @var int[]
     */
    private $seq;

    /**
     * 次に出力する配列のインデックスです。
     *
     * @var int
     */
    private $index;

    /**
     * 指定された整数配列を乱数列として使用する ArrayRandom オブジェクトを生成します。
     *
     * @param int[] $seq 乱数列として順番に出力する整数の配列
     * @throws InvalidArgumentException 空の配列が指定された場合
     */
    public function __construct(array $seq)
    {
        if (!count($seq)) {
            throw new InvalidArgumentException("Empty sequence specified");
        }

        $this->seq   = array_values($seq);
        $this->index = 0;
    }

    /**
     * 次の乱数を取得します。
     *
     * @return int 配列から取得した次の整数
     * @throws LogicException 配列内の値が 0 未満、または mt_getrandmax() を超えている場合
     */
    public function next(): int
    {
        $index = $this->index;
        $next  = (int) $this->seq[$index];
        if ($next < 0 || mt_getrandmax() < $next) {
            throw new LogicException("Invalid random number: {$next}");
        }
        $this->index = ($index + 1) % count($this->seq);
        return $next;
    }
}
