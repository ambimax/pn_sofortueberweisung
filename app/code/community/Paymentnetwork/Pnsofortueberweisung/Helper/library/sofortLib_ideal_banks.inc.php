<?php
require_once dirname(__FILE__).'/sofortLib_abstract.inc.php';

/**
 * This class encapsulates retrieval of listed banks of the Netherlands
 *
 * Copyright (c) 2012 Payment Network AG
 *
 * $Date: 2012-05-21 16:53:26 +0200 (Mo, 21. Mai 2012) $
 * @version SofortLib 1.5.0  $Id: sofortLib_ideal_banks.inc.php 4191 2012-05-21 14:53:26Z niehoff $
 * @author Payment Network AG http://www.payment-network.com (integration@sofort.com)
 *
 */
class SofortLib_iDeal_Banks extends SofortLib_Abstract {
	
	protected $_xmlRootTag = 'ideal';
	
	protected $_parameters = array();
	
	protected $_response = array();
	
	private $banks = array();
	
	
	public function __construct($configKey, $apiUrl = '') {
		list ($userId, $projectId, $apiKey) = explode(':', $configKey);
		parent::__construct($userId, $apiKey, $apiUrl.'/banks');
	}
	
	
	public function getBanks() {
		return $this->_banks;
	}
	
	
	protected function _parseXml() {
		if (isset($this->_response['ideal']['banks']['bank'][0]['code']['@data'])) {
			foreach($this->_response['ideal']['banks']['bank'] as $key => $bank) {
				$this->_banks[$key]['code'] = $bank['code']['@data'];
				$this->_banks[$key]['name'] = $bank['name']['@data'];
			}
		}
	}
}
?>