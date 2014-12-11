<?php
class Registry_object extends MX_Controller {

	private $components = array();

	function view(){
		if($this->input->get('id')){
			$ro = $this->ro->getByID($this->input->get('id'));
		}

		$this->load->library('blade');

		$this->blade
			->set('ro', $ro)
			->set('contents', $this->components['view'])
			->set('aside', $this->components['aside'])
			->render('registry_object/view');
	}

	function search() {
		if($this->input->get('q')) {
			redirect('search/#!/q='.$this->input->get('q'));
		}
		$this->load->library('blade');
		$this->blade
			->set('lib', array('angular13'))
			->set('scripts', array('search_app', 'search_components'))
			->set('facets', $this->components['facet'])
			->render('registry_object/search');
	}

	function s() {
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');

		$data = json_decode(file_get_contents("php://input"), true);
		$filters = $data['filters'];

		$this->load->library('solr');
		$this->solr->setFilters($filters);
		foreach($this->components['facet'] as $facet){
			$this->solr->setFacetOpt('field', $facet);
		}
		$this->solr->setOpt('fl', 'id,title,description,slug');
		$this->solr->setOpt('hl', 'true');
		$this->solr->setOpt('hl.fl', '*');

		$this->solr->setFacetOpt('mincount','1');
		$this->solr->setFacetOpt('limit','100');
		$this->solr->setFacetOpt('sort','count');
		$result = $this->solr->executeSearch();

		echo json_encode($result);
	}

	function get() {
		$this->load->model('registry_objects', 'ro');
		if($this->input->get('id')){
			$ro = $this->ro->getByID($this->input->get('id'));
		}
		echo json_encode($ro);
	}

	function __construct() {
		parent::__construct();
		$this->load->model('registry_objects', 'ro');
		$this->components = array(
			'view' => array('descriptions', 'identifiers-list', 'related-objects-list', 'subjects-list', 'citation-info'),
			'aside' => array('metadata-info', 'suggested-datasets-list'),
			'facet' => array('group', 'license_class', 'type')
		);
	}
}