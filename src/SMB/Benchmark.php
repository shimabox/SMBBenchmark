<?php

namespace SMB;

use SMB\Arrayto\Interfaces;

/**
 * 簡易ベンチマーク計測用
 *
 * PHP version 5 >= 5.4
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
     * 繰り返し回数格納用
     *
     * @var array
     */
    protected $repeat = array();

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
        $this->startTime[$mark]      = (int)$mt[1];

        return $this;
    }

    /**
     * 測定用
     *
     * @param callable $callable
     * @param array $args
     * @param string $mark
     * @param int $repeat
     * @return \SMB\Benchmark
     */
    public function measure(callable $callable, $args = array(), $mark = '', $repeat = 1)
    {
        $_repeat = (int) $repeat;

        !$mark ?: $this->start($mark);

        if ($_repeat > 1) {

            for ($i = 0; $i < $_repeat; $i++) {
                call_user_func_array($callable, $args);
            }

            $this->repeat[$mark] = $_repeat;

        } else {
            call_user_func_array($callable, $args);
        }

        !$mark ? : $this->end($mark);

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
        $mt = explode(" ", microtime());

        if ($this->validMark($mark) === false) {
            return $this;
        }

        $this->endMiclotime[$mark] = (float)$mt[0];
        $this->endTime[$mark]      = (int)$mt[1];

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
     * 結果をダウンロード
     * @param SMB\Arrayto\Interfaces\Downloadable $downloader
     * @param string $fileName
     */
    public function download(Interfaces\Downloadable $downloader, $fileName)
    {
        $result = $this->getFormatter()->forOutput($this->resultAll());
        $downloader->setRows($result)->download($fileName);
    }

    /**
     * 結果を出力
     * @param SMB\Arrayto\Interfaces\Outputable $outputter
     */
    public function output(Interfaces\Outputable $outputter)
    {
        $result = $this->getFormatter()->forOutput($this->resultAll());
        $outputter->setRows($result)->output();
    }

    /**
     * 結果を保存
     * @param SMB\Arrayto\Interfaces\Writable $writer
     * @param string $fileName
     */
    public function write(Interfaces\Writable $writer, $fileName)
    {
        $result = $this->getFormatter()->forOutput($this->resultAll());
        $writer->setRows($result)->setFileName($fileName)->write();
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
     * @param boolean $checkStartMark 開始用マーキング保持変数をチェックするかどうか
     * @return boolean
     */
    protected function existsMark($mark, $checkStartMark = true)
    {
        if ($this->validMark($mark) === false) {
            return false;
        }

        if (isset($this->endMiclotime[$mark])) {

            if ($checkStartMark) {
                return isset($this->startMiclotime[$mark]);
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

            if ($this->isSpecifiedRepeat($mark)) {
                return bcdiv(
                    bcadd($diffTime, $diffMiclotime, $this->scale), $this->repeat[$mark], $this->scale
                );
            }

            return bcadd($diffTime, $diffMiclotime, $this->scale);
        }

        $diffMiclotime = $this->endMiclotime[$mark] - $this->startMiclotime[$mark];

        if ($this->isSpecifiedRepeat($mark)) {
            return ($diffTime + $diffMiclotime) / $this->repeat[$mark];
        }

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

    /**
     * 繰り返しの指定がされているか
     *
     * @param string $mark
     * @return boolean
     */
    protected function isSpecifiedRepeat($mark)
    {
        return isset($this->repeat[$mark]) && $this->repeat[$mark] > 1;
    }

}
