<?php

/**
 * Class Registry_object
 */
class Registry_object extends MX_Controller
{

    private $components = array();

    /**
     * Viewing a single registry object
     * @return HTML generated by view
     * @internal param $_GET ['id'] $_GET['slug']  $_GET['any'] $_GET['key']parsed through the dispatcher
     */
    function view()
    {
        $this->load->library('blade');

        //Setup the variables
        $ro = null;
        $id = $this->input->get('id');
        $slug = $this->input->get('slug');
        $key = $this->input->get('key');
        $any = $this->input->get('any');
        $useCache = $this->input->get('useCache') == 'no' ? false : true;

        //If there's a single value to find, is id if numeric, otherwise it's slug
        if ($any) {
            if (is_numeric($any)) {
                $id = $any;
            } else {
                $slug = $any;
            }
        }

        //If an ID is provided
        //view/{id} => redirect to {slug}/{id}
        if ($id) {
            $ro = $this->ro->getByID($id, null, $useCache);
            if ($ro && $ro->prop['status'] == 'success') {
                if (!$ro->prop['core']['slug']) {
                    //it's ok
                } else if (!$slug || $slug != $ro->prop['core']['slug']) {
                    redirect($ro->prop['core']['slug'] . '/' . $id);
                }
            }
        }

        //If a slug is provided
        //view/{slug} => redirect to {slug}/{id}
        //if there are multiple records => redirect to a search page
        if ((!$ro || $ro->prop['status'] == 'error') && $slug) {
            $ro = $this->ro->getBySlug($slug, null, $useCache);
            if ($ro == 'MULTIPLE') {
                redirect('search/#!/slug=' . $slug);
            }
            if ($ro && $ro->prop['status'] == 'success') {
                redirect($slug . '/' . $ro->prop['core']['id']);
            }
        }

        //If a key is provided
        //view/?key={key} => redirect to {slug}/{id}
        if ((!$ro || $ro->prop['status'] == 'error') && $key) {
            $ro = $this->ro->getByKey($key, $useCache);
            if ($ro && $ro->prop['status'] == 'success') {
                redirect($ro->prop['core']['slug'] . '/' . $ro->prop['core']['id']);
            }
        }


        if ($ro && $ro->prop['status'] == 'success') {
            //Found the record, handle rendering of normal view page
            $this->displayRecord($ro);
        } elseif (strpos($key, 'http://purl.org/au-research/grants/nhmrc/') !== false || strpos($key, 'http://purl.org/au-research/grants/arc/') !== false) {

            //[SPECIAL] Handle Soft 404 for grants

            //check if it's potentially an NHMRC key or ARC key
            if (strpos($key, 'http://purl.org/au-research/grants/nhmrc/') !== false) {
                $institution = 'National Health and Medical Research Council';
                $grantIdPos = strpos($key, 'nhmrc/') + 6;
            } else {
                $institution = 'Australian Research Council';
                $grantIdPos = strpos($key, 'arc/') + 4;
            }

            $grantId = substr($key, $grantIdPos);
            $purl = $key;

            $this->blade
                ->set('scripts', array('grant_form'))
                ->set('institution', $institution)
                ->set('grantId', $grantId)
                ->set('purl', $purl)
                ->render('soft_404_activity');
        } else {

            //No Record or Error

            $message = ($ro ? $ro->prop['status'] . NL . $ro->prop['message'] : false);
            $this->blade
                // ->set('scripts', array('view'))
                ->set('id', $this->input->get('id'))
                ->set('key', $this->input->get('key'))
                ->set('slug', $this->input->get('slug'))
                ->set('message', $message)
                ->render('soft_404');
        }
    }

