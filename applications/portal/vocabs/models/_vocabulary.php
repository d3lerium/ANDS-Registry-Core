<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Basse Vocabulary model for a single vocabulary object
 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
 */
class _vocabulary {

	//object properties are all located in the same array
	public $prop;

	function __construct($id = false) {
		//populate the property as soon as the object is constructed
		$this->init();
		if ($id) {
			$this->populate_from_db($id);
		}
	}

	/**
	 * Initialize a registry object
	 * @todo
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 * @return void
	 */
	function init() {
		//nothing here
	}

	/**
	 * Returns a flat array of indexable fields
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 * @return array 
	 */
	public function indexable_json() {
		$this->populate_from_db($this->prop['id']);
		$json = array();

		//index single values
		$single_values = array('id', 'title', 'slug', 'licence', 'pool_party_id');
		foreach ($single_values as $s) {
			$json[$s] = $this->prop[$s];
		}

		if ($this->prop['data']) {
			$data = json_decode($this->prop['data'], true);

			if (isset($data['description'])) {
				$json['description'] = $data['description'];
			}

			if (isset($data['subjects'])) {
				$json['subjects'] = array();
				foreach($data['subjects'] as $subject) {
					$json['subjects'][] = $subject['subject'];
				}
			}
			if (isset($data['top_concept'])) {
				$json['top_concept'] = array();
				if (is_array($data['top_concept'])) {
					foreach($data['top_concept'] as $s) {
						$json['top_concept'][] = $s;
					}
				} else {
					$json['top_concept'] = $data['top_concept'];
				}
			}

			if (isset($data['language'])) {
				$json['language'] = array();
				if (is_array($data['language'])) {
					foreach($data['language'] as $s) {
						$json['language'][] = $s;
					}
				} else {
					$json['language'][] = $data['language'];
				}
				
			}
		}

		return $json;
	}

	/**
	 * Return the current object as a displayable array, with the data attribute break apart
	 * into PHP array
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 * @return array 
	 */
	public function display_array() {
		$result = json_decode(json_encode($this->prop), true);
		if ($this->data) {
			//dirty hack to convert json into multi dimensional array from an object
			$ex = json_decode(json_encode(json_decode($this->data)), true);
			foreach($ex as $key=>$value) {
				if (!isset($result[$key])) $result[$key] = $value;
			}
			unset($result['data']);
		}
		return $result;
	}


	/**
	 * Populate the prop array with an array of key=>value pair
	 * @param  array  $values $key=>$value pair
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 * @return void
	 */
	public function populate($values = array()) {
		foreach ($values as $key=>$value) {
			$this->prop[$key] = $value;
		}
	}

	/**
	 * Populate the _vocabulary props by extracting the data from DB
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 * @param  int $id 
	 * @return void     
	 */
	public function populate_from_db($id) {
		$ci =& get_instance();
		$db = $ci->load->database('vocabs', true);
		if (!$db) throw new Exception('Unable to connect to database');
		if (!$id) throw new Exception('ID required');

		$query = $db->get_where('vocabularies', array('id'=>$id));
		$data = $query->first_row();
		$this->populate($data);

		//replace the versions with the one from the database
		$this->prop['versions'] = array();
		$query = $db->get_where('versions', array('vocab_id'=>$id));
		if ($query && $query->num_rows() > 0) {
			foreach($query->result_array() as $row) {
				$version = $row;

				//break apart version data
				if (isset($version['data'])) {
					$version_data = json_decode($version['data'], true);
					foreach($version_data as $key=>$value) {
						if (!isset($version[$key])) {
							$version[$key] = $value;
						}
					}
					unset($version['data']);
				}

				$this->prop['versions'][] = $version;
			}
		}
	}

