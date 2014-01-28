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
 * @copyright  Copyright (c) 2012 initOS GmbH & Co. KG, 2012 Payment Network AG
 * @author Markus Schneider <markus.schneider@initos.com>
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Ideal.php 3844 2012-04-18 07:37:02Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib_ideal_classic.php';
class Paymentnetwork_Pnsofortueberweisung_Block_Form_Ideal extends Mage_Payment_Block_Form
{
    /**
     * store array with bank codes
     * 
     * @var array
     */
    private $_relatedBanks = null;
    
	/**
	 * Init default template for block
	 */
	protected function _construct()
	{
		$this->setTemplate('pnsofortueberweisung/form/ideal.phtml');
		// replace title with image
		$this->setMethodTitle('');
		$this->setMethodLabelAfterHtml('<img src="'.Mage::helper('pnsofortueberweisung')->__('https://images.sofort.com/en/ideal/logo_155x50.png').'">');
		return parent::_construct();		
	}  
	
	/**
	 * Retrieve payment configuration object
	 *
	 * @return Mage_Payment_Model_Config
	 */
	protected function _getConfig()
	{
		return Mage::getSingleton('payment/config');
	}
	
	/**
	 * return banks with code
	 * 
	 * @return array
	 */
	public function getBanks() {
	    if(empty($this->_relatedBanks)){
    	    $sofort = new SofortLib_iDealClassic(Mage::getStoreConfig('payment/sofort_ideal/configkey'),Mage::getStoreConfig('payment/sofort_ideal/password'), 'sha1');
    	    $this->_relatedBanks =  $sofort->getRelatedBanks();
	    }
	    return $this->_relatedBanks;
	}
	
	/**
	 * get current account holder
	 * 
	 * @return string
	 */
	public function getAccountHolder() {
	    return Mage::getSingleton('core/session')->getIdealHolder();
	}
	
	/**
	 * return current account number
	 * 
	 * @return string
	 */
    public function getAccountNumber() {
	    return Mage::getSingleton('core/session')->getIdealAccountNumber();
	}
	
	/**
	 * return bank code
	 * 
	 * @return int
	 */
    public function getIdealBankCode() {
	    return Mage::getSingleton('core/session')->getIdealBankCode();				  
	}
	
}