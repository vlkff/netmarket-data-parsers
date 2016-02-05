<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class LiquidBandwidthSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Пропускная способность', 'Воздухообмен', 'Максимальный воздухообмен', 'Расход топлива', 'Производительность');
    static public $categories = array();

    protected $knownMeasures = array('куб. м/час', 'л/ч', 'кг/ч', 'л/мин', 'кг/мин');

    protected $measures = array(
        'куб. м/час' => array('куб\.\sм\/час', 'куб\.м\/ч'),
        'л/ч' => array('л\/ч', 'л\/час'),
        'кг/ч' => array('кг\/ч'),
        'кг/мин' => array('кг\/мин'),
        'л/мин' => array('л\/мин'),
    );

    protected function patterns() {

        $patterns = parent::generateSimplePatterns($this->current_key, $this->measures);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
