<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class DataBandwidthSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Пропускная способность', 'Скорость чтения данных', 'Скорость записи данных');
    static public $categories = array();

    protected $knownMeasures = array('Мб/с');

    protected $measures = array(
        'Мб/с' => array('Мб\/с')
    );

    protected function patterns() {

        $current_key = $this->current_key;
        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = parent::generateSimplePatterns($current_key, $this->measures);

        // 60...90 Мб/с
        $patterns['/^(\d+\.?\d*)\.\.\.(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function($matches) use ($measures, $current_key) {
            $return = [];
            $return[] = array(
                'key' => $current_key,
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
