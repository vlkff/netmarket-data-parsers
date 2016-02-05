<?php
/**
 * @todo: write description
 *
 * A special class for skipping parsing categories we are sure we have no to parse.
 *
 */

namespace common\spec_parsers;

class VolumeSkipCatsSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Объем');

    // Духи
    static public $categories = array(4167);

    protected $knownMeasures = array();

    protected function patterns() {

    }

    public function parse() {
        $this->parsed = NULL;
        $this->matched_regex = '';
        $this->parse_called = true;
        return NULL;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
