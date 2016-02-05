<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class FreezingPowerSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Мощность замораживания');
    static public $categories = array();

    protected $knownMeasures = array('кг/cутки');

    protected $measures = array(
        'кг/cутки' => array('кг\/cутки'),
    );

    protected function patterns() {

        $measure_aliases = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measure_aliases));
        $current_key = $this->current_key;

        $patterns = [];
        $patterns['/(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measure_aliases, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measure_aliases),
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
