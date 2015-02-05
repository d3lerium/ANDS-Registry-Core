<?php
class Registry_object extends MX_Controller {

	private $components = array();

	/**
	 * Viewing a single registry object
	 * @param $_GET['id'] parsed through the dispatcher
	 * @todo  $_GET['slug'] or $_GET['any']
	 * @return HTML generated by view
	 */
	function view(){

		if($this->input->get('id')){
			$ro = $this->ro->getByID($this->input->get('id'));
		}

		$this->load->library('blade');

		$theme = ($this->input->get('theme') ? $this->input->get('theme') : '2-col-wrap');

        switch($ro->core['class']){
            case 'collection':
                $render = 'registry_object/view';
                break;
            case 'activity':
                $render = 'registry_object/activity';
                $theme = ($this->input->get('theme') ? $this->input->get('theme') : 'activity');
                break;
            default:
                $render = 'registry_object/view';
                break;
        }

		//record event
		$ro->event('view');
		ulog_terms(
			array(
				'event' => 'portal_view',
				'roid' => $ro->core['id'],
				'roclass' => $ro->core['class'],
				'dsid' => $ro->core['data_source_id'],
				'group' => $ro->core['group'],
				'ip' => $this->input->ip_address(),
				'user_agent' => $this->input->user_agent()
			),'portal', 'info'
		);

		$this->blade
			->set('scripts', array('view', 'view_app', 'tag_controller'))
			->set('lib', array('jquery-ui', 'dynatree', 'qtip'))
			->set('ro', $ro)
			->set('contents', $this->components['view'])
            ->set('activity_contents',$this->components['activity'])
			->set('aside', $this->components['aside'])
            ->set('activity_aside', $this->components['activity_aside'])
            ->set('view_headers', $this->components['view_headers'])
			->set('url', $ro->construct_api_url())
			->set('theme', $theme)
			->render($render);
	}

	function preview($id) {
		$this->load->library('blade');
		$ro = $this->ro->getByID($id);
		$this->blade
			->set('ro', $ro)
			->render('registry_object/preview');
	}

	function addTag() {
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		set_exception_handler('json_exception_handler');
		$data = json_decode(file_get_contents("php://input"), true);

		$data = $data['data'];
		$data['user'] = $this->user->name();
		$data['user_from'] = $this->user->authDomain();

		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,base_url().'registry/services/rda/addTag');//post to SOLR
		curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$content = curl_exec($ch);//execute the curl
		curl_close($ch);//close the curl

		echo $content;
	}

	/**
	 * Returns the stat of a record
	 * @param  int $id
	 * @return json
	 */
	function stat($id) {
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		set_exception_handler('json_exception_handler');
		$this->load->model('registry_objects', 'ro');
		$ro = $this->ro->getByID($id);
		$stats = $ro->stat();

		echo json_encode($stats);
	}

	/**
	 * Search View
	 * Displaying the search view for the current component
	 * @return HTML 
	 */
	function search() {
		//redirect to the correct URL if q is used in the search query
		if($this->input->get('q')) {
			redirect('search/#!/q='.$this->input->get('q'));
		}
		$this->load->library('blade');
		$this->blade
			->set('lib', array('ui-events', 'angular-ui-map', 'google-map'))
			// ->set('scripts', array('search_app'))
			->set('facets', $this->components['facet'])
			->set('search', true) //to disable the global search
			->render('registry_object/search');
	}

	/**
	 * Main search function
	 * SOLR search
	 * @param  string $class class restriction
	 * @return json
	 */
	function filter($no_record = false) {
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		set_exception_handler('json_exception_handler');

		$data = json_decode(file_get_contents("php://input"), true);
		$filters = $data['filters'];

		// experiment with delayed response time
		// sleep(2);

		$this->load->library('solr');

		//restrict to default class
		$default_class = isset($filters['class']) ? $filters['class'] : 'collection';
		$this->solr->setOpt('fq', '+class:'.$default_class);

		$this->solr->setFilters($filters);

		//not recording a hit for the quick search done for advanced search
		if (!$no_record) {
			$event = array(
				'event' => 'portal_search',
				'ip' => $this->input->ip_address(),
				'user_agent' => $this->input->user_agent()
			);
			$event = array_merge($event, $filters);
			ulog_terms($event,'portal');
		}
		

		//returns this set of Facets
		foreach($this->components['facet'] as $facet){
			if ($facet!='temporal' && $facet!='spatial') $this->solr->setFacetOpt('field', $facet);
		}

		//flags, these are the only fields that will be returned in the search
		$this->solr->setOpt('fl', 'id,title,description,group,slug,spatial_coverage_centres,spatial_coverage_polygons');

		//highlighting
		$this->solr->setOpt('hl', 'true');
		$this->solr->setOpt('hl.fl', '*');
		$this->solr->setOpt('hl.simple.pre', '&lt;b&gt;');
		$this->solr->setOpt('hl.simple.post', '&lt;/b&gt;');

		//experiment hl attrs
		// $this->solr->setOpt('hl.alternateField', 'description');
		// $this->solr->setOpt('hl.alternateFieldLength', '100');
		// $this->solr->setOpt('hl.fragsize', '300');
		// $this->solr->setOpt('hl.snippets', '100');

		$this->solr->setFacetOpt('mincount','1');
		$this->solr->setFacetOpt('limit','100');
		$this->solr->setFacetOpt('sort','count');
		$result = $this->solr->executeSearch();
		$result->{'url'} = $this->solr->constructFieldString();

		echo json_encode($result);
	}

	/**
	 * List all attribute of a registry object
	 * for development only!
	 * @return json 
	 */
	function get($id) {
		$this->load->model('registry_objects', 'ro');
		$ro = $this->ro->getByID($id);
		echo json_encode($ro);
	}

	/**
	 * Construction
	 * Defines the components that will be displayed and search for within the application
	 */
	function __construct() {
		parent::__construct();
		$this->load->model('registry_objects', 'ro');
		$this->components = array(
			'view' => array('descriptions','reuse-list','quality-list','dates-list','spatial-info', 'connectiontree','publications-list','related-objects-list',  'subjects-list', 'identifiers-list','tags'),
			'aside' => array('rights-info','contact-info'),
            'view_headers' => array('title','related-parties'),
            'activity'=>array('descriptions','spatial-info','publications-list', 'subjects-list','identifiers-list','contact-info'),
            'activity_aside'=>('related-objects-list'),
			'facet' => array('spatial','group', 'license_class', 'type', 'temporal', 'access_rights')
		);
	}
}