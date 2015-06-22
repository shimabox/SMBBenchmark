<?php

namespace SMB;

/**
 * 簡易ベンチマーク計測用
 *
 * PHP version 5 >= 5.4
 *
 * <code>
 * // example.1 簡単な例
 *
 * // インスタンス生成<br>
 * $bm = SMB\Benchmark::getInstance();
 *
 * // スタート<br>
 * $bm->start('bench1'); // 引数にマーキング用の値を渡す
 *
 * // 計測処理<br>
 * for ($i = 0; $i < 100; $i++) {<br>
 *     new stdClass();<br>
 * }
 *
 * // 終了<br>
 * $bm->end('bench1');
 *
 * // 出力 echo<br>
 * // 小数点6桁まで出力（デフォルト）<br>
 * $bm->echoResult('bench1'); // => benchmark => bench1 : 0.・・・・・・秒
 *
 * // 出力 受け取り<br>
 * $ret = $bm->result('bench1');
 *
 * // example.2 計測したい処理をmeasure()に書く<br>
 * // measure()はcallableを引数に取ります
 *
 * // インスタンス生成<br>
 * $bm = SMB\Benchmark::getInstance();
 *
 * // 計測したい処理をmeasure()に書く<br>
 * $d = 4; // ローカル変数<br>
 * $bm->measure(<br>
 *     // 第1引数に無名関数も渡せてその中に処理も書ける<br>
 *     function($a, $b, $c) use ($d){<br>
 *         echo $a.$b.$c.$d.PHP_EOL;<br>
 *     },<br>
 *     array(1, 2, 3), // 関数に対して引数を配列で渡せる<br>
 *     'bench2' // start(),end()を使わなくてもマークできる<br>
 * );<br>
 *
 * // 関数の引数なしでマークありの場合は空の配列を第2引数に渡す<br>
 * $bm->measure(<br>
 *     function(){<br>
 *         for ($i = 0; $i < 10000; $i++) {<br>
 *             new stdClass();<br>
 *         }<br>
 *     },<br>
 *     array(),<br>
 *     'bench3'<br>
 * );
 *
 * // オブジェクトの関数も渡せる<br>
 * $obj = new Hoge();<br>
 * $bm->measure(array($obj, 'callbackMethod'));<br>
 * // 引数は配列で渡す<br>
 * $bm->measure(array($obj, 'callbackMethod'), array(1, 2, 3));<br>
 * // 静的メソッド<br>
 * $bm->measure(array('Hoge', 'staticCallbackMethod'));<br>
 *
 * // example.3 メソッドチェーン可能
 *
 * $bm = SMB\Benchmark::getInstance()<br>
 *         ->start('bench4')<br>
 *         ->measure(function(){<br>
 *             usleep(1000000); // 1秒<br>
 *         })<br>
 *         ->end('bench4')<br>
 *         ->measure(function(){<br>
 *                 usleep(500000); // 0.5秒<br>
 *         }, array(), 'bench5')<br>
 *         ;
 *
 * // 入れ子も可能<br>
 * $bm = SMB\Benchmark::getInstance()<br>
 *         ->start('bench6')<br>
 *         ->start('bench7')<br>
 *         ->measure(function(){<br>
 *             usleep(100000); // 0.1秒<br>
 *         })<br>
 *         ->end('bench7')<br>
 *         ->measure(function(){<br>
 *                 usleep(50000); // 0.05秒<br>
 *         }, array(), 'bench8')<br>
 *         ->end('bench6')<br>
 *         ;
 *
 * // example.4 すべての計測結果を出力<br>
 * $bm->echoResultAll(); // => bench1〜bench8までの計測結果がechoされる<br>
 * $bm->resultAll();     // => bench1〜bench8までの計測結果が配列で返される<br>
 *
 * // example.5 初期化<br>
 * // 上記でわかる通りシングルトンなので初期化用メソッドを持っています
 *
 * // マーキングのクリア<br>
 * $bm->clearMark('bench8');  // bench8をクリア<br>
 * $bm->echoResult('bench8'); // => 出力なし<br>
 * $bm->echoResultAll();      // => bench1〜bench7までの計測結果が出力される<br>
 *
 * // すべてのマーキングをクリア<br>
 * $bm->clearMarkAll();<br>
 * $bm->echoResultAll(); // => 出力なし
 *
 * // インスタンスのクリア<br>
 * SMB\Benchmark::clear();
 *
 * // その他詳細は/example/index.phpを参照
 *
 * </code>
 *
 * @example /example/index.php
 * @author  shimabox.net
 */
