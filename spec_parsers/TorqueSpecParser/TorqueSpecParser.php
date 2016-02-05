<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class TorqueSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Максимальный крутящий момент');
    static public $categories = array();

    protected $knownMeasures = array('Н·м');

    protected $measures = array(
        'Н·м' => array('Н·м', 'Н\*м'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns($this->current_key, $measures);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
