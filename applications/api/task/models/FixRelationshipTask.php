<?php
/**
 * Class:  FixRelationshipTask
 *
 * @author: Minh Duc Nguyen <minh.nguyen@ands.org.au>
 */

namespace ANDS\API\Task;
use \Exception as Exception;

/**
 * Class FixRelationshipTask
 *
 * @package ANDS\API\Task
 */
class FixRelationshipTask extends Task
{
    public function run_task()
    {
        //load the required CI components
        $this->ci =& get_instance();
        $this->ci->load->model('registry/registry_object/registry_objects', 'ro');
        $this->ci->load->model('registry/data_source/data_sources', 'ds');
        $this->ci->load->library('solr');

        //Load parameters
        parse_str($this->params, $params);
        if (array_key_exists('id', $params)) {
            $ids = explode(',', $params['id']);
            foreach ($ids as $id) {
                $this->fixRelationshipRecord($id);
            }
        } else {
            throw new Exception("No id presents in params. params=".$this->params);
        }
    }

    /**
     * Fix the relationship index of a particular record
     *
     * @param $id
     * @throws Exception
     */
    private function fixRelationshipRecord($id)
    {

        $ro = $this->ci->ro->getByID($id);
        $relatedObjects = $ro->getAllRelatedObjects(false, false, true);

        // Relationship for this object
        $relationIndex = $ro->getRelationshipIndex();
        $this->indexSolr('relations', $relationIndex, false);
        $this->log('Fixed Relationship for ID: ' . $id);
        $portalIndex = $ro->indexable_json();
        $this->indexSolr('portal', [$portalIndex], false);
        $this->log('Fixed portal index for ID: ' . $id);

        $dataSource = $this->ci->ds->getByID($ro->data_source_id);

        $allowReverseInternalLinks = ($dataSource->allow_reverse_internal_links == "t" || $dataSource->allow_reverse_internal_links == 1);

        // Construct the reverse ones
        foreach ($relatedObjects as $related) {
            /**
             * If the data source of the related object has reverse internal link
             * AND the related and this object shares the same data source
             * OR different data source but reverse external link is on the related data source
             */
            if (array_key_exists('registry_object_id', $related)) {
                $relatedDataSourceID = $this->ci->ro->getAttribute($related['registry_object_id'], 'data_source_id');
                $allowReverse = ($relatedDataSourceID == $dataSource->id) && $allowReverseInternalLinks;
                if (!$allowReverse) {
                    $relatedDataSourceAllowReverseExternal = $this->ci->ds->getAttribute($relatedDataSourceID,
                        'allow_reverse_external_links');
                    $allowReverse = ($relatedDataSourceAllowReverseExternal == "t" || $relatedDataSourceAllowReverseExternal == 1);
                }

                if ($allowReverse) {

                    //Fix relationsIndex by replacing existing or add new relations
                    if ($relatedDataSourceID == $dataSource->id) {
                        $reverseType = 'REVERSE_INT';
                    } else {
                        $reverseType = 'REVERSE_EXT';
                    }

                    $relationDoc = [
                        'id' => md5($related['key'] . $ro->key),
                        'from_id' => $related['registry_object_id'],
                        'from_key' => $related['key'],
                        'from_status' => $related['status'],
                        'from_title' => $related['title'],
                        'from_class' => $related['class'],
                        'from_type' => $related['type'],
                        'from_slug' => $related['slug'],
                        'to_id' => $ro->id,
                        'to_class' => $ro->class,
                        'to_type' => $ro->type,
                        'to_key' => $ro->key,
                        'to_title' => $ro->title,
                        'to_slug' => $ro->slug,
                        'relation' => [$related['relation_type']],
                        'relation_origin' => [$reverseType]
                    ];

                    // additional fields
                    $relationDoc['relation_description'] = isset($rel['relation_description']) ? [$rel['relation_description']] : [];
                    $relationDoc['relation_url'] = isset($rel['relation_url']) ? [$rel['relation_url']] : [];

                    $this->indexSolr('relations', [$relationDoc], false);
                    $this->log('Fixed relationship from ' . $related['registry_object_id'] . ' to ' . $ro->id);

                    /**
                     * Fix Portal index
                     * If a relation already exist in portal, leave it, if not
                     * - intensive/easy = indexSolr the other record
                     * - fast/hard = updateSolr the other record with the missing bits
                     */
                    if ($this->relationPortalExist($ro, $related['registry_object_id'])) {
                        $this->log('Relation already exist from ' . $related['registry_object_id'] . ' to ' . $ro->id . ' in portal. No action required');
                    } else {
                        //syncs the other doc todo updateSolr instead
                        $relatedRo = $this->ci->ro->getByID($related['registry_object_id']);
                        $relatedIndex = $relatedRo->indexable_json();
                        $this->indexSolr('portal', [$relatedIndex], false);
                        $this->log('Indexed ' . $related['registry_object_id']. ' to portal core');
                    }
                }
            }
        }
    }