class Benchmark
{
    /**
     * 出力する小数点以下の桁数(デフォルト6桁 マイクロ秒単位)
     *
     * @var int
     */
    const DEFAULT_SCALE = 6;

    /**
     * インスタンス
     *
     * @var array 自身のインスタンス
     */
    protected static $instance = array();

    /**
     * BCMath 任意精度数学関数が使えるかどうか
     *
     * @var boolean
     */
    protected $canUseBCMath = false;

    /**
     * ベンチマーク開始時点のUnixタイムスタンプ(マイクロ秒)格納用
     *
     * @var array
     */
    protected $startMiclotime = array();

    /**
     * ベンチマーク開始時点のUnixタイムスタンプ格納用
     *
     * @var array
     */
    protected $startTime = array();

    /**
     * ベンチマーク終了時点のUnixタイムスタンプ(マイクロ秒)格納用
     *
     * @var array
     */
    protected $endMiclotime = array();

    /**
     * ベンチマーク終了時点のUnixタイムスタンプ格納用
     *
     * @var array
     */
    protected $endTime = array();

    /**
     * 出力する小数点以下の桁数
     *
     * @var int
     */
    protected $scale = self::DEFAULT_SCALE;

    /**
     * 出力結果フォーマッター
     *
     * @var \SMB\Benchmark\Formatter
     */
    protected $formatter = null;

    /**
     * コンストラクタ
     *
     * newしたらFatal error
     */
    private function __construct()
    {

    }

    /**
     * インスタンス取得
     *
     * @return \SMB\Benchmark
     */
    public static function getInstance()
    {
        $className = get_called_class();

        if (isset(self::$instance[$className]) !== false) {
            return self::$instance[$className];
        }

        $self               = new static();
        $self->canUseBCMath = function_exists('bcsub')
                                    && function_exists('bcadd');

        return self::$instance[$className] = $self;
    }

    /**
     * 出力する小数点以下の桁数 setter<br>
     * 0 or 負数指定時はデフォルトにセットし直す
     *
     * @param int $scale
     * @return \SMB\Benchmark
     */
    public function setScale($scale)
    {
        $_scale      = (int) $scale;
        $this->scale = $_scale < 1 ? self::DEFAULT_SCALE : $_scale;
        return $this;
    }

    /**
     * 出力結果フォーマッター setter
     *
     * @param \SMB\Benchmark\IFormatter $formatter
     * @return \SMB\Benchmark
     */
    public function setFormatter(Benchmark\IFormatter $formatter)
    {
        $this->formatter = $formatter;
        return $this;
    }

    /**
     * 開始
     *
     * @param string $mark
     * @return \SMB\Benchmark
     */
    public function start($mark)
    {
        if ($this->validMark($mark) === false) {
            return $this;
        }

        $mt = explode(" ", microtime());
        $this->startMiclotime[$mark] = (float)$mt[0];
        $this->startTime[$mark]      = time();

        return $this;
    }

    /**
     * 測定用
     *
     * @param callable $callable
     * @param array $args
     * @param string $mark
     * @return \SMB\Benchmark
     */
    public function measure(callable $callable, $args = array(), $mark = '')
    {
        !$mark ?: $this->start($mark);

        call_user_func_array($callable, $args);

        !$mark ?: $this->end($mark);

        return $this;
    }

    /**
     * 終了
     *
     * @param string $mark
     * @return \SMB\Benchmark
     */
    public function end($mark)
    {
        if ($this->validMark($mark) === false) {
            return $this;
        }

        $mt = explode(" ", microtime());
        $this->endMiclotime[$mark] = (float)$mt[0];
        $this->endTime[$mark]      = time();

        return $this;
    }

