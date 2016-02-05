<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class VolumeCat4523SpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Объем');

    // Утюги
    static public $categories = array(4523);

    protected $knownMeasures = array('мл');

    protected function patterns() {
        $patterns['/^резервуар\sдля\sводы\sна\s(\d+)\sмл$/mi'] = function ($matches) {
            return array(
                'key' => 'Емкость для воды',
                'value' => $matches[1],
                'measure' => 'мл',
            );
        };
        $patterns['/^бойлер\sна\s(\d+)\sмл$/mi'] = function ($matches) {
            return array(
                'key' => 'Емкость для воды',
                'value' => $matches[1],
                'measure' => 'мл',
            );
        };

        $patterns['/^резервуар\sдля\sводы\sна\s(\d+)\sмл,\sбойлер\sна\s(\d+)\sмл$/mi'] = function ($matches) {
            return array(
                'key' => 'Емкость для воды',
                'value' => $matches[1] + $matches[2],
                'measure' => 'мл',
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
