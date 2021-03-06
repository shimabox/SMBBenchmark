<?php

error_reporting(-1);
ini_set('display_errors', 1);

// vendor/autoload.php
require_once realpath(__DIR__ . '/../vendor').'/autoload.php';

/*
// composer.json
{
  "require": {
    "shimabox/smbbenchmark": "^1.0"
  }
}
*/

/*
 |------------------------------------------------------------------------------
 | example.1 通常利用
 |------------------------------------------------------------------------------
 */
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
$bm->echoResult('bench1'); // => benchmark => bench1 : 0.・・・・・・

/*
 |------------------------------------------------------------------------------
 | example.2 計測したい処理をmeasure()に書く
 |------------------------------------------------------------------------------
 */
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

/*
 |------------------------------------------------------------------------------
 | example.3 メソッドチェーン可能
 |------------------------------------------------------------------------------
 */
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

$bm->echoResult('bench9');  // => benchmark => bench9 : 0.15・・・・
$bm->echoResult('bench10'); // => benchmark => bench10 : 0.1・・・・・
$bm->echoResult('bench11'); // => benchmark => bench10 : 0.05・・・・

/*
 |------------------------------------------------------------------------------
 | example.4 すべての計測結果を出力
 |------------------------------------------------------------------------------
 */
$bm->echoResultAll(); // => bench1〜bench11までの計測結果が出力される

/*
 |------------------------------------------------------------------------------
 | example.5 初期化
 |------------------------------------------------------------------------------
 */
// 上記でわかる通りシングルトンなので初期化用メソッドを持っています

// マーキングのクリア
$bm->clearMark('bench11');  // bench11をクリア
$bm->echoResult('bench11'); // => 出力なし
$bm->echoResultAll();       // => bench1〜bench10までの計測結果が出力される

// すべてのマーキングをクリア
$bm->clearMarkAll();
$bm->echoResultAll(); // => 出力なし

// インスタンスのクリア
SMB\Benchmark::clear();

/*
 |------------------------------------------------------------------------------
 | example.6 ベンチマークの結果だけを返す
 |------------------------------------------------------------------------------
 */
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

/*
  |------------------------------------------------------------------------------
  | example.7 計測対象の処理を指定した回数繰り返し実行 (結果は平均値)
  |------------------------------------------------------------------------------
 */
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
echo $bm->echoResult('bench17') . PHP_EOL; // benchmark => bench17 : 0.002・・・

/*
 |------------------------------------------------------------------------------
 | example.8 計測結果のダウンロード/出力/保存
 |------------------------------------------------------------------------------
 | 出力(output())とダウンロード(download())は、header出力を行っているので
 | 使用する前に出力が何も無いことを確認してください
 | @see https://github.com/shimabox/SMBArrayto
 */

$bm = SMB\Benchmark::getInstance()
        ->measure(
            function() {
                usleep(1000); // 0.001秒
            },
            array(),
            'bench18'
        )
        ->measure(
            function() {
                usleep(2000); // 0.002秒
            },
            array(),
            'bench19'
        )
        ;

// e.g.) CSV形式
$csv = SMB\Arrayto\Csv::Factory();

$downloader = $csv->getDownloader();
$outputter  = $csv->getOutputter();
$writer     = $csv->getWriter();

// 第2引数で指定したファイル名でダウンロード (header出力あり)
// $bm->download($downloader, 'hoge.csv'); exit;

// 出力(header出力 + echo())
// $bm->output($outputter); exit;

// 第2引数で指定したファイルパスに保存
// $bm->write($writer, 'hoge.csv'); exit;

/*
 |------------------------------------------------------------------------------
 | その他
 |------------------------------------------------------------------------------
 */
// 浮動小数点を扱う為、BCMath 任意精度数学関数を使用しています。
// @link http://php.net/manual/ja/ref.bc.php
// BCMathが使えなくても利用できますが早い処理の測定結果は精度が落ちます。

// 出力結果はデフォルトで小数点6桁(0.000000秒 マイクロ秒単位)まで
// 表示されていますがこれは変更可能です。
// ※小数点を少なくした場合、処理が早すぎるとほぼ0秒に丸められます
// 変更するには setScale() を使います。

// インスタンス生成
$bm = SMB\Benchmark::getInstance();

// 小数点4桁に変更
$bm->setScale(4);

$bm->start('other1');
for ($i = 0; $i < 1; $i++) {}
$bm->end('other1');

// 出力

// 小数点4桁まで出力
$bm->echoResult('other1'); // => benchmark => other1 : 0.XXXX (処理が早過ぎると、ほぼ0.0000秒になる)
// もちろんチェーン可能
SMB\Benchmark::getInstance()
    ->setScale(4)
    ->measure(function() {}, array(), 'other2')
    ->echoResult('other2')
;

// 1より小さい値をセットされたらデフォルトの6桁でセットし直します。
$bm->setScale(0); // => scaleは6になる

// echoResult(),echoResultAll() での出力フォーマットはデフォルトで
//   <pre>benchmark => {$mark} : {$benchmark}</pre>
// ですが、\SMB\Benchmark\Formatter::forEcho()を修正するか、
// \SMB\Benchmark\IFormatterを実装したクラスを作成しセットすることで
// 好きなフォーマットに変更可能です。

// こんなフォーマッターを作成したら
//<?php
//
//class SampleFormatter implements SMB\Benchmark\IFormatter
//{
//	public function forEcho($mark, $benchmark)
//	{
//		return '<pre>'.$mark.'の計測時間は'.$benchmark.'秒でした</pre>'.PHP_EOL;
//	}
//}

// この様に使えます
require_once 'SampleFormatter.php'; // サンプル用

$formatter = new SampleFormatter();
SMB\Benchmark::getInstance()
    ->setFormatter($formatter)
    ->measure(function() {

    }, array(), 'other3')
    ->echoResult('other3')
; // => other3の計測時間は0.XXXXXX秒でした

// このライブラリの対象はPHP5.4以上ですが、PHP5.3でも動かしたい場合は
// v1.2.0のSMB\Benchmark\PHP53\Benchmark を利用してください。
// 違いは
//  - Benchmark::measure() 第1引数のタイプヒンティング(callable)を削除
//  - 無名関数内で$thisを使うために
//    - Benchmark::resultAll(), Benchmark::echoResultAll() で$thisを退避
//    - Benchmark::existsMark(), Benchmark::calc(), Benchmark::getFormatter() のアクセス修飾子をpublicに
//  - 計測結果のダウンロード/出力/保存機能は使用不可
// です。

// 尚、当ライブラリで出した計測結果はあくまでも目安として使ってください。