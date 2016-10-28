<?php

namespace tourze\swoole\yii2\log;

use Yii;
use yii\helpers\Console;
use yii\log\Logger;
use yii\log\Target;

/**
 * ConsoleTarget writes log to console (useful for debugging console applications)
 *
 * @author pahanini <pahanini@gmail.com>
 */
class ConsoleTarget extends Target
{

    /**
     * @var bool If true context message will be added to the end of output
     */
    public $enableContextMassage = false;

    public $displayCategory = false;

    public $displayDate = false;

    public $dateFormat = 'Y-m-d H:i:s';

    public $padSize = 30;

    /**
     * @var array color scheme for message labels
     */
    public $color = [
        'error' => Console::BG_RED
    ];

    /**
     * @inheritdoc
     * @return string
     */
    protected function getContextMessage()
    {
        return $this->enableContextMassage ? parent::getContextMessage() : '';
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        foreach ($this->messages as $message) {
            if ($message[1] == Logger::LEVEL_ERROR) {
                Console::error($this->formatMessage($message));
            } else {
                Console::output($this->formatMessage($message));
            }
        }
    }

    /**
     * @param array $message
     * 0 - massage
     * 1 - level
     * 2 - category
     * 3 - timestamp
     * 4 - ???
     *
     * @return string
     */
    public function formatMessage($message)
    {

        $label = $this->generateLabel($message);
        $text = $this->generateText($message);

        return str_pad($label, $this->padSize, ' ') . ' '.$text;
    }

    /**
     * @param $message
     *
     * @return string
     */
    private function generateLabel($message)
    {
        $label = '';

        //Add date to log
        if (true == $this->displayDate) {
            $label.= '['.date($this->dateFormat, $message[3]).']';
        }

        //Add category to label
        if (true == $this->displayCategory) {
            $label.= "[".$message[2]."]";
        }
        $level = Logger::getLevelName($message[1]);

        $tmpLevel= "[$level]";

        if (Console::streamSupportsAnsiColors(\STDOUT)) {
            if (isset($this->color[$level])) {
                $tmpLevel = Console::ansiFormat($tmpLevel, [$this->color[$level]]);
            } else {
                $tmpLevel = Console::ansiFormat($tmpLevel, [Console::BOLD]);
            }
        }
        $label.= $tmpLevel;

        return $label;
    }

    /**
     * @param $message
     *
     * @return string
     */
    private function generateText($message)
    {
        $text = $message[0];
        if (is_array($text) || is_object($text)) {
            $text = "Array content is \n\r".json_encode($text, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (!is_string($text)) {
            $text = 'Message is ' . gettype($text);
        }
        return $text;
    }
}
