<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class SpeedsNumberSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Количество скоростей');
    static public $categories = array();

    protected $knownMeasures = array();

    protected function patterns() {

        // 8
        $patterns['/^(\d+)$/mi'] = function ($matches) {
            return array(
                'key' => 'Количество скоростей',
                'value' => $matches[1],
                'measure' => '',
            );
        };
        // 3, ступенчатая регулировка
        $patterns['/^(\d+),\sступенчатая\sрегулировка$/mi'] = function ($matches) {
            return array(
                'key' => 'Количество скоростей',
                'value' => $matches[1],
                'measure' => '',
            );
        };

        $patterns['/^(\d+),\sплавная\sрегулировка$/mi'] = function ($matches) {
            return array(
                'key' => 'Количество скоростей',
                'value' => $matches[1],
                'measure' => '',
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
