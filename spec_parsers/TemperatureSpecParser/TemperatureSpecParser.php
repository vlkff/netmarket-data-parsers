<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class TemperatureSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Нижняя температура комфорта', 'Экстремальная температура', 'Верхняя температура комфорта',
        'Температура пара', 'Максимальная температура нагрева воды');
    static public $categories = array();

    protected $knownMeasures = array('°C');

    protected $measures = array(
        '°C' => array('°C', '°С'),
    );

    protected function patterns() {

        $measure_aliases = $this->measures;
        $current_key = $this->current_key;

        $measures_list = implode('|', $this->getMeasureAliases($measure_aliases));

        $patterns = $this->generateSimplePatterns($this->current_key, $measure_aliases);

        $patterns = [];
        $patterns['/(\+?\-?)(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measure_aliases, $current_key) {
            $value = ($matches[1] == '-') ? $matches[1].$matches[2] : $matches[2];
            return array(
                'key' => $current_key,
                'value' => $value,
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measure_aliases),
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
