# Woof - Well Object-Oriented Framework

Woof は **「Web アプリケーションを、HTTP リクエストを入力・HTTP レスポンスを出力とする『純粋関数』として表現する」**
という基本ポリシーを極限まで追求した PHP 用の Web フレームワークです。

この「関数としての純粋性」と「高いテスタビリティ (テスト容易性) 」という強みに徹底的に特化するため、
Woof はあえて組み込みの ORM・ルーティング・テンプレートエンジンなどの機能を持たず、
非常に軽量で強固なコア (土台) として設計されています。これにより、予測不能な外部状態に振り回されない、
極めて見通しが良くテストしやすいアプリケーションを構築できます。

## 設計思想と構造

Woof が掲げる「関数としての Web アプリケーション」を実現するための手段として、
アーキテクチャにはいくつかの重要なルールと仕組みが用意されています。

### 1. 副作用を `Environment` に閉じ込める
Web アプリケーションの開発では、システム時刻の取得・乱数・データベース操作・セッション管理など、
動的に変化する値や外部システムとのデータのやり取り (副作用) がどうしても避けられません。

Woof では、このような外部要因に依存する処理を直接呼び出すのではなく、すべて `Environment`
という単一のコンテキストオブジェクトに集約し、外部から注入する設計をとっています。
これにより、アプリケーションの主要なロジックは純粋さを保ち、
テスト時には固定の時刻やモック化された環境を容易に差し替えることができます。

#### Environment が提供する 3 つの主要な入出力機構
`Environment` は、アプリケーションの実行に必要なファイルやデータへのアクセスを、
その性質に応じて以下の 3 つの役割に明確に分割して提供します。

* **Config**: 実行環境 (本番環境・検証環境・ローカル環境など) ごとに異なる設定値を定義します。
* **Resources**: プログラムと連動して使用されるファイル群 (HTML テンプレート・システムメッセージの翻訳ファイルなど) を管理します。
* **DataStorage**: 実行中に発生する動的なファイル (アプリケーションログ・セッションデータ・キャッシュなど) の保管場所を提供します。

以下にそれぞれの用途と違いについて記載します。

| 名称 | 用途 | 動的な変更の有無 | バージョン管理への追加 |
| :--- | :--- | :--- | :--- |
| **Config** | 環境固有の設定値の定義 | なし | なし |
| **Resources** | プログラムから参照される各種静的ファイル | なし | あり |
| **DataStorage** | プログラムの実行中に追加・更新・削除される動的ファイル | あり | なし |

### 2. 状態の不変性 (Immutable) と Builder パターン
予期せぬ状態の変更 (副作用) を防ぐため、HTTP リクエスト (Request) や HTTP レスポンス (Response)
などの主要なオブジェクトはイミュータブル (不変) として設計されています。
これらのクラスを安全に生成・変更するために、各種 Builder クラスを使用します。

## インストール方法

Composer を使用してインストールします。

```bash
composer require uhero-corp/woof
```

*(※テンプレートエンジンや DB コネクタなどが必要な場合は、要件に合わせて外部ライブラリを別途インストールして組み合わせて使用します)*

## ディレクトリ構成

Woof では、セキュリティの観点から公開ディレクトリ (ドキュメントルート) とシステム・リソースのディレクトリを完全に分離することを推奨しています。

```text
project-root/
├── config/               (設定ファイル群)
│   ├── .gitignore        (※ 末尾が .json や .ini で終わる全ファイルをバージョン管理から除外)
│   ├── app.json
│   └── ...
├── resources/            (HTML テンプレートや静的リソースなど)
│   └── template.html
├── storage/              (セッション、キャッシュ、ログなどの動的ファイル)
│   └── .gitignore        (※ 自身 [.gitignore] を除く全ファイルをバージョン管理から除外)
└── htdocs/               (Web サーバーのドキュメントルート)
    └── index.php         (フロントコントローラー)
```

## 基本的な開発の流れ

以下は、Woof を使った最もシンプルなアプリケーションの例です。リクエストの解析から処理、レスポンスの出力までの流れをステップごとに記述します。

### 1. 処理クラス (Controller) の作成

`Woof\Web\Controller` インターフェースを実装し、入力を受け取って出力を返す `handle` メソッドを定義します。

