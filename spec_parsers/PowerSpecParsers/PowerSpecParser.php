<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class PowerSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Мощность', 'Блок питания', 'Мощность звука',
        'Мощность всасывания', 'Мощность передатчика',
        'Выходная мощность, пиковая', 'Активная мощность', 'Полная мощность',
        'Номинальная мощность', 'Максимальная мощность мотора', 'Максимальная нагрузка');
    static public $categories = array();

    protected $knownMeasures = array('Вт', 'мВт', 'кВт', 'л.с.', 'кВА', 'ВА', 'дБм');

    protected $measures = array(
        'Вт' => array('Вт'),
        'мВт' => array('мВт'),
        'кВт' => array('кВт'),
        'л.с.' => array('л\.с\.', 'л\.\sс\.'),
        'кВА' => array('кВ·А', 'кВА', 'kVA'),
        'ВА' => array('ВА', 'В·А'),
        'дБм' => array('dBM', 'дБм'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));
        $electric_measures = array_diff_key($measures, array('л.с.' => ''));
        $electric_measures_list = implode('|', $this->getMeasureAliases($electric_measures));
        $current_key = $this->current_key;

        $patterns = $this->generateSimplePatterns($this->current_key, $measures);

        // 10...12 л.с. => 12 л.с.
        $patterns['/^(\d+\.?\d*)\.\.\.(\d+\.?\d*)\s('.$measures_list.')$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        // XX Вт (номинальная)
        $patterns['/^(\d+)\s('.$measures_list.')\s\(номинальная\)$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => ($current_key == 'Мощность') ? 'Мощность номинальная' : $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };
        $patterns['/^(\d+\.\d+)\s('.$measures_list.')\s\(номинальная\)$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => ($current_key == 'Мощность') ? 'Мощность максимальная' : $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };
        // XX Вт (максимальная)
        $patterns['/^(\d+)\s('.$measures_list.')\s\(максимальная\)$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => ($current_key == 'Мощность') ? 'Мощность максимальная' : $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };
        $patterns['/^(\d+\.\d+)\s('.$measures_list.')\s\(максимальная\)$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => ($current_key == 'Мощность') ? 'Мощность максимальная' : $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };

        // 2400 Вт (выходная 1600 Вт)
        $patterns['/^(\d+)\s('.$electric_measures_list.')\s\(выходная\s(.+)\s('.$electric_measures_list.')\)$/mi'] = function ($matches) use ($electric_measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $electric_measures),
            );
        };
        $patterns['/^(\d+\.\d+)\s('.$electric_measures_list.')\s\(выходная\s(.+)\s('.$electric_measures_list.')\)$/mi'] = function ($matches) use ($electric_measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $electric_measures),
            );
        };


        // 800 ВА / 480 Вт
        $patterns['/^(\d|\.)+\sВА\s\/\s(\d+)\s('.$electric_measures_list.')$/mi'] = function ($matches) use ($electric_measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[2],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $electric_measures),
            );
        };
        $patterns['/^(\d|\.)+\s(ВА|кВ·А)\s\/\s(\d+\.\d+)\s('.$electric_measures_list.')$/mi'] = function ($matches) use ($electric_measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[3],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[4], $electric_measures),
            );
        };

        // 300 Вт, при блокировке мотора: 2000 Вт
        $patterns['/^(\d+)\s('.$electric_measures_list.'),\sпри\sблокировке\sмотора:\s((\d|\.)+)\s('.$electric_measures_list.')$/mi'] = function ($matches) use ($electric_measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $electric_measures),
            );
        };
        $patterns['/^(\d+\.\d+)\s('.$electric_measures_list.'),\sпри\sблокировке\sмотора:\s((\d|\.)+)\s('.$electric_measures_list.')$/mi'] = function ($matches) use ($electric_measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $electric_measures),
            );
        };

        // 2400 <measure> (...)
        $patterns['/^(\d+)\s?('.$measures_list.')\s\(.+\)$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };
        $patterns['/^(\d+(\.|,){1}\d+)\s?('.$measures_list.')\s\(.+\)$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => str_replace(',', '.', $matches[1]),
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        // 2400 <measure>...
        $patterns['/^(\d+)\s?('.$measures_list.').+/'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => str_replace(',', '.', $matches[1]),
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2], $measures),
            );
        };
        $patterns['/^(\d+(\.|,){1}\d+)\s?('.$measures_list.').+/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => str_replace(',', '.', $matches[1]),
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3], $measures),
            );
        };

        return $patterns;
    }

    public function parse() {
        if ($this->parse_called) {
            return $this->getParsed();
        }


        if ($this->options['debug']) {
            echo "Trying to match value '$this->value' \n";
        }

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $parse_exploded = function($exploded_values, &$this, $pattern) {
            if ($this->options['debug']) {
                echo "Exploded values are ".var_export($exploded_values, true)." \n";
            }

            $result = array();
            foreach ($exploded_values as $value) {
                $parser = new PowerSpecParser(trim($value));
                if ($this->options['debug']) {
                    $parser->setOption('debug', true);
                }
                $parsed = $parser->parse();
                if ($parsed) {
                    $result = array_merge($result, $parsed);
                }
            }

            if (!empty($result)) {
                $this->parsed = $result;
                $this->parsed = $this->validateParsed($this->parsed);
                $this->matched_regex = $pattern;
                $this->parse_called = true;
                if ($this->options['debug']) {
                    echo "Parse result is ".var_export($this->parsed, true)." \n";
                }
                return $this->parsed;
            } else {
                if ($this->options['debug']) {
                    echo "Parse result is empty \n";
                }
                $this->parsed = false;
                $this->parse_called = true;
                $this->matched_regex = '';
                return false;
            }
        };

        // First, try to process special cases.
        // parse 40 Вт (номинальная), 140 Вт (максимальная)
        $pattern = '/^(.+)\s('.$measures_list.')\s\(номинальная\),\s(.+)\s('.$measures_list.')\s\(максимальная\)$/mi';
        if (preg_match($pattern, $this->value)) {
            $exploded_values = explode(',', $this->value);
            if (!empty($exploded_values) && count($exploded_values) == 2) {
                return $parse_exploded($exploded_values, $this, $pattern);
            }
        }

        // 30 л.с., 22000 Вт
        $pattern = '/^(.+)\s('. implode('|', $measures['л.с.']) .'),\s(.+)\s('.$measures_list.')$/mi';
        if (preg_match($pattern, $this->value)) {
            $exploded_values = explode(',', $this->value);
            if (!empty($exploded_values) && count($exploded_values) == 2) {
                return $parse_exploded(array($exploded_values[0]), $this, $pattern);
            }
        }

        // 1600 Вт / 2.1 л. с.
        $pattern = '/^(.+)\s('.$measures_list.')\s\/\s(.+)\s('. implode('|', $measures['л.с.']) .')$/mi';
        if (preg_match($pattern, $this->value)) {
            $exploded_values = explode('/', $this->value);
            if (!empty($exploded_values) && count($exploded_values) == 2) {
                return $parse_exploded(array($exploded_values[1]), $this, $pattern);
            }
        }

        // 1600 Вт (макс.|пиковая 2000 Вт)
        $electric_measures = array_diff_key($measures, array('л.с.' => ''));
        $electric_measures = $this->getMeasureAliases($electric_measures);
        $electric_measures = implode('|', $electric_measures);
        $pattern = '/^((.+)\s('.$electric_measures.'))\s\((макс\.|пиковая)\s((.+)\s('.$electric_measures.'))\)$/mi';
        $matches = array();
        if (preg_match($pattern, $this->value, $matches)) {
            $result = array();
            $parser_nominal = new PowerSpecParser(trim($matches[1]));
            $parser_nominal->parse();
            $parser_max = new PowerSpecParser(trim($matches[5]));
            $parser_max->parse();
            if ($parser_nominal->getParsed()) {
                $result[] = array(
                    'value' => $parser_nominal->getParsed()[0]['value'],
                    'measure' => $parser_nominal->getParsed()[0]['measure'],
                    'key' => 'Мощность номинальная',
                );
            }
            if ($parser_max->getParsed()) {
                $result[] = array(
                    'value' => $parser_max->getParsed()[0]['value'],
                    'measure' => $parser_max->getParsed()[0]['measure'],
                    'key' => 'Мощность максимальная',
                );
            }
            if (!empty($result)) {
                $this->parsed = $result;
                $this->parsed = $this->validateParsed($this->parsed);
                $this->matched_regex = $pattern;
                $this->parse_called = true;
                if ($this->options['debug']) {
                    echo "Parse result is ".var_export($this->parsed, true)." \n";
                }
                return $this->parsed;
            } else {
                if ($this->options['debug']) {
                    echo "Parse result is empty \n";
                }
                $this->parsed = false;
                $this->parse_called = true;
                $this->matched_regex = '';
                return false;
            }

        }

        // If the parsed value should not be processed by code above let it to be processed by standard method with patterns declared in $this->patterns.
        return parent::parse();
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
