<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class FocalLengthSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Фокусное расстояние');
    static public $categories = array();

    protected $knownMeasures = array('мм');

    protected $measures = array(
        'мм' => array('мм'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns($this->current_key, $measures);

        $patterns['/^(\d+\.?\d*)\s-\s(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            $return = [];
            $return[] = array(
                'key' => 'Фокусное расстояние (мин.)',
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
            $return[] = array(
                'key' => 'Фокусное расстояние (макс.)',
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
            return $return;
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
