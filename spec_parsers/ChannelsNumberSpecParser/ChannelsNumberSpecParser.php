<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class ChannelsNumberSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Количество каналов');
    static public $categories = array();

    protected $knownMeasures = array();

    protected function patterns() {

        $patterns = array();

        // parse flat numbers 30
        $patterns['/^(\d+)$/mi'] = function ($matches) {
            return array(
                'key' => 'Количество каналов',
                'value' => $matches[1],
                'measure' => '',
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
