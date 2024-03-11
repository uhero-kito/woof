<?php

namespace Woof\Web;

use Woof\Resources;

/**
 * 画面の描画 (レンダリング) 処理を担うクラスが実装すべきインタフェースです。
 */
interface View
{
    /**
     * リソースとコンテキストを利用して画面をレンダリングし、結果の文字列を返します。
     *
     * @param Resources $resources 描画に使用するリソース群
     * @param Context $context リンク生成などに使用するコンテキスト
     * @return string 描画結果の文字列
     */
    public function render(Resources $resources, Context $context): string;

    /**
     * この View が出力する Content-Type の値を返します。
     *
     * @return string Content-Type の値 (例: "text/html; charset=UTF-8")
     */
    public function getContentType(): string;
}
