<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

abstract class DimensionsSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array();
    static public $categories = array();

    protected $knownMeasures = array('мм', 'см');

    protected $measures = array(
        'мм' => array('мм'),
        'см' => array('см', 'cм'), // second one in ENG keyboard layout
    );

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