	/**
	 * Saving / Adding Vocabulary
	 * Requires the vocabs database connection group to be present
	 * $data is extracted for values to be put into the database and the
	 * rest is encoded within the data field
	 * If an ID is present in the _vocabulary, an update is issued
	 * If there is no ID, this is a new vocabulary and it will be added
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 * @param  $data 
	 * @return boolean
	 */
	public function save($data = false) {
		$ci =& get_instance();
		$db = $ci->load->database('vocabs', true);
		if (!$db) throw new Exception('Unable to connect to database');

		if ($this->id) {
			//update
			if ($data) {
				$saved_data = array(
					'title' => $data['title'],
					'licence' => isset($data['licence']) ? $data['licence'] : false,
					'description' => isset($data['description']) ? $data['description'] : false,
					'pool_party_id' => isset($data['pool_party_id']) ? $data['pool_party_id'] : false,
					'modified_date' => date("Y-m-d H:i:s"),
					'data' => json_encode($data)
				);
				$db->where('id', $data['id']);
				$result = $db->update('vocabularies', $saved_data);

				//deal with versions
				$this->updateVersions($data, $db);

				if ($result) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			//add new
			
			//check if there's an existing vocab with the same slug
			$slug = url_title($this->prop['title'], '-', TRUE);
			$result = $db->get_where('vocabularies', array('slug'=>$slug));
			if ($result->num_rows() > 0) {
				return false;
			}

			$data = array(
				'title' => $this->prop['title'],
				'slug' => $slug,
				'description' => isset($this->prop['description']) ? $this->prop['description'] : '',
				'licence' => isset($this->prop['licence']) ? $this->prop['licence'] : '',
				'pool_party_id' => isset($this->prop['pool_party_id']) ? $this->prop['pool_party_id'] : '',
				'created_date'=> date("Y-m-d H:i:s"),
				'modified_date' => date("Y-m-d H:i:s"),
				'data' => json_encode($this->prop)
			);
    		$result = $db->insert('vocabularies', $data);
    		$new_id = $db->insert_id();

    		//deal with versions
			$this->updateVersions($data, $db);

    		if ($result && $new_id) {
    			$new_vocab = new _vocabulary($new_id);
    			return $new_vocab;
    		} else {
    			return false;
    		}
		}
	}

	/**
	 * Update the versions table according to the data received
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 * @access private
	 * @param  data $data 
	 * @param  db_obj $db   
	 * @return void
	 */
	private function updateVersions($data, $db) {

		//pre-update the object to make sure the versions are current
		$this->populate_from_db($this->prop['id']);

		//deleting the versions that is not in the income feed and not blank
		$existing = array();
		foreach($this->versions as $version) {
			$existing[] = $version['id'];
		}
		$incoming = array();
		foreach($data['versions'] as $version) {
			if (isset($version['id']) && $version['id']!="") {
				$incoming[] = $version['id'];
			}
		}
		$deleted = array_diff($existing, $incoming);
		foreach($deleted as $id) {
			$db->delete('versions', array('id'=>$id));
		}

		foreach($data['versions'] as $version) {
			if (isset($version['id']) && $version['id']!="") {
				//update the existing version
				$saved_data = array(
					'title' => $version['title'],
					'status' => $version['status'],
					'release_date' => date('Y-m-d H:i:s',strtotime($version['release_date'])),
					'vocab_id' => $this->prop['id'],
					'repository_id' => '',
					'data' => json_encode($version)
				);
				$db->where('id', $version['id']);
				$result = $db->update('versions', $saved_data);
				if (!$result) throw new Exception($db->_error_message());
			} else {
				//add the version if it doesn't exist
				$version_data = array(
					'title' => $version['title'],
					'status' => $version['status'],
					'release_date' => date('Y-m-d H:i:s',strtotime($version['release_date'])),
					'vocab_id' => $this->prop['id'],
					'repository_id' => '',
					'data' => json_encode($version)
				);
				$result = $db->insert('versions', $version_data);
				if (!$result) throw new Exception($db->_error_message());
			}
		}

		//update the object
		$this->populate_from_db($this->prop['id']);
	}

	/**
	 * Magic function to get an attribute, returns property within the $prop array
	 * @param  string $property property name
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 * @return property result           
	 */
	public function __get($property) {
		if(isset($this->prop[$property])) {
			return $this->prop[$property];
		} else return false;
	}

	/**
	 * Magic function to set an attribute
	 * @param string $property property name
	 * @param string $value    property value
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 */
	public function __set($property, $value) {
		$this->prop[$property] = $value;
	}

	/**
	 * Magic function to return the object as a JSON encoded string
	 * @author  Minh Duc Nguyen <minh.nguyen@ands.org.au>
	 * @return string
	 */
	public function __toString() {
		return json_encode($this->prop);
	}
}