    /**
     * Using blade to display the record
     * Construct the data that needs to be pre generated so that the render is seamless
     *
     * @param $ro
     */
    private function displayRecord($ro){

        //Setup the common variables
        $banner = asset_url('images/collection_banner.jpg', 'core');
        $theme = ($this->input->get('theme') ? $this->input->get('theme') : '2-col-wrap');
        $logo = $this->getLogo($ro->core['group']);
        $group_slug = url_title($ro->core['group'], '-', true);

        //Depends on the class of the record, show different view
        switch ($ro->core['class']) {
            case 'collection':
                $render = 'registry_object/view';
                break;
            case 'activity':
                $render = 'registry_object/activity';
                $theme = ($this->input->get('theme') ? $this->input->get('theme') : 'activity');
                $banner = asset_url('images/activity_banner.jpg', 'core');
                break;
            case 'party':
                $render = 'registry_object/party';
                $theme = ($this->input->get('theme') ? $this->input->get('theme') : 'party');
                break;
            case 'service':
                $render = 'registry_object/service';
                $theme = ($this->input->get('theme') ? $this->input->get('theme') : 'service');
                break;
            default:
                $render = 'registry_object/view';
                break;
        }

        //Record a successful view only if the record is PUBLISHED

        if ($ro->core['status'] == 'PUBLISHED') {
            $ro->event('viewed');
            $event = array(
                'event' => 'portal_view',
                'roid' => $ro->core['id'],
                'roclass' => $ro->core['class'],
                'dsid' => $ro->core['data_source_id'],
                'group' => $ro->core['group'],
                'ip' => $this->input->ip_address(),
                'user_agent' => $this->input->user_agent()
            );
            if ($this->input->get('source')) $event['source'] = $this->input->get('source');
            ulog_terms($event, 'portal', 'info');
        } else {
            //DRAFT Preview are not recorded, or should they?
        }

        // Determine resolved party identifiers
        $resolvedPartyIdentifiers = array();
        if (isset($ro->relationships['party_one'])) {
            foreach ($ro->relationships['party_one'] as $rel) {
                if (is_array($rel) && ($rel['origin'] == 'IDENTIFIER' || $rel['origin'] == 'IDENTIFIER REVERSE') && $rel['registry_object_id'] != '')
                    $resolvedPartyIdentifiers[] = $rel['related_object_identifier'];
            }
        }

        // Theme Page
        $the_theme = '';
        if(isset($ro->core['theme_page'])){
            $the_theme = $this->getTheme($ro->core['theme_page']);
        }

        //Decide whethere to show the duplicate identifier
        $show_dup_identifier_qtip = true;
        if ($this->input->get('fl') !== false) {
            $show_dup_identifier_qtip = false;
        }
        $fl = '?fl';

        //Do the rendering
        $this->blade
            ->set('scripts', array('view', 'view_app', 'tag_controller'))
            ->set('lib', array('jquery-ui', 'dynatree', 'qtip', 'map'))
            ->set('relatedLimit', 5)
            ->set('ro', $ro)
            ->set('resolvedPartyIdentifiers', $resolvedPartyIdentifiers)
            ->set('contents', $this->components['view'])
            ->set('aside', $this->components['aside'])
            ->set('view_headers', $this->components['view_headers'])
            ->set('url', $ro->construct_api_url())
            ->set('theme', $theme)
            ->set('theme_page',  $the_theme)
            ->set('logo', $logo)
            ->set('isPublished', $ro->core['status'] == 'PUBLISHED')
            ->set('banner', $banner)
            ->set('group_slug', $group_slug)
            ->set('fl', $fl)
            ->set('show_dup_identifier_qtip', $show_dup_identifier_qtip)
            ->render($render);
    }

