# SMBBenchmark

[![License](https://poser.pugx.org/shimabox/smbbenchmark/license)](https://packagist.org/packages/shimabox/smbbenchmark)
[![Build Status](https://travis-ci.org/shimabox/SMBBenchmark.svg?branch=master)](https://travis-ci.org/shimabox/SMBBenchmark)
[![Coverage Status](https://coveralls.io/repos/github/shimabox/SMBBenchmark/badge.svg?branch=master)](https://coveralls.io/github/shimabox/SMBBenchmark?branch=master)
[![Maintainability](https://api.codeclimate.com/v1/badges/f7afbb17888f2fd2cc01/maintainability)](https://codeclimate.com/github/shimabox/SMBBenchmark/maintainability)
[![Latest Stable Version](https://poser.pugx.org/shimabox/smbbenchmark/v/stable)](https://packagist.org/packages/shimabox/smbbenchmark)
[![Latest Unstable Version](https://poser.pugx.org/shimabox/smbbenchmark/v/unstable)](https://packagist.org/packages/shimabox/smbbenchmark)

Simple benchmark of php

## About

* PHPの簡易ベンチマークです
* 無名関数内に計測処理を書いたり、オブジェクトの関数も計測出来ます
* 計測結果のecho、または、計測結果の取得が出来ます
* PHP5.4以上で動きます
  * 後述しますが、PHP5.3対応版もあります

## Installation

```
composer require shimabox/smbbenchmark
```

## Usage

```php
// vendor/autoload.php を読み込みます
require_once '/your/path/to/vendor/autoload.php';
```

### example.1 通常利用

```php
// インスタンス生成
$bm = SMB\Benchmark::getInstance();

// スタート
$bm->start('bench1'); // 引数にマーキング用の値を渡す

// 計測処理
for ($i = 0; $i < 100; $i++) {
    new stdClass();
}

// 終了
$bm->end('bench1');

// 出力
// 小数点6桁(マイクロ秒単位)まで出力（デフォルト）
$bm->echoResult('bench1'); // => benchmark => bench1 : 0.・・・・・・秒
```

### example.2 計測したい処理を無名関数内に書く(measure()を使う)

```php
// インスタンス生成
$bm = SMB\Benchmark::getInstance();

// 計測したい処理をmeasure()に書く
// measure()はcallableを引数に取ります
$d = 4; // ローカル変数
$bm->measure(
    // 第1引数に無名関数も渡せてその中に処理も書ける
    function($a, $b, $c) use ($d) {
        echo $a . $b . $c . $d . PHP_EOL;
    },
    array(1, 2, 3), // 関数に対して引数を配列で渡せる
    'bench2' // start(),end()を使わなくてもマークできる
);

$bm->echoResult('bench2');

// 関数の引数なしでマークありの場合は空の配列を第2引数に渡す
$bm->measure(
    function() {
        for ($i = 0; $i < 10000; $i++) {
            new stdClass();
        }
    },
    array(),
    'bench3'
);

// オブジェクトの関数も渡せる
require_once 'Hoge.php'; // サンプル用

$obj = new Hoge();

$bm->start('bench4');
$bm->measure(array($obj, 'callbackMethod'));
$bm->end('bench4');
$bm->echoResult('bench4');

// 引数は配列で渡す
$bm->start('bench5');
$bm->measure(array($obj, 'callbackMethod'), array(1, 2, 3));
$bm->end('bench5');
$bm->echoResult('bench5');

// 静的メソッドも測定可能
$bm->start('bench6');
$bm->measure(array('Hoge', 'staticCallbackMethod'));
$bm->end('bench6');
$bm->echoResult('bench6');
```

### example.3 メソッドチェーン可能

```php
$bm = SMB\Benchmark::getInstance()
        ->start('bench7')
        ->measure(function() {
            usleep(1000000); // 1秒
        })
        ->end('bench7')
        ->measure(function() {
            usleep(500000); // 0.5秒
        }, array(), 'bench8')
        ;

$bm->echoResult('bench7');
$bm->echoResult('bench8');

// 入れ子も可能
$bm = SMB\Benchmark::getInstance()
        ->start('bench9')
        ->start('bench10')
        ->measure(function() {
            usleep(100000); // 0.1秒
        })
        ->end('bench10')
        ->measure(function() {
            usleep(50000); // 0.05秒
        }, array(), 'bench11')
        ->end('bench9')
        ;

$bm->echoResult('bench9');  // => benchmark => bench9 : 0.15・・・・秒
$bm->echoResult('bench10'); // => benchmark => bench10 : 0.1・・・・・秒
$bm->echoResult('bench11'); // => benchmark => bench10 : 0.05・・・・秒
```

### example.4 すべての計測結果を出力

```php
$bm->echoResultAll(); // => bench1〜bench11までの計測結果が出力される
```

### example.5 初期化

* 上記でわかる通りシングルトンなので初期化用メソッドを持っています

```php
// マーキングのクリア
$bm->clearMark('bench11');  // bench11をクリア
$bm->echoResult('bench11'); // => 出力なし
$bm->echoResultAll();       // => bench1〜bench10までの計測結果が出力される

// すべてのマーキングをクリア
$bm->clearMarkAll();
$bm->echoResultAll(); // => 出力なし

// インスタンスのクリア
SMB\Benchmark::clear();
```

### example.6 ベンチマークの結果だけを返す

```php
// 単独の出力
$bm = SMB\Benchmark::getInstance()
        ->measure(function() {
            usleep(50000); // 0.05秒
        }, array(), 'bench12')
        ->measure(function() {
            usleep(5000); // 0.005秒
        }, array(), 'bench13')
        ;

echo $bm->result('bench12') . PHP_EOL; // => 0.05・・・・
echo $bm->result('bench13') . PHP_EOL; // => 0.005・・・

// 計測結果すべての出力
$bm->clearMarkAll();
$bm = SMB\Benchmark::getInstance()
        ->start('bench14')
        ->start('bench15')
        ->measure(function() {
            usleep(100000); // 0.1秒
        })
        ->end('bench15')
        ->measure(function() {
            usleep(50000); // 0.05秒
        }, array(), 'bench16')
        ->end('bench14')
        ;

var_dump($bm->resultAll()); // => array('bench14'=>'0.15・・・・', 'bench15'=>'0.1・・・・・', 'bench16'=>'0.05・・・・')

$bm->clearMarkAll();
var_dump($bm->resultAll()); // => array()
```

### example.7 計測対象の処理を指定した回数繰り返し実行 (結果は平均値)

```php
// SMB\Benchmark::getInstance()->measure();
// 第4引数に繰り返し行う回数を指定します。
$bm = SMB\Benchmark::getInstance()
        ->measure(
            function() {
                usleep(2000); // 0.002秒
            },
            array(),
            'bench17',
            100 // 100回繰り返す
        )
        ;

// 結果は100回行なった結果の平均値
echo $bm->echoResult('bench17') . PHP_EOL; // benchmark => bench17 : 0.002・・・秒
```

## その他

### BCMath(任意精度数学関数)を使用しています

浮動小数点を扱う為、BCMath(任意精度数学関数)を使用しています。

[BCMath](http://php.net/manual/ja/ref.bc.php)

BCMathが使えなくても利用できますが測定結果の精度は落ちます。

### BCMath(php-bcmath) のインストール方法

例です。各環境に合わせて修正してください。

**環境**
```
$ cat /etc/redhat-release # CentOS release 6.8 (Final)
```

**インストール**
```
# enablerepoは適宜修正
$ sudo yum install php-bcmath --enablerepo=remi,remi-php56
```

**以下でインストールされているか確認できます**
```
$ yum list installed php-bcmath
インストール済みパッケージ
php-bcmath.x86_64    5.6.29-1.el6.remi    @remi-php56
```

**インストールできたらapacheのreloadを行います**
```
$ sudo service httpd reload
```

### 出力結果の小数点を変更
出力結果はデフォルトで小数点6桁(0.000000秒 マイクロ秒単位)まで表示されていますが、これは変更可能です。
※小数点を少なくした場合、処理が早すぎるとほぼ0秒に丸められます

変更するには ```setScale()``` を使います。

```php
// インスタンス生成
$bm = SMB\Benchmark::getInstance();

// 小数点4桁に変更
$bm->setScale(4);

$bm->start('other1');
for ($i = 0; $i < 1; $i++) {}
$bm->end('other1');

// 出力

// 小数点4桁まで出力
$bm->echoResult('other1'); // => benchmark => other1 : 0.XXXX秒 (処理が早過ぎると、ほぼ0.0000秒になる)


// もちろんチェーン可能
SMB\Benchmark::getInstance()
    ->setScale(4)
    ->measure(function() {}, array(), 'other2')
    ->echoResult('other2')
    ;

// 1より小さい値をセットされたらデフォルトの6桁でセットし直します。
$bm->setScale(0); // => scaleは6になる
```

### 出力結果のフォーマットを変更

echoResult(),echoResultAll() の出力フォーマットはデフォルトで
```benchmark => {$mark} : {$benchmark}秒```ですが、\SMB\Benchmark\Formatter::forEcho()を修正するか、\SMB\Benchmark\IFormatterを実装したクラスを作成しセットすることで好きなフォーマットに変更可能です。

こんなフォーマッターを作成したら、

```php
<?php

class SampleFormatter implements SMB\Benchmark\IFormatter
{
	public function forEcho($mark, $benchmark)
	{
		return '<pre>'.$mark.'の計測時間は'.$benchmark.'秒でした</pre>'.PHP_EOL;
	}
}
```

この様に使えます。

```php
require_once 'SampleFormatter.php'; // サンプル用

$formatter = new SampleFormatter();
SMB\Benchmark::getInstance()
    ->setFormatter($formatter)
    ->measure(function() {}, array(), 'bench19')
    ->echoResult('other3')
    ; // => other3の計測時間は0.XXXXXX秒でした
```

### PHP5.3対応版

このライブラリの対象はPHP5.4以上ですが、PHP5.3でも動かしたい場合は ```SMB\Benchmark\PHP53\Benchmark``` を利用してください。

違いは

* Benchmark::measure() 第1引数のタイプヒンティング(callable)を削除
* 無名関数内で$thisを使うために
  * Benchmark::resultAll(), Benchmark::echoResultAll() で$thisを退避
  * Benchmark::existsMark(), Benchmark::calc(), Benchmark::getFormatter() のアクセス修飾子をpublicに

です。(本当は配列もせっかくだから[]で扱いたかった。。)

## Test

* PHPUnitのバージョンは4.8.31で確認しています

```
vendor/bin/phpunit
```

## 注意点

当ライブラリで出した計測結果はあくまでも目安として使ってください。

## License

* MIT License
