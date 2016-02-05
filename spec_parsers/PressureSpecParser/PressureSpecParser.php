<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class PressureSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Максимальный напор');
    static public $categories = array();

    protected $knownMeasures = array('м');

    protected $measures = array(
        'м' => array('м'),
    );

    protected function patterns() {

        $measures = $this->measures;

        $patterns = $this->generateSimplePatterns('Максимальный напор', $measures);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
