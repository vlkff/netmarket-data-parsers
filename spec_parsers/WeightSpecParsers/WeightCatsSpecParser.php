<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class WeightCatsSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Максимальная нагрузка');
    static public $categories = array(3982, 4312);

    protected $knownMeasures = array('г', 'кг');

    protected function patterns() {

        return array(
            // строгая с точкой
            '/^(\d+\.{1}\d+)\s?(г|кг){1}$/mi' =>
                function($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => $matches[2],
                    );
                    return $parsed;
                },

            // - строгая без точки эти две работают для всех кроме 6000 рядов
            '/^(\d+)\s?(г|кг){1}$/mi' =>
                function($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => $matches[2],
                    );
                    return $parsed;
                },

            // - менее строгая | 4.3 кг (без шины, цепи, топлива и масла)
            '/^(\d+\.{1}\d+)\s?(г|кг){1}(.*)/i' =>
                function ($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => $matches[2],
                    );
                    return $parsed;
                },

            // - менее строгая         | 43 кг (без шины, цепи, топлива и масла)
            '/^(\d+)\s?(г|кг){1}(.*)/i' =>
                function ($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1],
                        'measure' => $matches[2],
                    );
                    return $parsed;
                },

            // диапазон, беру 2е значение | 34.1123...344 г
            '/^(\d+\.{0,1}\d*)\.\.\.(\d+\.{0,1}\d*)\s*(г|кг){1}$/mi' =>
                function ($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[2],
                        'measure' => $matches[3],
                    );
                    return $parsed;
                },

            // диапазон, беру 2е значение | 34.1123-344 г
            '/^(\d+\.{0,1}\d*)-(\d+\.{0,1}\d*)\s*(г|кг){1}$/mi' =>
                function ($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[2],
                        'measure' => $matches[3],
                    );
                    return $parsed;
                },

            // | 1,6 кг
            '/^(\d+)\,{1}(\d+)\s?(г|кг){1}$/mi' =>
                function ($matches) {
                    $parsed = array();
                    $parsed[] = array(
                        'value' => $matches[1] . '.' . $matches[2],
                        'measure' => $matches[3],
                    );
                    return $parsed;
                },

            // | 2.55
            '/^(\d+\.{1}\d+)$/mi' =>
                function ($matches) {
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
        if ($row['measure'] == 'г' && $to_measure == 'кг') {
            $row['value'] = $row['value'] / 1000;
        }
        if ($row['measure'] == 'кг' && $to_measure == 'г') {
            $row['value'] = $row['value'] * 1000;
        }
        return $row;
    }
}