    /**
     * Preview popup HTML content
     * Used when the user click on a related object link
     * @author Minh Duc Nguyen <minh.nguyen@ands.org.au>
     * @return view
     */
    function preview()
    {
        $this->load->library('blade');

        if ($this->input->get('ro_id')) {
            $ro = $this->ro->getByID($this->input->get('ro_id'));
            $omit = $this->input->get('omit') ? $this->input->get('omit') : false;

            ulog_terms(
                array(
                    'event' => 'portal_preview',
                    'roid' => $ro->core['id'],
                    'dsid' => $ro->core['data_source_id'],
                    'group' => $ro->core['group'],
                    'class' => $ro->core['class']
                ),
                'portal', 'info'
            );

            $this->blade
                ->set('ro', $ro)
                ->set('omit', $omit)
                ->render('registry_object/preview');
        } elseif ($this->input->get('identifier_relation_id')) {

            //hack into the registry network and grab things
            //@todo: figure things out for yourself
            $rdb = $this->load->database('registry', TRUE);
            $result = $rdb->get_where('registry_object_identifier_relationships', array('id' => $this->input->get('identifier_relation_id')));

            if ($result->num_rows() > 0) {
                $fr = $result->first_row();

                $ro = false;

                $pullback = false;
                //ORCID "Pull back"
                if ($fr->related_info_type == 'party' && $fr->related_object_identifier_type == 'orcid' && isset($fr->related_object_identifier)) {
                    $pullback = $this->ro->resolveIdentifier('orcid', $fr->related_object_identifier);
                    $filters = array('identifier_value' => $fr->related_object_identifier);
                    $ro = $this->ro->findRecord($filters);
                }

                ulog_terms(
                    array(
                        'event' => 'portal_preview',
                        'identifier_relation_id' => $this->input->get('identifier_relation_id')
                    ),
                    'portal', 'info'
                );

                $this->blade
                    ->set('record', $fr)
                    ->set('ro', $ro)
                    ->set('pullback', $pullback)
                    ->render('registry_object/preview-identifier-relation');
            }
        } else if ($this->input->get('identifier_doi')) {
            $identifier = $this->input->get('identifier_doi');

            //DOI "Pullback"
            $pullback = $this->ro->resolveIdentifier('doi', $identifier);
            $ro = $this->ro->findRecord(array('identifier_value' => $identifier));

            ulog_terms(
                array(
                    'event' => 'portal_preview',
                    'identifier_doi' => $this->input->get('identifier_doi')
                ),
                'portal', 'info'
            );

            $this->blade
                ->set('ro', $ro)
                ->set('pullback', $pullback)
                ->render('registry_object/preview_doi');
        }
    }

    /**
     * Returns the vocabulary element
     * todo move to the vocab component
     * @param string $vocab
     */
    function vocab($vocab = 'anzsrc-for')
    {
        $uri = $this->input->get('uri');
        $data = json_decode(file_get_contents("php://input"), true);
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        set_exception_handler('json_exception_handler');
        $filters = $data['filters'];
        $this->load->library('vocab');
        if (!$uri) { //get top level

            if ($vocab == 'anzsrc-for' || $vocab == 'anzsrc-seo') {
                $toplevel = $this->vocab->getTopLevel($vocab, $filters);
                echo json_encode($toplevel['topConcepts']);
            } else {
                $toplevel = $this->getSubjectsVocab($vocab, $filters);
                echo json_encode($toplevel);
            }

        } else {
            $r = array();
            $result = json_decode($this->vocab->getConceptDetail($vocab, $uri), true);
            if (isset($result['result']['primaryTopic']['narrower'])) {
                foreach ($result['result']['primaryTopic']['narrower'] as $narrower) {
                    $curi = $narrower['_about'];
                    $concept = json_decode($this->vocab->getConceptDetail($vocab, $curi), true);
                    $concept = array(
                        'notation' => $concept['result']['primaryTopic']['notation'],
                        'prefLabel' => $concept['result']['primaryTopic']['prefLabel']['_value'],
                        'uri' => $curi,
                        'collectionNum' => $this->vocab->getNumCollections($curi, $filters),
                        'has_narrower' => (isset($concept['result']['primaryTopic']['narrower']) && sizeof($concept['result']['primaryTopic']['narrower']) > 0) ? true : false
                    );

                    array_push($r, $concept);
                }
            }
            echo json_encode($r);
        }
    }

