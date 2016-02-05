<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class FlatNumberSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Количество режимов', 'Количество мест', 'Число пружин на место',
        'Количество камер', 'Число ступеней очистки', 'Всего конфорок');
    static public $categories = array();

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
            '/^(\d+\.\d+)$/mi' =>
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
