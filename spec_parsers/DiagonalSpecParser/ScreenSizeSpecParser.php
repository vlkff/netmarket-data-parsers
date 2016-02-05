<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class ScreenSizeSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Размер экрана', 'Максимальный размер экрана');
    static public $categories = array();

    protected $knownMeasures = array('дюйм.');

    protected $measures = array(
        'дюйм.' => array('"', 'дюйм.'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns($this->current_key, $measures);

        // 17...17.1 "
        $patterns['/^(\d+\.?\d*)\.\.\.(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function($matches) use ($measures) {
            $return = [];
            $return[] = array(
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
            $return[] = array(
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
