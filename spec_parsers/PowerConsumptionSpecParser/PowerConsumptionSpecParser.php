<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class PowerConsumptionSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Потребляемая мощность', 'Потребляемая энергия');
    static public $categories = array();

    protected $knownMeasures = array('Вт', 'кВт·ч', 'ВА', 'кВт*ч/кг');

    protected $measures = array(
        'Вт' => array('Вт'),
        'кВт·ч' => array('кВт·ч', 'кВт\*ч'),
        'ВА' => array('ВА'),
        'кВт*ч/кг' => array('кВт\*ч\/кг'),
    );

    protected function patterns() {

        $current_key = $this->current_key;
        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns($this->current_key, $measures);

        // при работе: 90 Вт*
        $patterns['/^при\sработе:\s(\d+(\.|,)?\d*)\s('.$measures_list.').*/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => str_replace(',', '.', $matches[1]),
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        // 90 Вт*
        $patterns['/^(\d+)\s?('.$measures_list.').+/'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };
        $patterns['/^(\d+(\.|,){1}\d+)\s?('.$measures_list.').+/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => str_replace(',', '.', $matches[1]),
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
