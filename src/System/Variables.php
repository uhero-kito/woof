<?php

namespace Woof\System;

/**
 * PHP の各種グローバル変数にアクセスするためのクラスです。
 *
 * スーパーグローバル変数 ($_SERVER, $_GET, $_POST など) への直接アクセスを排除し、
 * テスト時などに任意のパラメータを注入可能にする (副作用を隔離する)
 * ために使用されるイミュータブルな値オブジェクトです。
 */
class Variables
{
    /**
     * グローバル変数 $_SERVER に相当する配列です。
     *
     * @var array
     */
    private $server;

    /**
     * グローバル変数 $_ENV に相当する配列です。
     *
     * @var array
     */
    private $env;

    /**
     * グローバル変数 $_GET に相当する配列です。
     *
     * @var array
     */
    private $get;

    /**
     * グローバル変数 $_POST に相当する配列です。
     *
     * @var array
     */
    private $post;

    /**
     * グローバル変数 $_COOKIE に相当する配列です。
     *
     * @var array
     */
    private $cookie;

    /**
     * グローバル変数 $_FILES に相当する配列です。
     *
     * @var array
     */
    private $files;

    /**
     * このクラスは VariablesBuilder を使用して構築するため、直接インスタンス化することはできません。
     */
    private function __construct()
    {
        $this->server = [];
        $this->env    = [];
        $this->get    = [];
        $this->post   = [];
        $this->cookie = [];
        $this->files  = [];
    }

    /**
     * 指定された値を保持する Variables インスタンスを生成します。
     *
     * このメソッドは VariablesBuilder::build() から参照されます。
     *
     * @param VariablesBuilder $builder 値が設定された VariablesBuilder インスタンス
     * @return Variables 構築された Variables インスタンス
     * @ignore
     */
    public static function newInstance(VariablesBuilder $builder): self
    {
        $instance         = new self();
        $instance->server = $builder->getServer();
        $instance->env    = $builder->getEnv();
        $instance->get    = $builder->getGet();
        $instance->post   = $builder->getPost();
        $instance->cookie = $builder->getCookie();
        $instance->files  = $builder->getFiles();
        return $instance;
    }

    /**
     * 現在定義されている各種グローバル変数を参照する Variables インスタンスを返します。
     *
     * @return Variables 現在のスーパーグローバル変数を保持する Variables インスタンス
     * @codeCoverageIgnore
     */
    public static function getDefaultInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
            $instance->server = $_SERVER;
            $instance->env    = $_ENV;
            $instance->get    = $_GET;
            $instance->post   = $_POST;
            $instance->cookie = $_COOKIE;
            $instance->files  = $_FILES;
        }
        return $instance;
    }

    /**
     * グローバル変数 $_SERVER に相当する配列を取得します。
     *
     * @return array $_SERVER に相当する配列
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * グローバル変数 $_ENV に相当する配列を取得します。
     *
     * @return array $_ENV に相当する配列
     */
    public function getEnv(): array
    {
        return $this->env;
    }

    /**
     * グローバル変数 $_POST に相当する配列を取得します。
     *
     * @return array $_POST に相当する配列
     */
    public function getPost(): array
    {
        return $this->post;
    }

    /**
     * グローバル変数 $_GET に相当する配列を取得します。
     *
     * @return array $_GET に相当する配列
     */
    public function getGet(): array
    {
        return $this->get;
    }

    /**
     * グローバル変数 $_COOKIE に相当する配列を取得します。
     *
     * @return array $_COOKIE に相当する配列
     */
    public function getCookie(): array
    {
        return $this->cookie;
    }

    /**
     * グローバル変数 $_FILES に相当する配列を取得します。
     *
     * @return array $_FILES に相当する配列
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