    /**
     * 結果を返す
     *
     * @param string $mark
     * @return string 結果が無ければ空文字
     */
    public function result($mark)
    {
        if ($this->existsMark($mark) === false) {
            return '';
        }

        return $this->calc($mark);
    }

    /**
     * 結果をエコー
     *
     * @param string $mark
     */
    public function echoResult($mark)
    {
        if ($this->existsMark($mark) === false) {
            return;
        }

        $benchmark = $this->calc($mark);

        echo $this->getFormatter()->forEcho($mark, $benchmark);
    }

    /**
     * 計測したものすべてを返す
     *
     * @return array
     */
    public function resultAll()
    {
        $ret = array();
        array_map(function($mark) use (&$ret) {
            if ($this->existsMark($mark, false) === false) {
                return false;
            }

            $ret[$mark] = $this->calc($mark);

        }, array_keys($this->startMiclotime));

        return $ret;
    }

    /**
     * 計測したものすべてをエコー
     */
    public function echoResultAll()
    {
        array_map(function($mark) {
            if ($this->existsMark($mark, false) === false) {
                return false;
            }

            $benchmark = $this->calc($mark);

            echo $this->getFormatter()->forEcho($mark, $benchmark);

        }, array_keys($this->startMiclotime));
    }

    /**
     * マーキングクリア
     *
     * @param string $mark
     */
    public function clearMark($mark)
    {
        if ($this->validMark($mark) === false) {
            return;
        }

        if (isset($this->startMiclotime[$mark])) {
            unset($this->startMiclotime[$mark]);
        }
        if (isset($this->startTime[$mark])) {
            unset($this->startTime[$mark]);
        }
        if (isset($this->endMiclotime[$mark])) {
            unset($this->endMiclotime[$mark]);
        }
        if (isset($this->endTime[$mark])) {
            unset($this->endTime[$mark]);
        }
    }

    /**
     * マーキングを全てクリア
     */
    public function clearMarkAll()
    {
        $this->startMiclotime = array();
        $this->startTime      = array();
        $this->endMiclotime   = array();
        $this->endTime        = array();
    }

    /**
     * インスタンスクリア
     */
    public static function clear()
    {
        $className = get_called_class();

        if (isset(self::$instance[$className]) !== false) {
            unset(self::$instance[$className]);
        }
    }

    /*
     |--------------------------------------------------------------------------
     | 内部関数
     |--------------------------------------------------------------------------
     */

    /**
     * マーキングが存在するか
     *
     * @param string $mark
     * @param boolean $checkAll すべてのマーキング保持変数をチェックするかどうか
     * @return boolean
     */
    protected function existsMark($mark, $checkAll = true)
    {
        if ($this->validMark($mark) === false) {
            return false;
        }

        if (
            isset($this->startTime[$mark])
            && isset($this->endMiclotime[$mark])
            && isset($this->endTime[$mark])
        ) {
            if ($checkAll) {
                return $this->startMiclotime[$mark];
            }

            return true;
        }

        return false;
    }

    /**
     * 処理時間を計算して返す<br>
     * BCMathが使えないと早い処理の計測精度が悪い
     *
     * @param string $mark
     * @return string
     */
    protected function calc($mark)
    {
        $diffTime = $this->endTime[$mark] - $this->startTime[$mark];

        if ($this->canUseBCMath) {
            $diffMiclotime = bcsub(
                $this->endMiclotime[$mark],
                $this->startMiclotime[$mark],
                $this->scale
            );

            return bcadd($diffTime, $diffMiclotime, $this->scale);
        }

        $diffMiclotime = $this->endMiclotime[$mark] - $this->startMiclotime[$mark];
        return $diffTime + $diffMiclotime;
    }

    /**
     * フォーマッター取得
     *
     * @return Benchmark\IFormatter
     */
    protected function getFormatter()
    {
        if ($this->formatter === null) {
            $this->formatter = new Benchmark\Formatter();
            return $this->formatter;
        }

        return $this->formatter;
    }

    /**
     * 有効なマークか
     *
     * @param string $mark
     * @return boolean
     */
    protected function validMark($mark)
    {
        if (
            $mark === ''
            || $mark === null
            || is_array($mark)
            || is_object($mark)
        ) {
            return false;
        }

        return true;
    }
}
