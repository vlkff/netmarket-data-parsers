<?php
/**
 * @todo: write description
 * @todo: implement interface for child classes and control methods and properties implementations.
 * @todo: implement debug option
 */

namespace common\spec_parsers;

use common\models\SpecTerm;
use yii\base\Exception;
use common\models\SpecTermsVoc;

/**
 * Add desc and cleanup the code.
 */
class TaxonomySpecParser {

    static private $vocs = array();
    static private $vocs_map = NULL;
    private $current_voc_ids = array();

    protected $parsed;
    protected $parse_called = false;


    /**
     * todo: rework it so it always should know both key and cat?
     */
    function __construct($key, $category_id = '', $flush_vocs_cache = FALSE) {

        $voc_ids = $this->getVocIds($key, $category_id);

        if (!($voc_ids = $this->getVocIds($key, $category_id))) {
            throw new Exception('Vocabulary not exists');
        }

        foreach ($voc_ids as $voc_id) {
            $this->initVoc($voc_id);
            $this->current_voc_ids[] = $voc_id;
        }

        $this->key = $key;
        $this->category_id = $category_id;
    }

    function parse($string) {
        $this->parsed = array();
        $this->parse_called = TRUE;

        foreach ($this->current_voc_ids as $voc_id) {
            foreach (self::$vocs[$voc_id]['terms'] as $term) {
                $term_names = $term['synonyms'];
                $term_names[] = $term['name'];
                foreach ($term_names as $term_name) {
                    if (strpos($string, $term_name) !== FALSE) {
                        $this->parsed[] = $term;
                        continue 2;
                    }
                }
            }
        }

        if (empty($this->parsed)) {
            $this->parsed = FALSE;
        }

        return $this->parsed;
    }

    public function getParsed() {
        if (!($this->parse_called)) {
            throw new Exception('The parse() method should be called before ' . __METHOD__);
        }
        return $this->parsed;
    }

    /*
     * load vocs data and terms if they wasn't loaded yet
     * return false
     */
    private function initVoc($id, $flush_cache = FALSE) {
        // load voc data and terms and write them to $this->vocs
        // @todo implement it
        if ($flush_cache) {
            self::$vocs = array();
        }

        if (array_key_exists($id, self::$vocs)) {
            return;
        }

        $voc = SpecTermsVoc::findOne(['id' => $id]);
        self::$vocs[$id] = $voc->attributes;
        self::$vocs[$id]['terms'] = array();
        $terms = SpecTerm::find()->where(['vocabulary_id' => $voc->id])->all();
        foreach ($terms as $term) {
            $term_data = $term->attributes;
            $term_data['synonyms'] = [];
            $synonyms = $term->synonyms;
            foreach ($synonyms as $synonym) {
                $term_data['synonyms'][] = $synonym->name;
            }
            self::$vocs[$id]['terms'][] = $term_data;
        }
    }

    /*
     * Return voc ids or false.
     * If category not set, return ONLY vocabularies not declare categories.
     */
    public static function getVocIds($key, $category) {
        // Find loaded voc in vocs map or
        if (is_null(self::$vocs_map)) {
            self::initVocsMap();
        }

        if (!empty($key) && empty(self::$vocs_map['by_key'][$key])) {
            return array();
        }
        if (!empty($category) && empty(self::$vocs_map['by_category'][$category])) {
            return array();
        }

        if (!empty($category) && !empty($key)) {
            $intersect = array_intersect(self::$vocs_map['by_category'][$category], self::$vocs_map['by_key'][$key]);
            return $intersect;
        }

        if (!empty($key)) {
            //return array_diff(self::$vocs_map['by_key'][$key], self::$vocs_map['declare_category']);
            return self::$vocs_map['by_key'][$key];
        }

        if (!empty($category)) {
            //return array_diff(self::$vocs_map['by_key'][$key], self::$vocs_map['declare_category']);
            return self::$vocs_map['by_category'][$category];
        }

        return array();
    }

    public static function canParse($key, $category) {
        if (self::getVocIds($key, $category)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /*
     * Build map of vocs id
     */
    private static function initVocsMap() {
        // load vocsmap
        $vocs = SpecTermsVoc::find()->all();
        self::$vocs_map = array('by_key' => [], 'by_category'=>[]);
        foreach ($vocs as $voc) {
            if (!empty($voc->key)) {
                self::$vocs_map['by_key'][$voc->key][] = $voc->id;
            }
            if(!empty($voc->category_id)) {
                self::$vocs_map['by_category'][$voc->category_id][] = $voc->id;
            }
        }
    }

    public static function getVocsMap() {
        if (empty(self::$vocs_map)) {
            self::initVocsMap();
        }
        return self::$vocs_map;
    }
}