```php
<?php

use Woof\Web\Controller;
use Woof\Web\WebEnvironment;
use Woof\Http\Request;
use Woof\Http\Response;
use Woof\Http\ResponseBuilder;
use Woof\Http\Response\TextBody;
use Woof\Http\Status;

class HelloController implements Controller
{
    public function handle(Request $request, WebEnvironment $env): Response
    {
        // Request と Environment を元に Response を構築して返す (純粋関数としての振る舞い)
        return (new ResponseBuilder())
            ->setStatus(Status::getOK())
            ->setBody(new TextBody("Hello, Woof!"))
            ->build();
    }
}
```

### 2. エンドポイント (index.php) の実装

フロントコントローラーとなる `index.php` では、環境を構築し、クライアントからのリクエストを取り出して処理に渡します。

```php
<?php

$appRoot = dirname(__DIR__);
require_once "{$appRoot}/vendor/autoload.php";

use Woof\Web\WebEnvironmentBuilder;
use Woof\Http\Request;
use Woof\Web\WebEnvironment;
use Woof\Web\Controller;
use Woof\Web\DefaultOutput;

// 1. 副作用を集約する Environment の構築
$env = (new WebEnvironmentBuilder())
    ->setConfigDir("{$appRoot}/config")
    ->setResourcesDir("{$appRoot}/resources")
    ->build();

// 2. クライアントからの Request オブジェクトを取得
$request = $env->getClientRequest();

// 3. ルーティング関数
// Request と Environment を評価し、対応する Controller を返す関数を定義します。
// マッチするルートがない (404) 場合は、デフォルト値として
// 404 専用の Controller を返すのが綺麗な設計です。
$router = function (Request $request, WebEnvironment $env): Controller {
    return new HelloController();
};

// 4. ルーティングの実行と、レスポンスの生成
$controller = $router($request, $env);
$response   = $controller->handle($request, $env);

// 5. レスポンスの出力
$output = new DefaultOutput();
$output->send($response);
```

### 3. 実践的な Controller と View の実装例

