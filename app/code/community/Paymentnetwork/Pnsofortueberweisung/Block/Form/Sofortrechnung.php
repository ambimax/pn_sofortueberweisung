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
 * @version	$Id: Sofortrechnung.php 3844 2012-04-18 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Block_Form_Sofortrechnung extends Mage_Payment_Block_Form
{
	/**
	 * Init default template for block
	 */
	protected function _construct()
	{
		$this->setTemplate('pnsofortueberweisung/form/sofortrechnung.phtml');

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
	
	public function getImageUrl() 
	{
		return Mage::getStoreConfig('payment/pnsofort/logourl').'de/sr/'.Mage::getStoreConfig('payment/sofort/logo').'.png';
	}
	
	public function isDisplayText() 
	{
		return  substr(Mage::getStoreConfig('payment/sofort/logo'), 0, 4) == 'logo';
	}	
}