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
 * @version	$Id: Pnsofortueberweisung.php 3844 2012-04-18 07:37:02Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';
class Paymentnetwork_Pnsofortueberweisung_Block_Info_Pnsofortueberweisung extends Mage_Payment_Block_Info
{
	private $sofort;
	/**
	 * Init default template for block
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('pnsofortueberweisung/info/sofortueberweisung.phtml');
	  	
	}
	
	 /**
	 * Retrieve info model
	 *
	 * @return Mage_Sofortueberweisung_Model_Info
	 */
	public function getInfo()
	{
		$info = $this->getData('info');
		
		if (!($info instanceof Mage_Payment_Model_Info)) {
			Mage::throwException($this->__('Can not retrieve payment info model object.'));
		}
		return $info;
	}
	
	 /**
	 * Retrieve payment method model
	 *
	 * @return Mage_Payment_Model_Method_Abstract
	 */
	public function getMethod()
	{
		return $this->getInfo()->getMethodInstance();
	}
	
	public function toPdf()
	{
		
		//$pdf = $this->getMethod()->getInfoInstance()->getAdditionalInformation('sofortrechnung_invoice_url');
		//return Zend_Pdf::parse(file_get_contents($pdf));
		$this->setTemplate('pnsofortueberweisung/info/pdf/sofortueberweisung.phtml');
		return $this->toHtml();
	}
	
	public function _toHtml() {
		if($this->getMethod()->getCode() == 'sofortvorkasse')
			$this->setTemplate('pnsofortueberweisung/info/sofortvorkasse.phtml');
		return parent::_toHtml();
	}
	
	public function getAmount() {
		return $this->sofort->getAmount();
	}

	public function getHolder() {
		return $this->sofort->getRecipientHolder();
	}

	public function getAccountNumber() {
		return $this->sofort->getRecipientAccountNumber();
	}
	
	public function getBankCode() {
		return $this->sofort->getRecipientBankCode();
	}

	public function getBic() {
		return $this->sofort->getRecipientBic();
	}
	
	public function getIban() {
		return $this->sofort->getRecipientIban();
	}
	
	public function getReason1() {
		$reason = $this->sofort->getReason();
		return $reason[0];
	}

	public function getReason2() {
		$reason = $this->sofort->getReason();
		return $reason[1];
	}
	
	public function loadData() {
		//$tid = $this->getInfo()->getPnSuTransactionId();
		$tid = $this->getMethod()->getInfoInstance()->getAdditionalInformation('sofort_transaction');
		if($tid) {
			$this->sofort = new SofortLib_TransactionData(Mage::getStoreConfig('payment/sofort/configkey'));
			$this->sofort->setTransaction($tid)->sendRequest();
			return !($this->sofort->isError());
		}
		return false;
	}
   
}