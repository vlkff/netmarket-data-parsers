<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class VolumeSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Объем', 'Объем двигателя', 'Отапливаемый объем', 'Объем бака',
        'Объем чаши', 'Объем холодильной камеры', 'Объем морозильной камеры', 'Рабочий объем',
        'Ресурс стандартного фильтрующего модуля');
    static public $categories = array();

    protected $knownMeasures = array('куб. м', 'л', 'мл', 'куб. см');

    protected function patterns() {

        // array of measures and their regexp's variants
        $measure_aliases = array(
            'куб. м' => array('куб\.\sм', 'м³'),
            'л' => array('л'),
            'мл' => array('мл'),
            'куб. см' => array('куб\.\sсм', 'куб\.см'),
        );
        $patterns = $this->generateSimplePatterns($this->current_key, $measure_aliases);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
