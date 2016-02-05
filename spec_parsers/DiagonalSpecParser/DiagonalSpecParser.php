<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class DiagonalSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Диагональ', 'Диагональ экрана');
    static public $categories = array();

    protected $knownMeasures = array('дюйм.', 'см');

    protected $measures = array(
        'дюйм.' => array('"', 'дюйм.'),
        'см' => array('см'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));
        $current_key = $this->current_key;

        $patterns = $this->generateSimplePatterns($current_key, $measures);

        // 50" (127 см)
        $patterns['/^(\d+)\s?('.$measures_list.')\s\(\d+\sсм\)$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };
        $patterns['/^(\d+(\.|,){1}\d+)\s?('.$measures_list.')\s\(\d+\sсм\)$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        if ($current_key == 'Диагональ экрана') {
            $patterns['/^(\d\.?\d*)$/'] = function ($matches) use ($current_key) {
                return array(
                    'key' => $current_key,
                    'value' => $matches[1],
                    'measure' => 'дюйм.',
                );
            };
        }

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
