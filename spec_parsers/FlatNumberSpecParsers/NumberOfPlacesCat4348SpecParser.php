<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class NumberOfPlacesCat4348SpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Количество мест');
    static public $categories = array(4348);

    protected $knownMeasures = array();

    protected function patterns() {

        return array(
            // 131
            '/^(\d+)$/mi' =>
                function($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                    );
                    return $parsed;
                },
        );
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
