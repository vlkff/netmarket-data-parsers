<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class DiscVolumeSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Объем накопителя');
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
