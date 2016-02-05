<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class WxHxDSpecParser extends DimensionsSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Размеры (ШxВxГ)', 'Габариты (ШxВxГ)', 'Размеры без подставки (ШxВxГ)');
    static public $categories = array();

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = array();
        $patterns['/^(\d+\.?\d*)\s?x\s?(\d+\.?\d*)\s?x\s?(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            $measure = \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[4], $measures);
            return array(
                array(
                    'key' => 'Ширина',
                    'value' => $matches[1],
                    'measure' => $measure,
                ),
                array(
                    'key' => 'Высота',
                    'value' => $matches[2],
                    'measure' => $measure,
                ),
                array(
                    'key' => 'Глубина',
                    'value' => $matches[3],
                    'measure' => $measure,
                ),
            );
        };
        // вариант c х в русской раскладке
        $patterns['/^(\d+\.?\d*)\s?х\s?(\d+\.?\d*)\s?х\s?(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            $measure = \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[4], $measures);
            return array(
                array(
                    'key' => 'Ширина',
                    'value' => $matches[1],
                    'measure' => $measure,
                ),
                array(
                    'key' => 'Высота',
                    'value' => $matches[2],
                    'measure' => $measure,
                ),
                array(
                    'key' => 'Глубина',
                    'value' => $matches[3],
                    'measure' => $measure,
                ),
            );
        };

        return $patterns;
    }
}