    /**
     * Returns the subject list for vocab
     * Used for Advanced Search
     * todo move to the vocab component
     * @param $vocab_type
     * @param $filters
     * @return array
     */
    function getSubjectsVocab($vocab_type, $filters)
    {
        $result = array();
        $result_type = $this->getAllSubjectsForType($vocab_type, $filters);
        if (isset($result_type['list'])) {
            $result = array_merge($result, $result_type['list']);
        }

        $azTree = array();
        $azTree['0-9'] = array('subtree' => array(), 'collectionNum' => 0, 'prefLabel' => '0-9', 'notation' => '0-9');
        foreach (range('A', 'Z') as $i) {
            $azTree[$i] = array('subtree' => array(), 'collectionNum' => 0, 'prefLabel' => $i, 'notation' => url_title($i));
        }

        foreach ($result as $r) {
            if (strlen($r['prefLabel']) > 0) {
                $first = strtoupper($r['prefLabel'][0]);
                if (is_numeric($first)) {
                    $first = '0-9';
                }
                if (ctype_alnum($first) && isset($azTree[$first])) {
                    $azTree[$first]['collectionNum']++;
                    array_push($azTree[$first]['subtree'], $r);
                }
            }
        }

        foreach ($azTree as &$com) {
            $com['has_narrower'] = $com['collectionNum'] > 0 ? true : false;
        }

        $result = array();
        foreach ($azTree as $com) {
            array_push($result, $com);
        }
        return $result;
    }

    /**
     * Returns all subjects for a particular subject type
     * Used by getSubjectsVocab
     * todo move to vocab component
     * @param $type
     * @param $filters
     * @return array|bool
     */
    private function getAllSubjectsForType($type, $filters)
    {
        $this->load->library('solr');
        $this->solr
            ->setOpt('q', '*:*')
            ->setOpt('defType', 'edismax')
            ->setOpt('mm', '3')
            ->setOpt('q.alt', '*:*')
            ->setOpt('fl', '*, score')
            ->setOpt('qf', 'id^1 group^0.8 display_title^0.5 list_title^0.5 fulltext^0.2')
            ->setOpt('rows', '0')
            ->clearOpt('fq');

        if ($filters) {
            $this->solr->setFilters($filters);
        } else {
            $this->solr->setBrowsingFilter();
        }

        $this->solr
            ->setOpt('fq', '+tsubject_'.$type.':*')
            ->setFacetOpt('field', 'tsubject_'.$type)
            ->setFacetOpt('limit', '25000');

        $content = $this->solr->executeSearch(true);

        //if still no result is found, do a fuzzy search, store the old search term and search again
        if ($this->solr->getNumFound() == 0) {
            if (!isset($filters['q'])) $filters['q'] = '';
            $new_search_term_array = explode(' ', $filters['q']);
            $new_search_term = '';
            foreach ($new_search_term_array as $c) {
                $new_search_term .= $c . '~0.7 ';
            }
            // $new_search_term = $data['search_term'].'~0.7';
            $this->solr->setOpt('q', 'fulltext:(' . $new_search_term . ') OR simplified_title:(' . iconv('UTF-8', 'ASCII//TRANSLIT', $new_search_term) . ')');
            $content = $this->solr->executeSearch(true);
        }

        if (isset($content['facet_counts']) && isset($content['facet_counts']['facet_fields'])) {
            $facets = $content['facet_counts']['facet_fields'];
        } else {
            return false;
        }

        $result = [
            'list' => []
        ];

        foreach ($facets as $facet) {
            for($i = 0; $i < sizeof($facet)-1;$i+=2) {
                $name = $facet[$i];
                $count = $facet[$i+1];
                $result['list'][] = [
                    'prefLabel' => $name,
                    'collectionNum' => $count,
                    'notation' => $name
                ];
            }
        }

        return $result;
    }

