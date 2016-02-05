<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class RAMVolumeSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Объем', 'Объем оперативной памяти', 'Размер оперативной памяти');
    static public $categories = array();

    protected $knownMeasures = array('Гб', 'Мб', 'Тб');

    protected function patterns() {

        $measure_aliases = array(
            'Гб' => array('Гб', 'ГБ'),
            'Мб' => array('Мб', 'МБ'),
            'Тб' => array('Тб', 'ТБ'),
        );

        $current_key = $this->current_key;
        $measures_list = implode('|', $this->getMeasureAliases($measure_aliases));

        $patterns = $this->generateSimplePatterns($current_key, $measure_aliases);

        // 1 модуль 16 Гб
        $patterns['/^1\sмодуль\s(\d+)\s('.$measures_list.')$/mi'] = function ($matches) use ($measure_aliases, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measure_aliases),
            );
        };

        // 4 модуля по 4 Гб
        $patterns['/^(\d+)\sмодуля\sпо\s(\d+)\s('.$measures_list.')$/mi'] = function ($matches) use ($measure_aliases, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1] * $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measure_aliases),
            );
        };

        // 8 модулей по 8 Гб
        $patterns['/^(\d+)\sмодулей\sпо\s(\d+)\s('.$measures_list.')$/mi'] = function ($matches) use ($measure_aliases, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1] * $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measure_aliases),
            );
        };

        // 4 Гб ( ... )
        $patterns['/^(\d+)\s('.$measures_list.')\s\(.+\)$/mi'] = function ($matches) use ($measure_aliases, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measure_aliases),
            );
        };

        // 1...3 Гб => 3 Гб
        $patterns['/^(\d+\.?\d*)\.\.\.(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measure_aliases, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measure_aliases),
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
