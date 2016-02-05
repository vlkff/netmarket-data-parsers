<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class CPUFreqSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Частота процессора');
    static public $categories = array();

    protected $knownMeasures = array('МГц', 'ГГц');

    protected $measures = array(
        'МГц' => array('МГц', 'мГц', 'MHz'),
        'ГГц' => array('ГГц', 'GHz'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = $this->generateSimplePatterns('Частота процессора', $measures);

        // 0...5600 мА·ч
        $patterns['/^(\d+\.?\d*)\.\.\.(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'key' => 'Частота процессора',
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
