<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class LengthSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Диаметр лопастей', 'Установочный диаметр', 'Глубина культивирования',
        'Ширина скашивания', 'Макс. глубина сканирования в пресной воде', 'Глубина пропила стали',
        'Глубина пропила дерева', 'Диаметр дымохода', 'Высота', 'Размер хода платформы',
        'Макс. диаметр диска', 'Ширина захвата', 'Высота пропила', 'Диаметр диска',
        'Пробег на одном заряде', 'Длина лодки', 'Глубина до зеркала воды', 'Глубина погружения');
    static public $categories = array();

    protected $knownMeasures = array('см', 'мм', 'м', 'км');

    protected $measures = array(
        'см' => array('см'),
        'мм' => array('мм'),
        'м' => array('м'),
        'км' => array('км'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns($this->current_key, $measures);

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
