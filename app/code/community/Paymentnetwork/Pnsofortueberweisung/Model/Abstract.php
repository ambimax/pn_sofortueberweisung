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
 * @copyright  Copyright (c) 2011 Payment Network AG
 * @author Payment Network AG http://www.payment-network.com (integration@payment-network.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Abstract.php 4972 2012-08-01 09:05:46Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib_sofortueberweisung_classic.php';

class Paymentnetwork_Pnsofortueberweisung_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
	const MODULE_VERSION = 'pn_mag_2.2.1';
	
	/**
	* Availability options
	*/
	protected $_code = 'Abstract';   
	protected $_paymentMethod = 'pnsofortueberweisung';
	
	protected $_formBlockType = 'pnsofortueberweisung/form_pnsofortueberweisung';
	protected $_infoBlockType = 'pnsofortueberweisung/info_pnsofortueberweisung';
	
	protected $_isGateway			   = false;
	protected $_canAuthorize			= true;
	protected $_canCapture			  = true;
	protected $_canCapturePartial	   = false;
	protected $_canRefund			   = false; //we don't supply a method for refunding
	protected $_canVoid				 = false;
	protected $_canUseInternal		  = false;
	protected $_canUseCheckout		  = true;
	protected $_canUseForMultishipping  = true;	
	

	
	public function _construct()
	{
		parent::_construct();
		$this->_init('pnsofortueberweisung/pnsofortueberweisung');
	}
	
	public function isInitializeNeeded()
	{
		return true;
	}
	
	/**
	 * will be executed instead of authorize() 
	 */
	public function initialize($paymentAction, $stateObject)
	{
		$stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
		$stateObject->setStatus(Mage::getSingleton('sales/order_config')->getStateDefaultStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT));
		$stateObject->setIsNotified(false);
	}	   
	
	public function getUrl(){
		return $this->getConfigData('url');
	}
	
	 /**
	 * Return redirect block type
	 *
	 * @return string
	 */
	public function getRedirectBlockType()
	{
		return $this->_redirectBlockType;
	}

	/**
	 * Return payment method type string
	 *
	 * @return string
	 */
	public function getPaymentMethodType()
	{
		return $this->_paymentMethod;
	}


   /**
	* Get redirect URL
	*
	* @return Mage_Payment_Helper_Data
	*/
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('pnsofortueberweisung/pnsofortueberweisung/redirect');
	}
	
	public function assignData($data)
	{
	   	if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}
		$info = $this->getInfoInstance();
				  
		return $this;
	}
	
	public function getSecurityKey(){
		return SofortLib_SofortueberweisungClassic::generatePassword();
	}
	
	public function validate()
	{
		parent::validate();

	
		return $this;
	}
	
	public function getFormFields()
	{
		$amount		= number_format($this->getOrder()->getGrandTotal(),2,'.','');
		$billing	= $this->getOrder()->getBillingAddress();
		$security 	= $this->getSecurityKey();
		
		$this->getOrder()->getPayment()->setSuSecurity($security)->save();
		return array();
	}
	
	/**
	 * Get quote
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function getQuote()
	{
		if (empty($this->_quote)) {			
			$this->_quote = $this->getCheckout()->getQuote();
		}
		return $this->_quote;
	}
	
	/**
	 * Get checkout
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function getCheckout()
	{
		if (empty($this->_checkout)) {
			$this->_checkout = Mage::getSingleton('checkout/session');
		}
		return $this->_checkout;
	}	
	
	/**
	 * Get order model
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder()
	{
		if (!$this->_order) {
			$paymentInfo = $this->getInfoInstance();
			$this->_order = Mage::getModel('sales/order')
							->loadByIncrementId($paymentInfo->getOrder()->getRealOrderId());
		}
		return $this->_order;
	}

	public function getTitle() {
		return Mage::helper('pnsofortueberweisung')->__($this->getConfigData('title'));
	}
	
	
	/**
	 * Retrieve information from payment configuration
	 *
	 * @param   string $field
	 * @return  mixed
	 */
	public function getConfigData($field, $storeId = null)
	{
		if (null === $storeId) {
			$storeId = $this->getStore();
		}

		if($field == 'active' || $field == 'sort_order')
			$field = $this->getCode().'_'.$field;
		elseif($field == 'title' || $field == 'payment_action')
			return Mage::getStoreConfig('payment/'.$this->getCode().'/'.$field, $storeId);


		$path = 'payment/sofort/'.$field;
		return Mage::getStoreConfig($path, $storeId);
	}  
	
	/**
	 * check if a prefix is male
	 * 
	 * @param string $prefix
	 */
	private function _isMale($prefix) {
	    $prefix = trim($prefix, " .,;");
	    $prefix = strtolower($prefix);
	    
	    $knowPrefix = array('mr', 'mister', 'herr', 'herrn', 'sahib', 'senor', 'sidi', 'sir');
	    
	    if(in_array($prefix, $knowPrefix)) {
	        return true;
	    }
	    return false;
	}
	
	/**
	 * check if a prefix is male
	 * 
	 * @param string $prefix
	 */
	private function _isFemale($prefix) {
	    $prefix = trim($prefix, " .,;");
	    $prefix = strtolower($prefix);
	    
	    $knowPrefix = array('mrs', 'miss', 'ms', 'dona', 'fraeulein', 'fräulein', 'frau', 'lady', 'madam', 'madame', 'senora', 'senorita');
	    
	    if(in_array($prefix, $knowPrefix)) {
	        return true;
	    }
	    return false;
	}
	
	/**
	 * returns salutation decoded
	 * 
	 * 2 = MR
	 * 3 = MRS
	 * 
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
    protected function _getSalutation($address) {
		
		// try if prefix given
		$prefix = $address->getPrefix();
		if(!empty($prefix)) {
		    // check for male prefix
		    if($this->_isMale($prefix)){
		        return 2;
		    }
		    // check for female prefix
		    if($this->_isFemale($prefix)) {
		        return 3;
		    }
		}	
			
		return '';
	}
	
	/**
	 * returns firstname
	 * 
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
	protected function _getFirstname($address) {
	    return $address->getFirstname();
	}
	
	/**
	 * returns lastname
	 * 
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
	protected function _getLastname($address) {
		
		return $address->getLastname();
	}
	
	/**
	 * returns NameAdditive, this means company name if exist
	 * 
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
	protected function _getNameAdditive($address) {
	    return ($address->getCompany()) ? $address->getCompany() : '';
	}
	
	/**
	 * returns street
	 * 
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
	protected function _getStreet($address) {
		$street = trim(str_replace("\n", " ", $address->getStreetFull()));
		$matched = preg_match('#(.+)[ \.,](.+)#i', trim($street), $matches);
		if($matched && !empty($matches[1]) && !empty($matches[2]))
			return $matches[1];
		
		return $street;		
	}

	/**
	 * returns streetnumber
	 * 
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
	protected function _getNumber($address) {
		$street = trim(str_replace("\n", " ", $address->getStreetFull()));
		$matched = preg_match('#(.+)[ \.,](.+)#i', trim($street), $matches);
		if($matched && !empty($matches[1]) && !empty($matches[2]))
			return $matches[2];
		
		return '';
	}
	
	/**
	 * returns postcode
	 * 
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
	protected function _getPostcode($address) {
	    return $address->getPostcode();
	}
	
	/**
	 * returns city
	 * 
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
	protected function _getCity($address) {
	    return $address->getCity();
	}
	
	/**
	 * returns country
	 * 
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @return string
	 */
	protected function _getCountry($address) {
	    return $address->getCountryId();
	}
}