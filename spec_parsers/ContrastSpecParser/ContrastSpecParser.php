<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class ContrastSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Контрастность');
    static public $categories = array();

    protected $knownMeasures = array();

    protected function patterns() {

        $patterns = array();

        // 800:1
        $patterns['/^(\d+):1$/mi'] = function ($matches) {
            return array(
                'key' => 'Контрастность x:1',
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
