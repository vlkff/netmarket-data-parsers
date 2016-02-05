<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace console\controllers;

use common\models\TermSynonym;
use Yii;
use yii\base\Exception;
use yii\console\Controller;

use common\models\SpecTermsVoc;
use common\models\SpecTerm;

use common\models\BaseSpecs;
use common\models\BaseCats;
use common\spec_parsers\TaxonomySpecParser;


class ItemListSpecController extends Controller
{
    /**
     * Show base_spec table processing stats.
     */
    public function actionIndex() {
        $output = array();
        exec('php yii help item-list-spec', $output);
        echo implode("\n", $output) . "\n";
    }

    public function actionVocAdd($key, $category = 'all', $name = '')
    {
        if ($category == 'all' || empty($category)) {
            $category = NULL;
        }

        $voc = new SpecTermsVoc();
        $voc->name = $name;
        $voc->key = $key;
        $voc->category_id = $category;
        $voc->save();
        echo "Vocabulary created with id $voc->id \n";
    }

    public function actionVocRemove($id)
    {
        $voc = SpecTermsVoc::findOne(['id' => $id]);
        if (empty($voc)) {
            echo "Vocabulary with id $voc not exists";
            return;
        }

        $yes = $this->prompt("Are you sure you want to remove vocabulary $voc->name id: $voc->id and all related terms? Type 'yes' to proceed \n");
        if ($yes == 'yes') {
            $voc = SpecTermsVoc::findOne(['id' => $id]);
            $voc->delete();
        }
    }

    public function actionVocFind($prop = '', $value = '')
    {
        $where = array();
        if (!empty($prop) && !empty($value)) {
            $where = array($prop => $value);
        }
        $vocs = SpecTermsVoc::find()->where($where)->all();
        echo "id | key | category_id | name  \n";
        array_filter($vocs, function ($voc) {
            echo "$voc->id | $voc->key | $voc->category_id | $voc->name  \n";
        });
    }

    // ToDo: implement terms aliases.
    /**
     * Import terms to vocabulary.
     * Usage: php yii item-list-spec/voc-import-terms 55 < terms.txt
     *  where 55 is vocabulary id
     */
    public function actionVocImportTerms($voc_id)
    {
        $voc = SpecTermsVoc::findOne(['id' => $voc_id]);
        if (empty($voc)) {
            echo "Vocabulary with id $voc_id not exists\n";
            return;
        }

        $fd = fopen("php://stdin", "r");
        $content = "";
        while (!feof($fd)){
            $content .= fread($fd, 1024);
        }
        fclose($fd);

        $content = $this->toUTF($content);

        $terms = SpecTerm::parseTermsStr($content);
        $terms_counter = 0;
        $synonyms_counter = 0;
        foreach ($terms as $term_name => $synonyms) {
            $term = new SpecTerm;
            $term->name = (string)$term_name;
            $term->vocabulary_id = $voc_id;
            try {
                if ($term->save()) {
                    $terms_counter++;
                }
            } catch (Exception $e) {
                if ($e->getCode() == 23505) {
                    echo "Term with name $term->name already exists in vocabulary $voc->name \n";
                } else {
                    throw $e;
                }
            }

            foreach ($synonyms as $synonym_name) {
                $synonym = new TermSynonym;
                $synonym->name = (string)$synonym_name;
                $synonym->term_id = $term->id;
                try {
                    if ($synonym->save()) {
                        $synonyms_counter++;
                    }
                } catch (Exception $e) {
                    if ($e->getCode() == 23505) {
                        echo "Synonym '$synonym->name' already exists for term '$term_name' \n";
                    } else {
                        throw $e;
                    }
                }
            }
        }
        echo "$terms_counter terms and $synonyms_counter synonyms imported to $voc->name \n";
    }

    public function actionVocFlushTerms($voc_id)
    {
        $voc = SpecTermsVoc::findOne(['id' => $voc_id]);
        if (empty($voc)) {
            echo "Vocabulary with id $voc_id not exists\n";
            return;
        }

        $yes = $this->prompt("Are you sure you want to remove all terms from vocabulary $voc->name id: $voc->id ? Type 'yes' to proceed \n");
        if ($yes == 'yes') {
            SpecTerm::deleteAll(['vocabulary_id' => $voc_id]);
            echo "All terms for vocabulary id: $voc_id has been removed\n";
        }
    }

    public function actionVocFlushRelations($voc_id)
    {
        $voc = SpecTermsVoc::findOne(['id' => $voc_id]);
        if (empty($voc)) {
            echo "Vocabulary with id $voc_id not exists\n";
            return;
        }

        $yes = $this->prompt("Are you sure you want to remove all terms->product relationships from vocabulary $voc->name id: $voc->id ? Type 'yes' to proceed \n");
        if ($yes == 'yes') {

            $sql1 = "DELETE FROM spec_parser_processor_processed WHERE base_spec_id IN ".
                "(".
                "SELECT id FROM base_spec WHERE key = :key AND item IN ".
                "(SELECT base_item_id FROM base_item_term WHERE term_id IN (SELECT id FROM term WHERE vocabulary_id = :voc_id))".
                ")";

            $sql2 = "DELETE FROM base_item_term WHERE term_id IN ".
                "(SELECT id FROM term WHERE vocabulary_id = :voc_id)";

            $removed_processed = \Yii::$app->db->createCommand($sql1, [':voc_id' => $voc_id, ':key' => $voc->key])->execute();
            $removed_relations = \Yii::$app->db->createCommand($sql2, [':voc_id' => $voc_id])->execute();

            echo "$removed_relations relations from terms to products for vocabulary id: $voc_id has been removed for $removed_processed processed specs\n";
        }
    }

