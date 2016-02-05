<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class TiresWeightMaxloadSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Максимальная нагрузка (на одну шину)');
    static public $categories = array(4127);

    protected $knownMeasures = array('кг');

    protected function patterns() {

        return array(
            // строгая с точкой
            '/^(\d+\.?\d*)\s?(кг){1}$/mi' =>
                function($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => 'кг',
                    );
                    return $parsed;
                },
            '/^(\d+\.?\d*)\.\.\.(\d+\.?\d*)\s?(кг){1}$/mi' =>
                function($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[2],
                        'measure' => 'кг',
                    );
                    return $parsed;
                },

        );
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
