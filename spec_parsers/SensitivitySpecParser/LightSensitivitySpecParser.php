<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class LightSensitivitySpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Чувствительность');
    static public $categories = array();

    protected $knownMeasures = array('ISO');

    protected $measures = array(
        'ISO' => array('ISO'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns('Чувствительность', $this->measures);

        // 100 - 3200 ISO
        $patterns['/^(\d+\.?\d*)\s?-\s?(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Чувствительность',
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };
        // 100 - 3200 ISO ...
        $patterns['/^(\d+\.?\d*)\s?-\s?(\d+\.?\d*)\s?('.$measures_list.')/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Чувствительность',
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };
        // 100 ISO ...
        $patterns['/^(\d+)\s?('.$measures_list.')/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Чувствительность',
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };


        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
