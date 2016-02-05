<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class ImpendanceSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Импеданс');
    static public $categories = array();

    protected $knownMeasures = array('Ом');

    protected $measures = array(
        'Ом' => array('Ом'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns('Импеданс', $measures);

        // 4-8 Ом
        $patterns['/^(\d+\.?\d*)\s?(-|–)\s?(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Импеданс',
                'value' => $matches[3],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[4], $measures),
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