    /**
     * Check if a relation exists between 2 objects
     * in the Portal index
     *
     * @param $ro
     * @param $relatedID
     * @return bool
     * @throws Exception
     */
    private function relationPortalExist($ro, $relatedID)
    {
        $searchClass = $ro->class;
        if ($ro->class == 'party') {
            if (strtolower(trim($ro->type)) == 'person') {
                $searchClass = 'party_one';
            } elseif (strtolower(trim($ro->type)) == 'group') {
                $searchClass = 'party_multi';
            }
        }
        $this->ci->solr
            ->init()
            ->setCore('portal')
            ->setOpt('fl', 'id')
            ->setOpt('fq', '+related_' . $searchClass . '_id:' . $ro->id)
            ->setOpt('fq', '+id:' . $relatedID);
        $solrResult = $this->ci->solr->executeSearch(true);

        if ($solrResult) {
            if ($solrResult['response']['numFound'] > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new Exception('search for relation failed between ' . $ro->id . ' and ' . $relatedID);
        }
    }

    // @Minh: COPIED FROM SyncTask -- below -- todo refactor

    /**
     * Index into a SOLR core a set of documents
     *
     * @param string $core
     * @param array  $solr_docs
     * @param bool   $commit
     * @throws Exception
     */
    private function indexSolr($core = 'portal', $solr_docs = array(), $commit = false)
    {
        if (sizeof($solr_docs) > 0) {
            try {
                $this->ci->solr->setCore($core);

                $total = sizeof($solr_docs);
                $chunkSize = 500;
                $numChunk = ceil(($chunkSize < $total ? ($total / $chunkSize) : 1));

                for ($i = 1; $i <= $numChunk; $i++) {
                    $offset = ($i == 1) ? 0 : $i * $chunkSize;
                    $docs = array_slice($solr_docs, $offset, $chunkSize);
                    $add_result = json_decode($this->ci->solr->add_json(json_encode($docs)), true);
                    if (isset($add_result['responseHeader']) && $add_result['responseHeader']['status'] === 0) {
                        $this->log("Adding to SOLR successful $core : $i/$numChunk")->save();
                    } else {
                        $this
                            ->log("Adding to SOLR failed: " . json_encode($add_result))
                            ->log("Attempting to POST each document separately")->save();
                        $this->separateSolrIndexing($docs);
                    }
                }

                if ($commit) {
                    $commit_result = json_decode($this->ci->solr->commit(), true);
                    if (isset($commit_result['responseHeader']) && $commit_result['responseHeader']['status'] === 0) {
                        $this->log("Commit to Indexed successful")->save();
                    } else {
                        throw new Exception("Commit to SOLR failed: " . json_encode($commit_result));
                    }
                }

            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * Attempt to index into SOLR the documents separately
     * If a document failed, the entire task is recorded as failed
     * Records that aren't supposed to fail, shouldn't fail
     *
     * @param $docs
     */
    private function separateSolrIndexing($docs)
    {
        foreach ($docs as $doc) {
            $solr_docs = [$doc];
            $add_result = json_decode($this->ci->solr->add_json(json_encode($solr_docs)), true);
            if (isset($add_result['responseHeader']) && $add_result['responseHeader']['status'] === 0) {
                $this->log("Success for document : " . $doc['id'])->save();
            } else {
                $this->log("Error for document " . $doc['id'] . " Error: " . json_encode($add_result));
                //get error message and repost it without the troublesome field, namely spatial_coverage_extents_wkt
                if (isset($doc['spatial_coverage_extents_wkt'])) {
                    $message = $add_result['error']['msg'];
                    if (strpos($message, "Couldn't parse shape") >= 0) {
                        $this->indexRecordWithout($doc, 'spatial_coverage_extents_wkt');
                    }
                }
            }
        }
    }

    /**
     * Index a record in SOLR without a particular field
     * as a last resort
     *
     * @param $doc
     * @param $field
     */
    private function indexRecordWithout($doc, $field)
    {
        unset($doc[$field]);
        $solr_docs = [$doc];
        $add_result = json_decode($this->ci->solr->add_json(json_encode($solr_docs)), true);
        if (isset($add_result['responseHeader']) && $add_result['responseHeader']['status'] === 0) {
            $this->log("Success for document : " . $doc['id'] . ' without field: ' . $field)->save();
        } else {
            $this->log("Error for document " . $doc['id'] . " Error: " . json_encode($add_result));
            $this->errored = true;
        }
    }
}