<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class TimeCat4579SpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Продолжительность работы');
    static public $categories = array(4579);

    protected $knownMeasures = array('страниц');

    protected $measures = array(
        'страниц' => array('страниц', 'стр'),
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
