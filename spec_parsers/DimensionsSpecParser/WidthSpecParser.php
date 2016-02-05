<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class WidthSpecParser extends DimensionsSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Ширина');
    static public $categories = array();

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', NumericSpecParser::getMeasureAliases($measures));
        $patterns = $this->generateSimplePatterns('Ширина', $this->measures);
        $patterns['/^(\d+\.?\d*)\.\.\.(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Ширина',
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };
        $patterns['/^\d+$/mi'] = function($matches) {
            return NULL;
        };
        return $patterns;
    }
}
