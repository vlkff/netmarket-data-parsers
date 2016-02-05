<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class PerformanceCat4051SpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Производительность');
    static public $categories = array(4051);

    protected $knownMeasures = array('листов/день');

    protected $measures = array(
        'листов/день' => array('листов\/день'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));
        $current_key = $this->current_key;

        $patterns = $this->generateSimplePatterns($this->current_key, $measures);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
