<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class BatteryCapacitySpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Емкость аккумулятора');
    static public $categories = array();

    protected $knownMeasures = array('мА⋅ч', 'А⋅ч', 'Вт⋅ч', 'фотографий');

    protected $measures = array(
        'мА⋅ч' => array('мАч', 'мА⋅ч', 'мА\*ч', 'мА·ч'),
        'А⋅ч' => array('А⋅ч', 'Ач', 'А\*ч', 'А·ч'),
        'Вт⋅ч' => array('Вт⋅ч'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns('Емкость аккумулятора', $measures);

        // 7000 мА⋅ч (26 Вт⋅ч)
        $patterns['/^(\d+\.?\d*)\s?('.$measures_list.')\s\(.*\)$/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Емкость аккумулятора',
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };

        // 0...5600 мА·ч
        $patterns['/^(\d+\.?\d*)\.\.\.(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Емкость аккумулятора',
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        // 700 мА*ч или 150 фотографий
        $patterns['/^(\d+\.?\d*)\s?('.$measures_list.').*/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Емкость аккумулятора',
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };

        // 700 фотографий
        $patterns['/^(\d+)\sфотографий$/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Емкость аккумулятора',
                'value' => $matches[1],
                'measure' => 'фотографий',
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
