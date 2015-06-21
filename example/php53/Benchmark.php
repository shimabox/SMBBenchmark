<?php

namespace SMB;

/**
 * 簡易ベンチマーク計測用
 *
 * PHP version 5 >= 5.3
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
     * @var array() 自身のインスタンス
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

        $this->startMiclotime[$mark] = (float)microtime();
        $this->startTime[$mark]      = time();

        return $this;
    }

    /**
     * 測定用
     *
     * @param mixed $callable
     * @param array $args
     * @param string $mark
     * @return \SMB\Benchmark
     */
    public function measure(/* callable */ $callable, $args = array(), $mark = '')
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

        $this->endMiclotime[$mark] = (float)microtime();
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
        $self = $this;
        array_map(function($mark) use (&$ret, $self) {
            if ($self->existsMark($mark, false) === false) {
                return false;
            }

            $ret[$mark] = $self->calc($mark);

        }, array_keys($this->startMiclotime));

        return $ret;
    }

    /**
     * 計測したものすべてをエコー
     */
    public function echoResultAll()
    {
        $self = $this;
        array_map(function($mark) use ($self) {
            if ($self->existsMark($mark, false) === false) {
                return false;
            }

            $benchmark = $self->calc($mark);

            echo $self->getFormatter()->forEcho($mark, $benchmark);

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
     * 無名関数内で呼ばれるのでpublic
     *
     * @param string $mark
     * @param boolean $checkAll すべてのマーキング保持変数をチェックするかどうか
     * @return boolean
     */
    public function existsMark($mark, $checkAll = true)
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
     * 無名関数内で呼ばれるのでpublic
     *
     * @param string $mark
     * @return string
     */
    public function calc($mark)
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
     * 無名関数内で呼ばれるのでpublic
     *
     * @return Benchmark\IFormatter
     */
    public function getFormatter()
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