    public function actionVocFindTerms($voc_id, $term_name = '')
    {
        $voc = SpecTermsVoc::findOne(['id' => $voc_id]);
        if (empty($voc)) {
            echo "Vocabulary with id $voc_id not exists\n";
            return;
        }

        $where = array('vocabulary_id' => $voc_id);
        if (!empty($term_name)) {
            $where['name'] = $term_name;
        }

        $terms = SpecTerm::find()->where($where)->all();

        echo "id | name | synonyms \n";
        array_filter($terms, function ($term) {
            echo "$term->id | $term->name ";
            foreach ($term->synonyms as $synonym) {
                echo "| $synonym->name";
            }
            echo "\n";
        });
    }

    private function toUTF($string) {
        $encodings = array('UTF-8', 'CP1251');
        $detected_encoding='';

        foreach ($encodings as $encoding) {
            if (strcmp(@iconv($encoding, $encoding, $string), $string) == 0) {
                $detected_encoding = $encoding;
                break;
            }
        }

        if (!empty($detected_encoding) && $detected_encoding !== 'UTF-8') {
            return iconv($detected_encoding, 'UTF-8', $string);
        }

        return $string;
    }

    /**
     * Find existed or create new vocabularies.
     */
    public function actionImportVocs($dir_path) {

        $counter_new_vocs = 0;
        $counter_new_terms = 0;
        $counter_new_synonyms = 0;

        if (!is_dir($dir_path)) {
            echo "Passed directory path '$dir_path' is not a directory\n";
            return;
        }

        $dirs = scandir($dir_path);
        unset($dirs[0]);
        unset($dirs[1]);
        $cat_dirs = array_filter($dirs, function($file) use ($dir_path) {
            return is_dir(implode('/', [$dir_path, $file]));
        });

        foreach ($cat_dirs as $cat_dir) {
            $dirs = scandir($dir_path.'/'.$cat_dir);
            unset($dirs[0]);
            unset($dirs[1]);
            $term_files = array_filter($dirs, function($file) use ($dir_path, $cat_dir) {
                return is_file(implode('/', [$dir_path, $cat_dir, $file])) && pathinfo($file)['extension'] == 'txt';
            });

            foreach ($term_files as $term_file) {
                // pathinfo works wrong on our server, so use explode instead
                $key = $this->toUTF(explode('.', $term_file)[0]);
                $category_id = $cat_dir;
                // Find existed or create a new one vocabulary
                $voc = SpecTermsVoc::findOne(['key' => $key, 'category_id' => $category_id]);
                if (empty($voc)) {
                    $voc = new SpecTermsVoc();
                    $voc->key = (string)($key);
                    $voc->category_id = (integer)$category_id;

                    try {
                        if ($voc->save()) {
                            echo "New vocabulary '$voc->name' created with id $voc->id \n";
                            $counter_new_vocs++;
                        }
                    } catch (Exception $e) {
                        echo "Vocabulary for key '$key' and category id $category_id NOT created\n";
                        continue;
                    }

                }

                $existed_voc_terms = [];
                $sql = "SELECT t.name as term, s.name as synonym ".
                    "FROM term t LEFT JOIN term_synonym s ON t.id = s.term_id " .
                    "WHERE t.vocabulary_id = $voc->id";
                foreach (Yii::$app->db->createCommand($sql)->queryAll() as $row) {
                    if (!isset($existed_voc_terms[ $row['term'] ])) {
                        $existed_voc_terms[ $row['term'] ] = array();
                    }
                    if (!empty($row['synonym'])) {
                        $existed_voc_terms[ $row['term'] ][] = $row['synonym'];
                    }
                }

                $content = file_get_contents(implode('/', [$dir_path, $cat_dir, $term_file]));
                $content = $this->toUTF($content);
                $import_terms = SpecTerm::parseTermsStr($content);
                foreach ($import_terms as $term_name => $synonyms) {
                    if (!array_key_exists($term_name, $existed_voc_terms)) {
                        $term = new SpecTerm;
                        $term->name = (string)$term_name;
                        $term->vocabulary_id = $voc->id;

                        if ($term->save()) {
                            echo "New term '$term->name' created with id $term->id for vocabulary $voc->name \n";
                            $counter_new_terms++;
                        }
                    } else {
                        $term = SpecTerm::findOne(['name' => $term_name]);
                    }

                    foreach ($synonyms as $synonym_name) {
                        if (!isset($existed_voc_terms[$term_name]) || !in_array($synonym_name, $existed_voc_terms[$term_name])) {
                            $synonym = new TermSynonym;
                            $synonym->term_id = $term->id;
                            $synonym->name = (string)$synonym_name;
                            if ($synonym->save()) {
                                echo "New synonym '$synonym->name' created with id $synonym->id for term $term->name \n";
                                $counter_new_synonyms++;
                            }

                        }
                    }
                }

            }
        }

        echo "$counter_new_vocs vocabularies created \n";
        echo "$counter_new_terms terms created \n";
        echo "$counter_new_synonyms synonyms created \n";
    }
}
