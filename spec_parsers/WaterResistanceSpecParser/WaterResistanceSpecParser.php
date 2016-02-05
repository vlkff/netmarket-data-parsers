<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class WaterResistanceSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Водостойкость дна', 'Водостойкость тента/дна', 'Водостойкость тента');
    static public $categories = array();

    protected $knownMeasures = array('мм в.ст.');

    protected $measures = array(
        'мм в.ст.' => array('мм в.ст.'),
    );

    protected function patterns() {

        $measures = $this->measures;

        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns($this->current_key, $measures);

        if ($this->current_key == 'Водостойкость тента/дна') {
            // 5000 / 5000 мм в.ст.
            $patterns['/^(\d+)\s\/\s(\d+)\s('.$measures_list.')$/mi'] = function($matches) use ($measures) {
                $return = [];
                $return[] = array(
                    'key' => 'Водостойкость тента',
                    'value' => $matches[1],
                    'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
                );
                $return[] = array(
                    'key' => 'Водостойкость дна',
                    'value' => $matches[2],
                    'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
                );
                return $return;
            };
        }

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
