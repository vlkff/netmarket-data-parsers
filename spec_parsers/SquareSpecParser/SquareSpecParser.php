<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class SquareSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Обслуживаемая площадь', 'Площадь обогрева');
    static public $categories = array();

    protected $knownMeasures = array('кв. м.');

    protected $measures = array(
        'кв. м.' => array('кв. м', 'кв.м'),
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
