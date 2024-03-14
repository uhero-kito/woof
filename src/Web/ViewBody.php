<?php

namespace Woof\Web;

use Woof\Http\Response\Body;
use Woof\Resources;
use Woof\Web\Context;

/**
 * View のレンダリング結果を HTTP レスポンスのボディとして扱うための Body 実装です。
 */
class ViewBody implements Body
{
    /**
     * 描画処理を行う View オブジェクトです。
     *
     * @var View
     */
    private $view;

    /**
     * 描画時に View に渡されるリソース群です。
     *
     * @var Resources
     */
    private $resources;

    /**
     * 描画時に View に渡される Context オブジェクトです。
     *
     * @var Context
     */
    private $context;

    /**
     * 一度レンダリングされた結果をキャッシュする文字列です。
     *
     * @var string
     */
    private $output;

    /**
     * レンダリングに必要なオブジェクト群を指定してインスタンスを生成します。
     *
     * @param View $view 描画処理を行う View オブジェクト
     * @param Resources $resources 描画に使用するリソース群
     * @param Context $context Web アプリケーションのパスの解決などを行う Context オブジェクト
     */
    public function __construct(View $view, Resources $resources, Context $context)
    {
        $this->view      = $view;
        $this->resources = $resources;
        $this->context   = $context;
    }

    /**
     * 保持している View オブジェクトを取得します。
     *
     * @return View View オブジェクト
     */
    public function getView(): View
    {
        return $this->view;
    }

    /**
     * レンダリング結果の文字列のバイト数を返します。
     *
     * @return int コンテンツのバイト数
     */
    public function getContentLength(): int
    {
        return strlen($this->getOutput());
    }

    /**
     * 保持している View から Content-Type の値を取得して返します。
     *
     * @return string Content-Type の値
     */
    public function getContentType(): string
    {
        return $this->view->getContentType();
    }

    /**
     * View をレンダリングして結果の文字列を取得します。
     * 初回呼び出し時にレンダリングが実行され、以降はキャッシュされた文字列が返されます。
     *
     * @return string レンダリング結果の文字列
     */
    public function getOutput(): string
    {
        if ($this->output === null) {
            $this->output = $this->view->render($this->resources, $this->context);
        }
        return $this->output;
    }

    /**
     * レンダリング結果の文字列をクライアントに送信します。
     *
     * @return bool 常に true
     */
    public function sendOutput(): bool
    {
        echo $this->getOutput();
        return true;
    }
}
