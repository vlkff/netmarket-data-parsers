<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class LiquidBandwidthCatsSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Производительность', 'Рекомендуемая производительность');
    static public $categories = array(4632, 4494, 4400, 4549, 4528, 4553, 4637, 3979);

    protected $knownMeasures = array('л/мин', 'куб. м/ч', 'л/час');

    protected $measures = array(
        'л/мин' => array('л\/мин'),
        'л/час' => array('л\/час'),
        'куб. м/ч' => array('куб\.\s?м\/ч', 'куб\.м\/час'),
    );

    protected function patterns() {

        $patterns = parent::generateSimplePatterns($this->current_key, $this->measures);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
