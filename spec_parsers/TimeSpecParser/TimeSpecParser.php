<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class TimeSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Время работы', 'Время горения', 'Время нагрева воды',
        'Время работы при полной нагрузке', 'Срок службы лампы', 'Продолжительность работы');
    static public $categories = array();

    protected $knownMeasures = array('ч', 'м', 'c');

    protected $measures = array(
        'ч' => array('ч', 'часов', 'час'),
        'м' => array('м' ,'мин'),
        'c' => array('с' ,'сек'),
    );

    protected function patterns() {

        $measures = $this->measures;

        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns($this->current_key, $measures);

        // 7.75...9 ч
        $patterns['/^(\d+\.?\d*)\.\.\.(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function($matches) use ($measures) {
            $return = [];
            $return[] = array(
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
