<?php

namespace Woof\System;

/**
 * Variables インスタンスを構築するためのビルダークラスです。
 */
class VariablesBuilder
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
     * 各種変数を空の配列で初期化して VariablesBuilder インスタンスを生成します。
     */
    public function __construct()
    {
        $this->server = [];
        $this->env    = [];
        $this->get    = [];
        $this->post   = [];
        $this->cookie = [];
        $this->files  = [];
    }

    /**
     * グローバル変数 $_SERVER に相当する配列を設定します。
     *
     * @param array $server $_SERVER に相当する配列
     * @return VariablesBuilder このオブジェクト自身
     */
    public function setServer(array $server): self
    {
        $this->server = $server;
        return $this;
    }

    /**
     * 設定された $_SERVER に相当する配列を取得します。
     *
     * @return array 設定された $_SERVER に相当する配列
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * グローバル変数 $_ENV に相当する配列を設定します。
     *
     * @param array $env $_ENV に相当する配列
     * @return VariablesBuilder このオブジェクト自身
     */
    public function setEnv(array $env): self
    {
        $this->env = $env;
        return $this;
    }

    /**
     * 設定された $_ENV に相当する配列を取得します。
     *
     * @return array 設定された $_ENV に相当する配列
     */
    public function getEnv(): array
    {
        return $this->env;
    }

    /**
     * グローバル変数 $_GET に相当する配列を設定します。
     *
     * @param array $get $_GET に相当する配列
     * @return VariablesBuilder このオブジェクト自身
     */
    public function setGet(array $get): self
    {
        $this->get = $get;
        return $this;
    }

    /**
     * 設定された $_GET に相当する配列を取得します。
     *
     * @return array 設定された $_GET に相当する配列
     */
    public function getGet(): array
    {
        return $this->get;
    }

    /**
     * グローバル変数 $_POST に相当する配列を設定します。
     *
     * @param array $post $_POST に相当する配列
     * @return VariablesBuilder このオブジェクト自身
     */
    public function setPost(array $post): self
    {
        $this->post = $post;
        return $this;
    }

    /**
     * 設定された $_POST に相当する配列を取得します。
     *
     * @return array 設定された $_POST に相当する配列
     */
    public function getPost(): array
    {
        return $this->post;
    }

    /**
     * グローバル変数 $_COOKIE に相当する配列を設定します。
     *
     * @param array $cookie $_COOKIE に相当する配列
     * @return VariablesBuilder このオブジェクト自身
     */
    public function setCookie(array $cookie): self
    {
        $this->cookie = $cookie;
        return $this;
    }

    /**
     * 設定された $_COOKIE に相当する配列を取得します。
     *
     * @return array 設定された $_COOKIE に相当する配列
     */
    public function getCookie(): array
    {
        return $this->cookie;
    }

    /**
     * グローバル変数 $_FILES に相当する配列を設定します。
     *
     * @param array $files $_FILES に相当する配列
     * @return VariablesBuilder このオブジェクト自身
     */
    public function setFiles(array $files): self
    {
        $this->files = $files;
        return $this;
    }

    /**
     * 設定された $_FILES に相当する配列を取得します。
     *
     * @return array 設定された $_FILES に相当する配列
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * 現在の設定をもとに Variables インスタンスを生成して返します。
     *
     * @return Variables 構築された Variables インスタンス
     */
    public function build(): Variables
    {
        return Variables::newInstance($this);
    }
}
