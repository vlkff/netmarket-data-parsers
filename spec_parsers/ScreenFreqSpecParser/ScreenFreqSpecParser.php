<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class ScreenFreqSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Частота обновления');
    static public $categories = array();

    protected $knownMeasures = array('Гц');

    protected $measures = array(
        'Гц' => array('Гц'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));
        $current_key = $this->current_key;

        $patterns = $this->generateSimplePatterns($current_key, $measures);

        // строк: 24-94 кГц; кадров: 50-76 Гц
        $patterns['/^строк:\s\d+-\d+\sкГц;\sкадров:\s(\d+)-(\d+)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        // ...кадров: 50-76 Гц...
        $patterns['/.*кадров:\s(\d+)-(\d+)\s('.$measures_list.').*/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
