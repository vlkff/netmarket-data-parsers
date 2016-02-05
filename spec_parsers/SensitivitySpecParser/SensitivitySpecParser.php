<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class SensitivitySpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Чувствительность');
    static public $categories = array();

    protected $knownMeasures = array('дБ', 'мВ', 'дБ/мВт', 'мкВ', 'дБ/В');

    protected $measures = array(
        'дБ' => array('дБ'),
        'мВ' => array('мВ'),
        'дБ/мВт' => array('дБ\/мВт'),
        'мкВ' => array('мкВ'),
        'дБ/В' => array('дБ\/В'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns('Чувствительность', $this->measures);

        // 0.16 мкВ (12dB SINAD)
        // 83 дБ (Вт/м)
        $patterns['/^(\d+\.?\d*)\s?('.$measures_list.')\s\(.*\)$/mi'] = function ($matches) use ($measures) {
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
