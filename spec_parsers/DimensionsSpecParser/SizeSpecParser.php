<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class SizeSpecParser extends DimensionsSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Размеры');
    static public $categories = array();

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', NumericSpecParser::getMeasureAliases($measures));
        $patterns = $this->generateSimplePatterns('Размеры', $this->measures);

        // 107x62x34 мм
        // 107x62 мм
        // мы не можем парсить такие значения так как не знаем очередность величин
        $patterns['/^(\d+\.?\d*)\s?x\s?(\d+\.?\d*)\s?x\s?(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function ($matches) {
            return NULL;
        };
        $patterns['/^(\d+\.?\d*)\s?x\s?(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function ($matches) {
            return NULL;
        };
        // варианты с х в русской раскладке
        $patterns['/^(\d+\.?\d*)\s?х\s?(\d+\.?\d*)\s?х\s?(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function ($matches) {
            return NULL;
        };
        $patterns['/^(\d+\.?\d*)\s?х\s?(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function ($matches) {
            return NULL;
        };

        // 31 - 36
        // 31 - 36 (...)
        // похоже на размер одежды, и нет ед измерений, так что пропускаем
        $patterns['/^(\d+\.?\d*)\s?-\s?(\d+\.?\d*)$/mi'] = function($matches) {
            return NULL;
        };
        $patterns['/^(\d+\.?\d*)\s?-\s?(\d+\.?\d*)\s?\(.*\)$/mi'] = function($matches) {
            return NULL;
        };

        // 31
        // просто число без ед измерений, пропускаем
        $patterns['/^\d+$/mi'] = function($matches) {
            return NULL;
        };
        return $patterns;
    }
}
