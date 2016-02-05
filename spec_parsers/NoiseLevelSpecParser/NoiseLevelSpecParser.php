<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class NoiseLevelSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Уровень шума');
    static public $categories = array();

    protected $knownMeasures = array('дБ', 'дБА');

    protected $measures = array(
        'дБ' => array('дБ'),
        'дБА' => array('дБА'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns('Уровень шума', $this->measures);

        // 19-21 дБА
        $patterns['/^(\d+\.?\d*)\s?-\s?(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Уровень шума',
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        // до 40 дБ
        $patterns['/^до\s(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Уровень шума',
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