実際の開発では `Woof\Web\Operator` クラスと外部のテンプレートエンジンを組み合わせて、より実践的にレスポンスを構築します。
ここでは、テンプレートエンジンとして [Binder](https://github.com/uhero-kito/binder) を採用し、
現在時刻や Cookie からの情報を HTML に埋め込んで出力する例を示します。

#### HTML テンプレートの作成 (resources/time.html)

まず、`Resources` の読み込み対象となる `resources` ディレクトリ内に HTML の雛形を作成します。

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>現在の時刻</title>
</head>
<body>
    <h1>現在の時刻</h1>
    <p>{{greeting}}</p>
    <p>現時刻は {{time}} です。</p>

    <ul>
        <li><a href="{{home_url}}">ホーム</a></li>
        <li><a href="{{privacy_url}}">プライバシーポリシー</a></li>
        <li><a href="{{contact_url}}">お問い合わせ</a></li>
    </ul>
</body>
</html>
```

#### View クラスの作成

`Woof\Web\View` インターフェースを実装し、テンプレートの読み込みと値のバインドを行うクラスを作成します。
Controller からは「現在時刻」と「前回の訪問時刻」という純粋なデータのみを受け取り、表示文言の構築は View の責務とします。

```php
<?php

use Woof\Web\View;
use Woof\Resources;
use Woof\Web\Context;
use Binder\Template;

class TimeView implements View
{
    /** @var int */
    private $time;
    
    /** @var int */
    private $lastVisited;

    // View が描画に必要とする状態 (現在時刻と前回の訪問時刻) をコンストラクタで受け取ります
    public function __construct(int $time, int $lastVisited = 0)
    {
        $this->time        = $time;
        $this->lastVisited = $lastVisited;
    }

    public function getContentType(): string
    {
        return "text/html; charset=UTF-8";
    }

    public function render(Resources $resources, Context $context): string
    {
        // 1. Resources から HTML の雛形を読み込む
        $html = $resources->get("time.html");

        // 2. 挨拶文の構築
        $greeting = $this->lastVisited > 0
            ? "前回のアクセスは " . date("Y/m/d H:i:s", $this->lastVisited) . " でした。"
            : "はじめまして。";

        // 3. Context を使ってリンク先の URL を書式化する
        $homeUrl    = $context->formatHref("/");
        $privacyUrl = $context->formatHref("/privacy");
        $contactUrl = $context->formatHref("/contact", ["ref" => "timepage"]);

        // 4. Binder を使ってプレースホルダーに値をセットし、レンダリング結果を返す
        return Template::readMarkup($html)
            ->entry()
            ->set("greeting", $greeting)
            ->set("time", date("Y/m/d H:i:s", $this->time))
            ->set("home_url", $homeUrl)
            ->set("privacy_url", $privacyUrl)
            ->set("contact_url", $contactUrl)
            ->render();
    }
}
```

#### Controller クラスの作成

Controller では `Environment` や `Request` から情報を取得し、`Operator` を使って Cookie の発行と View の指定をスマートに記述します。

```php
<?php

use Woof\Web\Controller;
use Woof\Web\WebEnvironment;
use Woof\Http\Request;
use Woof\Http\Response;
use Woof\Http\Response\Cookie;
use Woof\Web\Operator;

class TimeController implements Controller
{
    public function handle(Request $request, WebEnvironment $env): Response
    {
        // 1. Cookie から前回の訪問時刻を取得 (未訪問の場合は 0 となる)
        $lastVisited = (int) $request->getCookie("last_visited");

        // 2. 副作用の隔離: `time()` 関数ではなく Environment (Clock) から現在時刻を取得
        $now = $env->now();

        // 3. Operator を使ってレスポンスを構築
        $operator = new Operator($request, $env);

        return $operator
            ->setCookie(new Cookie("last_visited", (string) $now))
            ->setView(new TimeView($now, $lastVisited))
            ->build();
    }
}
```

### 4. 実装のコツとテストの考え方

Woof の「純粋関数」という強みを最大限に活かすため、以下のプラクティスを守ることを推奨します。

* **View には「純粋な値 (不変な状態) 」のみを渡す (キャッシュと副作用の安全な管理)**  
  View をインスタンス化する際、メンバ変数 (状態) として持たせるのは具体的な値そのものだったり、
  それらをまとめた完全に不変 (イミュータブル) な専用データクラスのインスタンスにすることが重要です。
  具体的な指針は以下の通りです。
  * **現在時刻に依存した画面**: `$env->now()` で取得した現在時刻 (整数値) そのものを渡します。`render()` メソッドの中で `time()` などを使って現在時刻を取得するのは NG です。
  * **乱数に依存した処理 (例: おみくじアプリなど)**: Controller 側で取得した乱数の結果を渡します。View の内部で新たに乱数を取得してはいけません。
  * **ログインユーザー情報の表示**: 例えばユーザー名の文字列そのものや、アイコン画像のパスあるいはバイナリデータ (バイト列) そのものを指定して渡すようにします。  
  ORM の `User` オブジェクトなどをそのまま View に持たせるのは NG (アンチパターン) です。
  暗黙のデータベースアクセスが発生する可能性があり、処理の純粋性が失われてしまいます。

  このルールを守ることで、キャッシュ機構を有効化した際に「別のユーザーの情報が誤ってキャッシュされて表示されてしまう」
  といった重大なセキュリティリスクを未然に防ぐことができます。  
  さらに **View の単体テストが極めて容易になります。**
  View が外部 (データベースなど) に依存せず、渡された状態のみによってレンダリング結果が決まるため、
  「この入力 (状態) を与えたら、この出力 (HTML) が返るはずである」という
  **入力と想定結果を 1:1 でシンプルに定義でき、パターンごとの網羅的なテストを簡単に実装できるようになる**
  ためです。

* **Controller のテストは「View の状態」を検証するだけで OK**  
  Controller が正しく動作したかテストする際、出力された HTML の文字列をパースしたり DOM を解析する必要はありません。
  Woof では、Controller が返した `Response` オブジェクトから、セットされた `View` オブジェクトをそのまま取り出すことができます。「意図した View クラスが使われているか」「View のメンバ変数に正しい状態がセットされているか」を検証するだけで、UI の変更に強固でシンプルに書けるテストを実現できます。

## 実際の開発における拡張とベストプラクティス

Woof は関数的なコア機能に特化しているため、実際のアプリケーション開発では周辺機能 (セッション・データベース・キャッシュなど) を組み合わせてスケールさせます。

* **ミドルウェア (認証・認可など)**: 
  ログイン認証や権限確認が必要な場合、権限周りのみを取り扱う Controller を別途作成して対象の Controller をラップする **Decorator パターン** を用いて実装します。これにより、安全かつ純粋に関数的なミドルウェアの役割を実現できます。
* **データベース (RDBMS)**: 
  `Config` クラスを利用して接続情報を管理し、`Environment` の拡張として PDO などのコネクタを生成・注入する仕組みをアプリケーション側で実装して対応します。

## 公式ドキュメント (Wiki)

より詳細な機能の仕様 (セッション管理・キャッシュ制御・テスト戦略など) や、具体的な実装パターンについては、公式 Wiki をご参照ください。

* [Woof Official Wiki](https://github.com/uhero-corp/woof/wiki)

## ライセンス

MIT License
