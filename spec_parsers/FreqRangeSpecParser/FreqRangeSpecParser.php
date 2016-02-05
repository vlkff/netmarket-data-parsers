<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class FreqRangeSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Диапазон воспроизводимых частот');
    static public $categories = array();

    protected $knownMeasures = array('Гц');

    protected $measures = array(
        'Гц' => array('Гц'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        // 3 - 33000 Гц
        $patterns['/^(\d+)\s?(-|–)\s?(\d+)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                array(
                    'key' => 'Минимальная частота',
                    'value' => $matches[1],
                    'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[4], $measures),
                ),
                array(
                    'key' => 'Максимальная частота',
                    'value' => $matches[3],
                    'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[4], $measures),
                ),
            );
        };

        // 35-20000 Гц (-10 дБ)
        $patterns['/^(\d+)\s?(-|–)\s?(\d+)\s?('.$measures_list.')\s\(.*\)$/mi'] = function ($matches) use ($measures) {
            return array(
                array(
                    'key' => 'Минимальная частота',
                    'value' => $matches[1],
                    'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[4], $measures),
                ),
                array(
                    'key' => 'Максимальная частота',
                    'value' => $matches[3],
                    'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[4], $measures),
                ),
            );
        };


        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
