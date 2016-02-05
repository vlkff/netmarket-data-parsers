<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class TiresloadIndexCat4127SpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Индекс нагрузки');
    static public $categories = array(4127);

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
            '/^(\d+)\.\.\.(\d+)$/mi' =>
                function($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[2],
                    );
                    return $parsed;
                },
        );
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
