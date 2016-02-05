<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class VolumeCat4405SpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Объем');

    // Пароварки
    static public $categories = array(4405);

    protected $knownMeasures = array('л');

    protected function patterns() {
        $patterns = $this->generateSimplePatterns('Объем', array('л' => array('л')));
        return $patterns;
    }

    public function parse() {
        // Implement parsing of values like 3 л + 3 л + ... , we need to count their summary here
        $exploded_values = explode('+', $this->value);
        if (!empty($exploded_values) && count($exploded_values) > 1) {
            $summ = 0;
            foreach ($exploded_values as $value) {
                $parser = new VolumeCat4405SpecParser(trim($value));
                $parsed = $parser->parse();
                if ($parsed) {
                    $summ += $parsed[0]['value'];
                } else {
                    $this->parsed = false;
                    $this->matched_regex = '';
                    return $this->parsed;
                }

            }
            $this->parse_called = true;
            $this->parsed = array(array(
                'key' => 'Объем',
                'value' => $summ,
                'measure' => $parser->getParsed()[0]['measure'],
            ));
            $this->parsed = $this->validateParsed($this->parsed);
            return $this->parsed;
        }
        else {
            return parent::parse();
        }
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
