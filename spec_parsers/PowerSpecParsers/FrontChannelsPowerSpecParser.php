<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class FrontChannelsPowerSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Мощность фронтальных каналов', 'Мощность фронтальных колонок');
    static public $categories = array();

    protected $knownMeasures = array('Вт');

    protected $measures = array(
        'Вт' => array('Вт'),
    );

    protected function patterns() {

        $patterns = array();
        $measures_list = implode('|', parent::getMeasureAliases($this->measures));
        $measures = $this->measures;
        $current_key = $this->current_key;

        // 2x55 Вт
        $patterns['/^(\d+)x(\d+)\s?('. $measures_list .')$/mi'] = function ($matches) use ($measures, $current_key) {
            return array(
                'key' => $current_key,
                'value' => $matches[1][0] * $matches[2][0],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[3][0], $measures),
            );
        };

        $patterns['/(\d+)\s?('. $measures_list .')/mi'] = function ($matches) use ($measures, $current_key) {
            $max_key = array_search(max($matches[1]), $matches[1]);
            return array(
                'key' => $current_key,
                'value' => $matches[1][$max_key],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2][$max_key], $measures),
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
