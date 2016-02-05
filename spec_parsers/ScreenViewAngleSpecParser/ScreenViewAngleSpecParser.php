<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class ScreenViewAngleSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Угол обзора');
    static public $categories = array();

    protected $knownMeasures = array('град.', 'град.мин');

    protected $measures = array(
        'град.' => array('°', 'град\.'),
        'град.мин' => array('град\.мин'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        // We can't use $this->generateSimplePatterns() as it can't work with parse() using preg_match_all;
        $patterns = array();
        $patterns['/^(\d+)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'value' => $matches[1][0],
                'key' => 'Угол обзора',
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2][0], $measures),
            );
        };
        $patterns['/^(\d+\.\d+)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'value' => $matches[1][0],
                'key' => 'Угол обзора',
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2][0], $measures),
            );
        };

        // 8.12 - 75.24 град.мин
        $patterns['/^(\d+\.?\d*)\s?-\s?(\d+\.?\d*)\s?('.$measures_list.')$/mi'] = function ($matches) use ($measures) {
            return array(
                'value' => $matches[2][0],
                'key' => 'Угол обзора',
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3][0], $measures),
            );
        };

        // 145° (по диагонали), 125° (по ширине), 166° (по высоте)
        // select the max one
        $patterns['/(^|\s)(\d+)\s?('.$measures_list.')/i'] = function ($matches) use ($measures) {
            $max_key = array_search(max($matches[2]), $matches[2]);
            return array(
                'value' => $matches[2][$max_key],
                'key' => 'Угол обзора',
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3][$max_key], $measures),
            );
        };

        return $patterns;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }

    /**
     * A copy of parent::parser with one distinction - use preg_match_all instead of preg_match.
     */
    public function parse() {
        if ($this->parse_called) {
            throw new Exception('->Parse() already been called');
        }

        $this->parse_called = true;
        $patterns = $this->patterns();
        if (empty($patterns)) {
            throw new Exception('No declared patterns');
        }

        if ($this->options['debug']) {
            echo "Trying to match value '$this->value' with patterns: \n";
            array_filter(array_keys($patterns), function($pattern){
                echo $pattern . "\n";
            });
        }

        foreach ($patterns as $pattern => $func) {
            $matches = array();
            if (preg_match_all($pattern, $this->value, $matches)) {

                if ($this->options['debug']) {
                    echo "Matched by pattern '$pattern', matches are: \n";
                    var_dump($matches);
                }

                $parsed = $func($matches);

                if (is_null($parsed)) {
                    $this->parsed = null;
                    $this->matched_regex = '';
                    if ($this->options['debug']) {
                        echo "Matched pattern '$pattern', return NULL \n";
                    }
                    return null;
                }

                $parsed = $this->formatParsed($parsed);

                if ($this->options['debug']) {
                    echo "Parse result is \n";
                    var_dump($parsed);
                }

                if (!empty($matches) && empty($parsed)) {
                    throw new SpecParserException("Regexp match the value but the parsing result is empty", $this, $pattern);
                }

                $this->parsed = $this->validateParsed($parsed, $pattern);
                $this->matched_regex = $pattern;

                // convert values to floats
                foreach ($this->parsed as &$row) {
                    if (is_string($row['value'])) {
                        $row['value'] = floatval($row['value']);
                    }
                }

                return $this->parsed;
            }
        }
        // Parsing wasn't success.
        if ($this->options['debug']) {
            echo "There are no matches\n";
        }

        $this->parsed = false;
        $this->matched_regex = '';
        return $this->parsed;
    }
}
