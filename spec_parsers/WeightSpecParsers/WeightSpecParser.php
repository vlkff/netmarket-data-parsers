<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

use yii\base\Exception;

class WeightSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Вес', 'Вес велосипеда',
        'Вес на место', 'Предел взвешивания', 'Вес велосипеда', 'Грузоподъемность', 'Максимальная нагрузка');
    static public $categories = array();

    protected $knownMeasures = array('г', 'кг', 'т');

    protected $measures = array(
        'г' => array('г'),
        'кг' => array('кг'),
        'т' => array('т'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        return array(
            // строгая с точкой
            '/^(\d+\.{1}\d+)\s?('.$measures_list.'){1}$/mi' =>
                function($matches) use ($measures) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
                    );
                    return $parsed;
                },

            // - строгая без точки эти две работают для всех кроме 6000 рядов
            '/^(\d+)\s?('.$measures_list.'){1}$/mi' =>
                function($matches) use ($measures) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
                    );
                    return $parsed;
                },

            // - менее строгая | 4.3 кг (без шины, цепи, топлива и масла)
            '/^(\d+\.{1}\d+)\s?('.$measures_list.'){1}(.*)/i' =>
                function ($matches) use ($measures) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
                    );
                    return $parsed;
                },

            // - менее строгая         | 43 кг (без шины, цепи, топлива и масла)
            '/^(\d+)\s?('.$measures_list.'){1}(.*)/i' =>
                function ($matches) use ($measures) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
                    );
                    return $parsed;
                },

            // диапазон, беру 2е значение | 34.1123...344 г
            '/^(\d+\.{0,1}\d*)\.\.\.(\d+\.{0,1}\d*)\s*('.$measures_list.'){1}$/mi' =>
                function ($matches) use ($measures) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[2],
                        'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
                    );
                    return $parsed;
                },

            // диапазон, беру 2е значение | 34.1123-344 г
            '/^(\d+\.{0,1}\d*)-(\d+\.{0,1}\d*)\s*('.$measures_list.'){1}$/mi' =>
                function ($matches) use ($measures) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[2],
                        'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
                    );
                    return $parsed;
                },

            // | 1,6 кг
            '/^(\d+)\,{1}(\d+)\s?('.$measures_list.'){1}$/mi' =>
                function ($matches) use ($measures) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1] . '.' . $matches[2],
                        'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
                    );
                    return $parsed;
                },

            // | 2.55
            '/^(\d+\.{1}\d+)$/mi' =>
                function ($matches) use ($measures) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => 'кг',
                    );
                    return $parsed;
                },

            // | 6
            '/^(\d+)$/mi' =>
                function ($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => 'кг',
                    );
                    return $parsed;
                },
        );
    }

    protected function convertMeasureSingleValue($to_measure, $row) {

        if ($row['measure'] == 'т' || $to_measure == 'т') {
            throw new Exception('Can\'t convert from тонны and to тонны');
        }

        if ($row['measure'] == 'г' && $to_measure == 'кг') {
            $row['value'] = $row['value'] / 1000;
        }
        if ($row['measure'] == 'кг' && $to_measure == 'г') {
            $row['value'] = $row['value'] * 1000;
        }
        return $row;
    }
}
