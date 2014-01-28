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
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Sofortvorkassesuccess.php 3844 2012-04-18 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Block_Sofortvorkassesuccess extends Mage_Checkout_Block_Onepage_Success
{
	private $params;
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('pnsofortueberweisung/sofortvorkassesuccess.phtml');
		$this->params = $this->getRequest()->getParams();
	}
	
	public function getAmount() {
		return number_format($this->params['amount'], 2, ',', '').' EUR';
	}

	public function getHolder() {
		return $this->params['holder'];
	}

	public function getAccountNumber() {
		return $this->params['account_number'];
	}
	
	public function getBankCode() {
		return $this->params['bank_code'];
	}

	public function getBic() {
		return $this->params['bic'];
	}
	
	public function getIban() {
		return $this->params['iban'];
	}
	
	public function getReason1() {
		return $this->params['reason_1'];
	}

	public function getReason2() {
		return $this->params['reason_2'];
	}
	
}