    /**
     * Returns a list of subjects with their slug
     * todo find out where this is needed
     * todo move to vocab component if it is needed, refactor required
     */
    public function getSubjects()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        set_exception_handler('json_exception_handler');
        $result = array();
        foreach ($this->config->item('subjects') as $subject) {
            $slug = url_title($subject['display'], '-', true);
            foreach ($subject['codes'] as $code) {
                $result[$slug][] = 'http://purl.org/au-research/vocabulary/anzsrc-for/2008/' . $code;
            }
        }
        echo json_encode($result);
    }

    /**
     * Resolves subjects
     * todo find out if this is needed
     * todo move to vocab component if needed, refactor
     * @param $vocab
     */
    function resolveSubjects($vocab)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        set_exception_handler('json_exception_handler');
        $data = json_decode(file_get_contents("php://input"), true);
        $subjects = $data['data'];

        $this->load->library('vocab');

        $result = array();

        if (is_array($subjects)) {
            foreach ($subjects as $subject) {
                $r = json_decode($this->vocab->getConceptDetail($vocab, 'http://purl.org/au-research/vocabulary/' . $vocab . '/2008/' . $subject), true);
                $result[$subject] = $r['result']['primaryTopic']['prefLabel']['_value'];
            }
        } else {
            $r = json_decode($this->vocab->getConceptDetail($vocab, 'http://purl.org/au-research/vocabulary/' . $vocab . '/2008/' . $subjects), true);
            $result[$subjects] = $r['result']['primaryTopic']['prefLabel']['_value'];
        }


        echo json_encode($result);
    }

    /**
     * Adding a tag to a record API end point
     * todo move to the api/registry/object/:id/tag REST API endpoint instead
     * todo update AJAX call
     */
    function addTag()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        set_exception_handler('json_exception_handler');
        $data = json_decode(file_get_contents("php://input"), true);

        $data = $data['data'];
        $data['user'] = $this->user->name();
        $data['user_from'] = $this->user->authDomain() ? $this->user->authDomain() : $this->user->authMethod();

        $event = $data;
        $event['event'] = 'portal_tag_add';
        ulog_terms($event, 'portal','info');

        $fields = '';
        foreach ($data as $key => $value) {
            $fields .= $key . '=' . rawurlencode($value) . '&';
        }//build the string

        $content = curl_post(base_url() . 'registry/services/rda/addTag', $fields, array('header' => 'multipart/form-data'));
        $this->_dropCache($data['id']);
        echo $content;
    }

    /**
     * Returns the stat of a record
     * todo move to the api/registry/object/:id/stat REST API endpoint instead
     * todo update AJAX call
     * @param  int $id
     * @return json
     */
    function stat($id)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        set_exception_handler('json_exception_handler');
        $this->load->model('registry_objects', 'ro');
        $ro = $this->ro->getByID($id);
        $stats = $ro->stat();

        echo json_encode($stats);
    }

    /**
     * increment the stats for a specified type by the value given
     * Returns the stat of a record
     * todo move to the api/registry/object/:id/stat REST API endpoint instead
     * todo update AJAX call
     * @param  int $id
     * @return json
     */
    function add_stat($id)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        set_exception_handler('json_exception_handler');
        $data = json_decode(file_get_contents("php://input"), true);
        $type = $data['data']['type'];
        $value = intval($data['data']['value']);
        $this->load->model('registry_objects', 'ro');
        $ro = $this->ro->getByID($id);
        $ro->event($type, $value);

        $event = array(
            'event' => $type,
            'roid' => $ro->core['id'],
            'roclass' => $ro->core['class'],
            'dsid' => $ro->core['data_source_id'],
            'group' => $ro->core['group'],
            'ip' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        );
        ulog_terms($event, 'portal', 'info');

        $stats = $ro->stat();

        echo json_encode($stats);
    }

    /**
     * Search View
     * Displaying the search view for the current component
     * @return HTML
     */
    function search()
    {
        //redirect to the correct URL if q is used in the search query
        if ($this->input->get('q')) {
            redirect('search/#!/q=' . $this->input->get('q'));
        }

        $this->load->library('blade');
        $this->blade
            ->set('lib', array('ui-events', 'angular-ui-map', 'google-map'))
            // ->set('scripts', array('search_app'))
            // ->set('facets', $this->components['facet'])
            ->set('search', true)//to disable the global search
            ->render('registry_object/search');
    }

    /**
     * Search View for Subjects Browser
     * Displaying the search view for the current component
     * @return HTML
     */
    function subjects()
    {
        //redirect to the correct URL if q is used in the search query
        if ($this->input->get('q')) {
            redirect('subjects/#!/q=' . $this->input->get('q'));
        }

        $this->load->library('blade');
        $this->blade
            ->set('lib', array('ui-events', 'angular-ui-map'))
            // ->set('scripts', array('search_app'))
            // ->set('facets', $this->components['facet'])
            ->set('search', true)//to disable the global search
            ->render('registry_object/subjects');
    }

    /**
     * Main search function
     * SOLR search
     *
     * @param bool $no_log
     * @return json
     */
    function filter($no_log = false)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        set_exception_handler('json_exception_handler');

        $data = json_decode(file_get_contents("php://input"), true);
        $filters = isset($data['filters']) ? $data['filters'] : false;

        // experiment with delayed response time
        // sleep(2);

        $this->load->library('solr');

        //restrict to default class
        $default_class = isset($filters['class']) ? $filters['class'] : 'collection';
        if (!is_array($default_class)) {
            $this->solr->setOpt('fq', '+class:' . $default_class);
        }

        $this->solr->setFilters($filters);




        //flags, these are the only fields that will be returned in the search
        $flags = [
            'id','type','title','description','group','data_source_id',
            'slug','spatial_coverage_centres','spatial_coverage_polygons',
            'administering_institution','researchers','matching_identifier_count',
            'list_description','earliest_year','latest_year'
        ];
        $this->solr->setOpt('fl', implode(',', $flags));

        //highlighting
        $this->solr
            ->setOpt('hl', 'true')
            ->setOpt('hl.fl', 'identifier_value_search, related_party_one_search, related_party_multi_search, related_activity_search, related_service_search, group_search, related_info_search, subject_value_resolved_search, description_value, date_to, date_from, citation_info_search')
            ->setOpt('hl.simple.pre', '&lt;b&gt;')
            ->setOpt('hl.simple.post', '&lt;/b&gt;')
            ->setOpt('hl.snippets', '2');

        //extract sentence
        $this->solr
            ->setOpt('hl.fragmenter', 'regex')
            ->setOpt('hl.fragsize', '140')
            ->setOpt('hl.regex.slop', '1.0')
            ->setOpt('hl.regex.pattern', "\w[^.!?]{400,600}[.!?]")
            ->setOpt('hl.bs.type', "SENTENCE")
            ->setOpt('hl.bs.maxScan', "30");

        // facets configuration
        $this->solr
            ->setFacetOpt('mincount', '1')
            ->solr->setFacetOpt('limit', '100')
            ->solr->setFacetOpt('sort', 'count');

        //temporal facet
        $this->solr
            ->setFacetOpt('field', 'earliest_year')
            ->setFacetOpt('field', 'latest_year')
            ->setOpt('f.earliest_year.facet.sort', 'count asc')
            ->setOpt('f.latest_year.facet.sort', 'count');


        /**
         * Set facets based on class
         * todo clean this up
         */
        if ($default_class == 'activity') {
            foreach ($this->components['activity_facet'] as $facet) {
                if ($facet != 'temporal' && $facet != 'spatial') $this->solr->setFacetOpt('field', $facet);
            }
        } elseif ($default_class == 'collection') {
            foreach ($this->components['facet'] as $facet) {
                if ($facet != 'temporal' && $facet != 'spatial') $this->solr->setFacetOpt('field', $facet);
            }
        } else {
            foreach ($this->components['facet'] as $facet) {
                if ($facet != 'temporal' && $facet != 'spatial') $this->solr->setFacetOpt('field', $facet);
            }
        }

        // Finally execute the search and get the result
        $result = $this->solr->executeSearch(true);

        //fuzzy search only if there's no result found
        if ($this->solr->getNumFound() == 0 && isset($filters['q'])) {
            $new_search_term_array = explode(' ', escapeSolrValue($filters['q']));
            $new_search_term = '';
            foreach ($new_search_term_array as $c) {
                $new_search_term .= $c . '~0.7 ';
            }
            // $new_search_term = $data['search_term'].'~0.7';
            $this->solr->setOpt('q', 'fulltext:(' . $new_search_term . ') OR simplified_title:(' . iconv('UTF-8', 'ASCII//TRANSLIT', $new_search_term) . ')');
            $result = $this->solr->executeSearch(true);
            if ($this->solr->getNumFound() > 0) {
                $result['fuzzy_result'] = true;
            }
        }

        //not recording a hit for the quick search done for advanced search
        if (!$no_log) {
            $event = array(
                'event' => 'portal_search',
                'ip' => $this->input->ip_address(),
                'user_agent' => $this->input->user_agent()
            );

            //merge event and filter so that in the event we have all the selected filters for analysis later on
            $event = $filters ? array_merge($event, $filters) : $event;



            $event['result_numFound'] = $result['response']['numFound'];

            //record search result set
            $result_roid = array();
            $result_group = array();
            $result_dsid = array();
            foreach ($result['response']['docs'] as $doc) {
                $result_roid[] = $doc['id'];
                $result_group[] = $doc['group'];
                $result_dsid[] = $doc['data_source_id'];
            }
            $result_group = array_unique($result_group);
            $result_dsid = array_unique($result_dsid);

            // glue is ,, split at reading time
            if (sizeof($result_roid) > 0) {
                $event = array_merge($event, array('result_roid'=>implode(',,', $result_roid)));
            }
            if (sizeof($result_group) > 0) {
                $event = array_merge($event, array('result_group'=>implode(',,', $result_group)));
            }
            if (sizeof($result_dsid) > 0) {
                $event = array_merge($event, array('result_dsid'=>implode(',,', $result_dsid)));
            }

            ulog_terms($event, 'portal');
        }

        // sanity check on the Query String we use SOLR to search
        $result['url'] = $this->solr->constructFieldString();

        echo json_encode($result);
    }

    /**
     * List all attribute of a registry object
     * todo find out if this is necessary, act accordingly
     *
     * @param $id
     * @param string $params
     * @return json
     */
    function get($id, $params = '')
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        set_exception_handler('json_exception_handler');

        $params = explode('-', $params);
        if (empty($params)) $params = array('core');

        $this->load->model('registry_objects', 'ro');
        $ro = $this->ro->getByID($id, $params);
        echo json_encode($ro->prop);
    }

    /**
     * Get the logo url for a groups logo if it exists!
     * todo refactor to use registry api instead
     * @param $group
     * @return string
     */
    function getLogo($group)
    {
        $this->load->model('group/groups', 'group');
        $logo = $this->group->fetchLogo($group);
        return $logo;
    }

    /**
     * Get the theme page of the record
     *
     * @param $slug
     * @return string
     */
    function getTheme($slug)
    {
        $url = registry_url() . 'services/rda/getThemePageIndex';
        $all_themes = json_decode(@file_get_contents($url), true);
        $the_theme = array();
        foreach ($all_themes['items'] as $theme) {

            if ($theme['slug'] == $slug) {
                $the_theme['title'] = $theme['title'];
                $the_theme['img_src'] = $theme['img_src'];
                return $the_theme;
            }
        }
        return false;
        // print_r($contents);
    }

    /**
     * Construction
     * Defines the components that will be displayed and search for within the application
     */
    function __construct()
    {
        parent::__construct();
        $this->load->model('registry_objects', 'ro');
        $this->components = array(
            'view' => array('descriptions', 'reuse-list', 'quality-list', 'dates-list', 'connectiontree', 'related-objects-list', 'spatial-info', 'subjects-list', 'related-metadata', 'identifiers-list'),
            'aside' => array('rights-info', 'contact-info'),
            'view_headers' => array('title', 'related-parties'),
            'facet' => array('spatial', 'group', 'license_class', 'type', 'temporal', 'access_rights'),
            'activity_facet' => array('type', 'activity_status', 'funding_scheme', 'administering_institution', 'funders')
        );
    }

    /**
     * Dropping the cache of this registry object ID
     * todo determine the nessessity of this function
     * @param $ro_id
     */
    function _dropCache($ro_id)
    {
        $api_id = 'ro-api-' . $ro_id . '-portal';
        $portal_id = 'ro-portal-' . $ro_id;
        $ci =& get_instance();
        $ci->load->driver('cache');
        try {
            $ci->cache->file->delete($api_id);
            $ci->cache->file->delete($portal_id);
        } catch (Exception $e) {

        }
    }
}