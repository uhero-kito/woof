<?php

namespace Woof\Web;

use Woof\Http\Request;
use Woof\Http\Response;

/**
 * Web アプリケーションにおけるコントローラー (リクエストハンドラ) が実装すべきインタフェースです。
 */
interface Controller
{
    /**
     * クライアントからの HTTP リクエストを受け取り、処理を行った結果として HTTP レスポンスを返します。
     *
     * @param Request $request クライアントの HTTP リクエスト
     * @param WebEnvironment $env Web アプリケーションの実行環境
     * @return Response 構築された HTTP レスポンス
     */
    public function handle(Request $request, WebEnvironment $env);
}
