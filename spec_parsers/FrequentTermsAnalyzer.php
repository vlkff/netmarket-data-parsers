<?php

namespace common\spec_parsers;

class FrequentTermsAnalyzer
{
    private $termsArray, $excludedWords, $termsString, $sortedTermsArray, $sortedPositions;
    private $termCandidates = array();
    private $threshold, $termsArraySize;
    private $minThreshold = 4;

    public function __construct(&$termsArray, &$excludedWords = array())
    {
        $this->termsArray = &$termsArray;
        $this->excludedWords = $excludedWords;
        $this->termsString = implode(' ', $this->termsArray);
        $this->termsArraySize = count($this->termsArray);
    }

    public function emptyFrequentWords()
    {
        undef($this->termCandidates);
    }

    public function getCompoundTerms($threshold = 0.001)
    {
        $this->threshold = intval($this->termsArraySize * $threshold);
        if ($this->threshold < $this->minThreshold) $this->threshold = $this->minThreshold;
        $termLength = 2;
        while ($this->analyze1($termLength)) $termLength++;
        return $this->CandidatesCleanUp(2, $termLength - 1);
    }

    public function getFrequentWords($threshold = 0.01)
    {
        $this->threshold = intval($this->termsArraySize * $threshold);
        if ($this->threshold < $this->minThreshold) $this->threshold = $this->minThreshold;
        $this->analyze1(1);
        return $this->CandidatesCleanUp(1, 1);
    }

    public function filterFrequentWords($string)
    {
        $words = $this->getFrequentWords();
        foreach ($words as $word => $termCounter) {
            $string = preg_replace('/ $word /', '', $string);
            $string = rtrim($string, $word . ' ');
            $string = ltrim($string, ' ' . $word);
        }
        return $string;
    }

    private function analyze1($termLength)
    {
        if (isset($this->termCandidates[$termLength])) return TRUE;
        $limit = count($this->termsArray) - $termLength;
        $stringPosition = 0;
        for ($i = 0; $i < $limit; $i++) {
            $term = '';
            for ($j = $i; $j < $i + $termLength; $j++) $term .= $this->termsArray[$j] . ' ';
            $term = rtrim($term);
            $termCounter = substr_count($this->termsString, $term, $stringPosition);
            if ($termCounter >= $this->threshold && !isset($this->termCandidates[$termLength][$term])) $this->termCandidates[$termLength][$term] = $termCounter;
            try {
                $stringPosition += strlen($this->termsArray[$j]) + 1;
            } catch (\yii\base\Exception $e) {

            }

        }
        return isset($this->termCandidates[$termLength]);
    }

    private function CandidatesCleanUp($firstResult, $termLength)
    {
        $out = array();

        if (empty($this->termCandidates)) {
            return array();
        }

        for ($i = $firstResult; $i <= $termLength; $i++) {
            foreach ($this->termCandidates[$i] as $candidate => $termCounter) {
                $terms = explode(' ', $candidate);
                if ($firstResult == 1 || (!isset($this->excludedWords[$terms[0]]) && !isset($this->excludedWords[$terms[count($terms) - 1]]))) {
                    $out[$candidate] = round($termCounter / $this->termsArraySize, 4);
                }
            }
        }
        arsort($out);
        return $out;
    }

}
