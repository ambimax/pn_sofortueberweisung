<?php
/// \cond
/**
 * interface for Payment Network XML-API
 *
 * this class implements basic http authentication and a xml-parser
 * for parsing response messages
 *
 * requires libcurl and openssl
 *
 * Copyright (c) 2012 Payment Network AG
 *
 * $Date: 2012-05-25 18:08:21 +0200 (Fr, 25. Mai 2012) $
 * @version SofortLib 1.5.0  $Id: sofortLib_abstract.inc.php 4278 2012-05-25 16:08:21Z boehm $
 * @author Payment Network AG http://www.payment-network.com (integration@sofort.com)
 * @internal
 *
 */
class SofortLib_Abstract extends SofortLib {
	
	protected $_validateOnly = false;
	
	protected $_apiVersion = '1.0';
	
	
	/**
	 * Override this callback to set the response in the right context
	 *
	 * @protected
	 */
	protected function _parseXml() {
		trigger_error('Missing implementation of parseXml()', E_USER_NOTICE);
	}
	
	
	/**
	 * send this message and get response
	 * save all warnings - errors are only saved if no payment-url is send from pnag
	 *
	 * @return SofortLib_TransactionData $this
	 */
	public function sendRequest() {
		$requestData[$this->_xmlRootTag] = $this->_parameters;
		$requestData = $this->_prepareRootTag($requestData);
		$xmlRequest = ArrayToXml::render($requestData);
		$this->_log($xmlRequest, ' XmlRequest -> ');
		$xmlResponse = $this->_sendMessage($xmlRequest);
		$this->_response = XmlToArray::render($xmlResponse);
		$this->_log($xmlResponse, ' XmlResponse <- ');
		$this->_handleErrors();
		$this->_parseXml();
		return $this;
	}
	
	
	protected function _log($xml, $message) {
		$this->enableLog(); //set enable to aktivate following lines
		$this->log(get_class($this).$message.$xml);
	}
	
	
	private function _prepareRootTag($requestData) {
		if ($this->_apiVersion) {
			$requestData[$this->_xmlRootTag]['@attributes']['version'] = $this->_apiVersion;
		}
		
		if ($this->_validateOnly) {
			$requestData[$this->_xmlRootTag]['@attributes']['validate_only'] = 'yes';
		}
		
		return $requestData;
	}
}
/// \endcond
?>