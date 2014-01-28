<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Paymentnetwork
 * @package	Paymentnetwork_Sofortueberweisung
 * @copyright  Copyright (c) 2008 [m]zentrale GbR, 2010 Payment Network AG
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Linkpnso.php 3855 2012-04-18 09:04:22Z dehn $
 */
  
class Varien_Data_Form_Element_Linkpnso extends Varien_Data_Form_Element_Abstract {
	public function __construct($attributes=array()) {
		parent::__construct($attributes);
		$this->setType('label');
	}

	public function getElementHtml() {
		$params = $this->getParams()->toArray();
		
		foreach($params AS $key => $val){
			switch($key){
				case 'backlink':
					$backurl = Mage::getSingleton('adminhtml/url')->getUrl($val);
					$params[$key] = $backurl;
				break;
				case 'projectssetting_interface_success_link':
					$params[$key] = $this->getConfigDataWeb('base_url').$val;
				break;
				case 'projectsnotification_http_url':
					$params[$key] = $this->getConfigDataWeb('base_url').$val;
				break;
				case 'projectssetting_interface_cancel_link':
					$params[$key] = $this->getConfigDataWeb('base_url').$val;
				break;
				case 'projectssetting_interface_timeout_link':
					$params[$key] = $this->getConfigDataWeb('base_url').$val;
				break;
				case 'projectssetting_project_password':
					$params[$key] = $this->getRandomPassword();
					//store pwd in session so we can save it later
					Mage::getSingleton('adminhtml/session')->setData('projectssetting_project_password', $params[$key]);
				break;
				case 'project_notification_password':
					$params[$key] = $this->getRandomPassword();
					Mage::getSingleton('adminhtml/session')->setData('project_notification_password', $params[$key]); 
				break;
				case 'projectsnotification_email_email':
					$params[$key] = Mage::getStoreConfig('trans_email/ident_general/email');
				break;
				case 'project_name':
					$params[$key] = Mage::getStoreConfig('general/store_information/name');
				break;
				default:
					$params[$key] = $val;
				break;		
			}
		}
		
		$queryString = http_build_query($params);
		$html = $this->getBold() ? '<strong>' : '';
		$html.= sprintf($this->getValue(),$this->getConfigDataPayment('urlCreateNew').'?'.$queryString);
		$html.= $this->getBold() ? '</strong>' : '';
		$html.= $this->getAfterElementHtml();
		return $html;
	}
	
	
	public function getRandomPassword($length = 32) {
		$password = '';
		
		//we generate about 5-34 random characters [A-Za-z0-9] in every loop
		do {
			$randomBytes = '';
			$strong = false;
			if(function_exists('openssl_random_pseudo_bytes')) { //php >= 5.3
				$randomBytes = openssl_random_pseudo_bytes(32, $strong);//get 256bit
			}
			if(!$strong) { //fallback
				$randomBytes = pack('I*', mt_rand()); //get 32bit (pseudo-random) 
			}
			
			//convert bytes to base64 and remove special chars
			$password .= preg_replace('#[^A-Za-z0-9]#', '', base64_encode($randomBytes));
		} while (strlen($password) < $length);
		
		return substr($password, 0, $length);
	}
	
	
	public function getConfigDataPayment($field, $storeId = null) {
		
		if (null === $storeId) {
			$storeId = $this->getStore();
		}
		
		$path = 'payment/pnsofortueberweisung/'.$field;
		return Mage::getStoreConfig($path, $storeId);
	}
	
	
	public function getConfigDataWeb($field, $storeId = null) {
		
		if (null === $storeId) {
			$storeId = $this->getStore();
		}
		
		$path = 'web/unsecure/'.$field;
		return Mage::getStoreConfig($path, $storeId);
	}
	
	
	public function getParams() {
		$_types = Mage::getConfig()->getNode('global/params_pnso/types')->asArray();
		$params = Mage::getModel('pnsofortueberweisung/params');
		
		foreach ($_types as $data) {
			$params->setData($data["param"],$data["value"]);
		}
		
		return $params;
	}
}