## Extraxcel

[![Total Downloads](https://poser.pugx.org/laravel/framework/d/total.svg)](https://packagist.org/packages/laravel/framework)
[![Latest Stable Version](https://poser.pugx.org/laravel/framework/v/stable.svg)](https://packagist.org/packages/laravel/framework)
[![License](https://poser.pugx.org/laravel/framework/license.svg)](https://packagist.org/packages/laravel/framework)

Extraxcelは単票形式のExcelファイルを複数束ね、データを抽出して１つの帳票形式のExcelファイルにするWEBアプリケーションです。

あなたの職場で、あるいはサークル内で、「罫線でデザインされた１つのワークシート＆その上に散りばめられた入力セル」といった特徴を持つExcelファイルをよく目にすると思います。
アンケートや各種の申請書類によく見られるあれです。

「単票形式」と呼ぶそれらのExcelファイルを、集計するのに膨大な手間をかけたことはありませんか？
1000個のアンケート結果を一覧表に直すのに数時間を費やしたことは？
10件の経費申請を見比べるために10個のExcelで画面を埋め尽くしたことは？

これからは大丈夫。Extraxcelが数分で終わらせます。


設置にはデータベースは不要。
Microsoft Officeも不要です。

簡単な操作で最大限の作業効率化を。


## システム要件 - System Requirement

+ Linux系サーバ または Windowsサーバ
+ Apache1.3以降
+ mod_rewrite が利用可能であること
+ .htaccess が利用可能であること
+ PHP5.6以上
+ mb_string が有効に設定されていること
+ composerが利用可能であること


## インストールと設定 - Installation & Setting

1. 任意のディレクトリにおいて、composerを実行します。

> php composer.phar create-project grassfield/extraxcel .

2. デフォルトのログ出力先に次のディレクトリとそれ以下の全てのファイルに、Apacheが書き込みできるパーミッションを設定してください。(この手順はWindowsサーバーでは不要です)

> chmod -R go+rw ./extraxcel




Documentation for the framework can be found on the [Laravel website](http://laravel.com/docs).

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](http://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

### License

The Laravel framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
