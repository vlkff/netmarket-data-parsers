<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class FreqSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Макс. частота колебания платформы');
    static public $categories = array();

    protected $knownMeasures = array('кол/мин');

    protected $measures = array(
        'кол/мин' => array('кол\/мин'),
    );

    protected function patterns() {

        $patterns = array();
        $measures_list = implode('|', parent::getMeasureAliases($this->measures));
        $measures = $this->measures;
        $current_key = $this->current_key;

        $patterns = parent::generateSimplePatterns($current_key, $measures);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }

}
