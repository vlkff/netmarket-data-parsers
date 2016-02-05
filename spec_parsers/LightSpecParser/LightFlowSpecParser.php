<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class LightFlowSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Световой поток');
    static public $categories = array();

    protected $knownMeasures = array('люмен');

    protected $measures = array(
        'люмен' => array('люмен', 'ANSI люмен', 'ANSI лм','Lm', 'люм'),
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
