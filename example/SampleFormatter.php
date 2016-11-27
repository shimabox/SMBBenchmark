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
        if (PHP_SAPI === 'cli') {
            return $mark . 'の計測時間は' . $benchmark . '秒でした' . PHP_EOL;
        }

        return '<pre>' . $mark . 'の計測時間は' . $benchmark . '秒でした</pre>';
    }
}
