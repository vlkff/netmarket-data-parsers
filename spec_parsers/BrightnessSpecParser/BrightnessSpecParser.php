<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class BrightnessSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Яркость');
    static public $categories = array();

    protected $knownMeasures = array('кд/м2');

    protected $measures = array(
        'кд/м2' => array('кд\/м2', 'кд\/м'),
    );

    protected function patterns() {

        $measures = $this->measures;
        //$measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns('Яркость', $measures);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
