<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class VolumeCat3951SpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Объем');

    // Рюкзаки
    static public $categories = array(3951);

    protected $knownMeasures = array('л');

    protected function patterns() {

        $patterns = $this->generateSimplePatterns('Объем', array('л' => array('л')));

        $patterns['/^(\d+)\.\.\.(\d+)\sл$/mi'] = function ($matches) {
            return array(
                'key' => 'Объем',
                'value' => $matches[2],
                'measure' => 'л',
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
