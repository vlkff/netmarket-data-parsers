<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class CardMemorySpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Поддержка карт памяти');
    static public $categories = array();

    protected $knownMeasures = array('Гб');

    protected $measures = array(
        'Гб' => array('Гб'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        //$patterns = $this->generateSimplePatterns('Поддержка карт памяти', $measures);
        $patterns = array();

        $patterns['/.*до\s(\d+)\s?('.$measures_list.').*/i'] = function ($matches) use ($measures) {
            return array(
                'value' => $matches[1],
                'key' => 'Поддержка карт памяти, до',
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
