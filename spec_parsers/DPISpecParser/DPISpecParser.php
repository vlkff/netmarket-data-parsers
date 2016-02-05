<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class DPISpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Разрешение оптического сенсора');
    static public $categories = array();

    protected $knownMeasures = array('dpi');

    protected function patterns() {

        $measure_aliases = array(
            'dpi' => array('dpi'),
        );

        $current_key = $this->current_key;
        $measures_list = implode('|', $this->getMeasureAliases($measure_aliases));

        $patterns = $this->generateSimplePatterns($this->current_key, $measure_aliases);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
