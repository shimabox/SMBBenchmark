<?php

/**
 * sample
 */
class SampleFormatter implements SMB\Benchmark\IFormatter
{
    /**
     * echo用フォーマット
     *
     * @param string $mark
     * @param float $benchmark
     * @return string
     */
    public function forEcho($mark, $benchmark)
    {
        return '<pre>' . $mark . 'の計測時間は' . $benchmark . '秒でした</pre>' . PHP_EOL;
    }
}
