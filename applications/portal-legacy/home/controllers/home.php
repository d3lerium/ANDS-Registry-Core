<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends MX_Controller {

	function index(){
		ini_set('xdebug.profiler_enable',1);
		$data['title']='Research Data Australia';

		check_services();

		//solr for counts
		$this->load->library('solr');
		$this->solr->setOpt('q', '*:*');
		//$this->solr->setOpt('fq', 'status:PUBLISHED');
		$this->solr->setOpt('rows','0');
		$this->solr->setFacetOpt('field', 'class');
		$this->solr->executeSearch();

		//classes
		$classes = $this->solr->getFacetResult('class');
		$data = array('collection'=>0,'service'=>0,'activity'=>0,'party'=>0);
		foreach($classes as $class=>$num){
			$data[$class] = $num;
		}

		$this->solr->init();
		$this->solr->setOpt('q', 'class:("collection")');
		//$this->solr->setOpt('fq', 'status:PUBLISHED');
		$this->solr->setOpt('rows','0');
		$this->solr->setFacetOpt('field', 'group');
		$this->solr->setFacetOpt('limit', '200');
		$this->solr->executeSearch();
		//groups
		$groups = $this->solr->getFacetResult('group');
		$data['groups'] = array();
		foreach($groups as $group=>$num){
			if ($num > 0)
			{
				$data['groups'][$group] = $num;
			}
		}

		$this->load->library('stats');
		// $this->stats->registerPageView();
		//spotlights
		//
	
		//register page view
		$event = array(
			'event'=>'portal_page',
			'page' => 'home',
			'ip' => $this->input->ip_address(),
			'user_agent' => $this->input->user_agent()
		);
		ulog_terms($event,'portal');
		
		$data['scripts'] = array('home_page');
		$data['js_lib'] = array('qtip', 'popup');
		$this->load->view('home', $data);
	}


	function contributors(){
		//solr for counts
		$this->load->library('solr');
		$this->solr->setOpt('q', 'class:("collection")');
		//$this->solr->setOpt('fq', 'status:PUBLISHED');
		$this->solr->setOpt('rows','0');
		$this->solr->setFacetOpt('field', 'class');
		$this->solr->setFacetOpt('field', 'group');
		$this->solr->setFacetOpt('sort', 'group asc');
		$this->solr->setFacetOpt('limit', '200');
		$this->solr->executeSearch();

		//groups
		$groups = $this->solr->getFacetResult('group');

		$data['groups'] = array();
		foreach($groups as $group=>$num){
			if ($num > 0)
			{
				$data['groups'][$group] = $num;
			}
		}
		ksort($data['groups'], SORT_FLAG_CASE | SORT_NATURAL);

		//contributors
		$this->load->model('view/registry_fetch','registry');
		$data['contributors'] = $this->registry->fetchInstitutionalPages();

		$links = array();
		foreach($data['groups'] as $g=>$count){
			$l = '';
			if(sizeof($data['contributors']['contents'])>0){
				foreach($data['contributors']['contents'] as $c){
					if($c['title']==$g){
						$l = anchor($c['slug'].'/'.$c['registry_object_id'], $g.' ('.$count.')');
						break;
					}else{
						$l = anchor('search#!/group='.rawurlencode($g), $g.' ('.$count.')');
					}
				}
			}else{
				$l = anchor('search#!/group='.rawurlencode($g), $g.' ('.$count.')');
			}
			array_push($links, $l);
		}
		$data['links'] = $links;
		
		$event = array(
			'event'=>'portal_page',
			'page' => 'contributors',
			'ip' => $this->input->ip_address(),
			'user_agent' => $this->input->user_agent()
		);
		ulog_terms($event,'portal');

		$data['title'] = 'Contributors - Research Data Australia';
		$this->load->view('who_contributes', $data);
	}

	function about(){
		$data['title'] = 'About - Research Data Australia';
		$event = array(
			'event'=>'portal_page',
			'page' => 'about',
			'ip' => $this->input->ip_address(),
			'user_agent' => $this->input->user_agent()
		);
		ulog_terms($event,'portal');
		$this->load->view('about', $data);
	}

	function disclaimer(){
		$data['title'] = 'Disclaimer - Research Data Australia';
		$event = array(
			'event'=>'portal_page',
			'page' => 'disclaimer',
			'ip' => $this->input->ip_address(),
			'user_agent' => $this->input->user_agent()
		);
		ulog_terms($event,'portal');
		$this->load->view('disclaimer', $data);
	}

	function privacy_policy() {
		$data['title'] = 'Privacy Policy - Research Data Australia';
		$event = array(
			'event'=>'portal_page',
			'page' => 'privacy_policy',
			'ip' => $this->input->ip_address(),
			'user_agent' => $this->input->user_agent()
		);
		ulog_terms($event,'portal');
		$this->load->view('privacy_policy', $data);
	}

	function contact(){
		$data['title'] = 'Contact Us - Research Data Australia';
		$data['message'] = '';
		$site_admin_email = get_config_item('site_admin_email');

		$event = array(
			'event'=>'portal_page',
			'page' => 'contact_page',
			'ip' => $this->input->ip_address(),
			'user_agent' => $this->input->user_agent()
		);
		ulog_terms($event,'portal');

		/*
			Obscure text email address from contact us page to help avoid email scrapers
		*/
		$data['contact_email'] = $this->myobfiscate($site_admin_email);
		if($this->input->get('sent')!=''){
			$this->load->library('user_agent');
			$data['user_agent']=$this->agent->browser();

			/* 
				check if fields hidden from actual users but avaiable to bots have been filled - if so let's not email 
			*/
			$title = $this->input->post('title');
			$last_name = $this->input->post('last_name');
			$bogus_email = $this->input->post('email');		

			/*
				also check that all three fields have been filled out - if not let's not email
			*/
			$name = $this->input->post('first_name');
			$email = $this->input->post('contact_email');
			$content = $this->input->post('content');

			if($title || $last_name || $bogus_email || !$name || !$email || !$content)
			{
				$data['sent'] = false;
				$data['message'] = "Please fill in all required fields.";
			//	$this->load->view('contact', $data);
			}
			else
			{
				$this->load->library('email');
				$this->email->from($email, $name);
				$this->email->to($site_admin_email);
				$this->email->subject('RDA Contact Us');
				$this->email->message($content);
				$this->email->send();
				$data['sent'] = true;
			}

		}else 
		{
			$data['sent'] = false;
		}
		
		$this->load->view('contact', $data);
	}

	function falling_water_register(){
		$this->load->library('user_agent');
		$data['user_agent']=$this->agent->browser();

		$site_admin_email = get_config_item('site_admin_email');

		$name = $this->input->post('name');
		$email = $this->input->post('email');

		$this->load->library('email');
		$this->email->from($email, $name);
		$this->email->to($site_admin_email);
		$this->email->subject('RDA new Falling Water participant');
		$this->email->message('A new user has registered to participate in project Falling Water: '.$name.' <'.$email.'>');
		$this->email->send();

	}

	function sitemap($page=''){
    	parse_str($_SERVER['QUERY_STRING'], $_GET);
    	$solr_url = get_config_item('solr_url');
    	$ds = '';
    	if(isset($_GET['ds'])) $ds=$_GET['ds'];

    	$event = array(
			'event'=>'portal_page',
			'page' => 'sitemap',
			'ip' => $this->input->ip_address(),
			'user_agent' => $this->input->user_agent()
		);
		ulog_terms($event,'portal');

    	if ($page == 'main'){
    		$pages = array(
    			base_url(),
    			base_url('home/about'),
    			base_url('home/contact'),
    			base_url('home/privacy_policy'),
    			base_url('themes')
    		);

    		header("Content-Type: text/xml");
			echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			foreach ($pages as $p) {
				echo '<url>';
				echo '<loc>'.$p.'</loc>';
				echo '<changefreq>weekly</changefreq>';
				echo '<lastmod>'.date('Y-m-d').'</lastmod>';
				echo '</url>';
			}
			echo '</urlset>';
    	} else {
	    	if($ds==''){

				$this->load->library('solr');
				$this->solr->setFacetOpt('field', 'data_source_key');
				$this->solr->setFacetOpt('limit', 1000);
				$this->solr->setFacetOpt('mincount', 0);

				$this->solr->executeSearch();
				$res = $this->solr->getFacet();

		    	$dsfacet = $res->{'facet_fields'}->{'data_source_key'};

				header("Content-Type: text/xml");
				echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
				echo '<sitemap><loc>'.base_url('home/sitemap/main').'</loc><lastmod>'.date('Y-m-d').'</lastmod></sitemap>';
				for($i=0;$i<sizeof($dsfacet);$i+=2){
					echo '<sitemap>';
					echo '<loc>'.base_url().'home/sitemap/?ds='.urlencode($dsfacet[$i]).'</loc>';
					echo '<lastmod>'.date('Y-m-d').'</lastmod>';
					echo '</sitemap>';
				}

				echo '</sitemapindex>';
			}elseif($ds!=''){

				$this->load->library('solr');
				$filters = array('data_source_key'=>$ds, 'rows'=>50000, 'fl'=>'key, id, update_timestamp, slug');
				$this->solr->setFilters($filters);
				$this->solr->executeSearch();
				$res = $this->solr->getResult();

		    	$keys = $res->{'docs'};
				$freq = 'weekly';
				if($this->is_active($ds)){
					$freq = 'daily';
				}

				header("Content-Type: text/xml");
				echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
				foreach($keys as $k) {
					//var_dump($k);
					echo '<url>';
					if ($k->{'slug'}){
						echo '<loc>'.base_url().$k->{'slug'}.'/'.$k->{'id'}.'</loc>';
					} else {
						echo '<loc>'.base_url().'view/?key='.urlencode($k->{'key'}).'</loc>';
					}
					echo '<changefreq>'.$freq.'</changefreq>';
					echo '<lastmod>'.date('Y-m-d', strtotime($k->{'update_timestamp'})).'</lastmod>';
					echo '</url>';
				}
				echo '</urlset>';
			}
    	}
    	
	}

	public function is_active($ds_key){
		$this->load->library('solr');
		$filters = array('data_source_key'=>$ds_key);
		$this->solr->setFilters($filters);
		$this->solr->setFacetOpt('query', 'record_created_timestamp:[NOW-1MONTH/MONTH TO NOW]');
		$this->solr->executeSearch();
		$facet = $this->solr->getFacet();
		$result = $this->solr->getNumFound();
		if($facet){
			if($facet->{'facet_queries'}->{'record_created_timestamp:[NOW-1MONTH/MONTH TO NOW]'} > 0){
				return true;
			}else return false;
		}else return false;
	}

	public function send(){
		$this->load->library('user_agent');
		$data['user_agent']=$this->agent->browser();
		$name = $this->input->post('name');
		$email = $this->input->post('email');
		$content = $this->input->post('content');

		$this->load->library('email');

		$this->email->from($email, $name);
		$this->email->to($this->config->item('site_admin_email'));
		$this->email->subject('RDA Contact Us');
		$this->email->message($content);

		$this->email->send();

		echo '<p> </p><p>Thank you for your response. Your message has been delivered successfully</p><p> </p><p> </p><p> </p><p> </p><p> </p><p> </p><p> </p>';
	}

	public function requestGrantEmail(){
		$this->load->library('user_agent');
		$data['user_agent']=$this->agent->browser();
		$name = $this->input->post('contact-name');
		$email = $this->input->post('contact-email');
		$content = 'Grant ID: '.$this->input->post('grant-id').NL;
		$content .= 'Grant Title: '.$this->input->post('grant-title').NL;	
		$content .= 'Institution: '.$this->input->post('institution').NL;
		$content .= 'purl: ('.$this->input->post('purl').')'.NL.NL;
		$content .= 'Reported by: '.$this->input->post('contact-name').NL;
		$content .= 'From: '.$this->input->post('contact-company').NL;
		$content .= 'Contact email: '.$this->input->post('contact-email').NL;
		
		$this->load->library('email');

		$this->email->from($email, $name);
		$this->email->to($this->config->item('site_admin_email'));
		$this->email->subject('Missing RDA Grant Record '.$this->input->post('grant-id'));
		$this->email->message($content);

		$this->email->send();

		echo '<p> </p><p>Thank you for your enquiry into grant `'.$this->input->post('grant-id').'`. A ticket has been logged with the ANDS Services Team. You will be notified when the grant becomes available in Research Data Australia. </p>';
	}

	public function myobfiscate($emailaddress){
 		$email= $emailaddress; 
 		$obfuscatedEmail = '';               
 		$length = strlen($email);                         
 		for ($i = 0; $i < $length; $i++){                
			$obfuscatedEmail .= "&#" . ord($email[$i]).";";
 		}
 		return $obfuscatedEmail;
	}
}