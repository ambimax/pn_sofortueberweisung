<?php
/**
 * This class is for confirming and changing statuses of invoices
 *
 * eg: $confirmObj = new SofortLib_ConfirmSr('yourapikey');
 *
 * $confirmObj->confirmInvoice('1234-456-789654-31321')->sendRequest();
 *
 * Copyright (c) 2012 Payment Network AG
 *
 * $Date: 2012-05-21 16:53:26 +0200 (Mo, 21. Mai 2012) $
 * @version SofortLib 1.5.0  $Id: sofortLib_confirm_sr.inc.php 4191 2012-05-21 14:53:26Z niehoff $
 * @author Payment Network AG http://www.payment-network.com (integration@sofort.com)
 *
 */
class SofortLib_ConfirmSr extends SofortLib_Abstract {
	
	protected $_parameters = array();
	
	protected $_response = array();
	
	protected $_xmlRootTag = 'confirm_sr';
	
	private $_file;
	
	/**
	 * create new confirm object
	 *
	 * @param String $apikey your API-key
	 */
	public function __construct($configKey = '') {
		list($userId, $projectId, $apiKey) = explode(':', $configKey);
		$apiUrl = (getenv('sofortApiUrl') != '') ? getenv('sofortApiUrl') : 'https://api.sofort.com/api/xml';
		parent::__construct($userId, $apiKey, $apiUrl);
	}
	
	
	/**
	 * Set the transaction you want to confirm/change
	 * @param String $arg Transaction Id
	 * @return SofortLib_ConfirmSr
	 */
	public function setTransaction($arg) {
		$this->_parameters['transaction'] = $arg;
		return $this;
	}
	
	
	/**
	 * set a comment for refunds
	 * @param string $arg
	 */
	public function setComment($arg) {
		$this->_parameters['comment'] = $arg;
		return $this;
	}
	
	
	/**
	 * add one item to the cart if you want to change the invoice
	 *
	 * @param string $productNumber product number, EAN code, ISBN number or similar
	 * @param string $title description of this title
	 * @param double $unit_price gross price of one item
	 * @param int $productType product type number see manual
	 * @param string $description additional description of this item
	 * @param int $quantity default 1
	 * @param int $tax tax in percent, default 19
	 */
	public function addItem($itemId, $productNumber, $productType, $title, $description, $quantity, $unitPrice, $tax) {
		$unitPrice = number_format($unitPrice, 2, '.', '');
		$tax = number_format($tax, 2, '.', '');
		$quantity = intval($quantity);
		$this->_parameters['items']['item'][] = array(
			'item_id' => $itemId,
			'product_number' => $productNumber,
			'product_type' => $productType,
			'title' => $title,
			'description' => $description,
			'quantity' => $quantity,
			'unit_price' => $unitPrice,
			'tax' => $tax,
		);
	}
	
	
	// TODO: implement removal of items
	public function removeItem($productId, $quantity = 0) {
		if (!isset($this->_parameters['items']['item'][$productId])) {
			return false;
		} elseif ($quantity = -1) {
			unset($this->_parameters['items']['item'][$productId]);
			return true;
		}
		
		$this->_parameters['items']['item'][$productId]['quantity'] = $quantity;
		return true;
	}
	
	
	function updateCart($cartItems = array()) {
		$i = 0;
		
		foreach ($cartItems as $cartItem) {
			$this->_parameters['items']['item'][$i]['item_id'] = $cartItem['itemId'];
			$this->_parameters['items']['item'][$i]['product_number'] = $cartItem['productNumber'];
			$this->_parameters['items']['item'][$i]['title'] = $cartItem['title'];
			$this->_parameters['items']['item'][$i]['description'] = $cartItem['description'];
			$this->_parameters['items']['item'][$i]['quantity'] = $cartItem['quantity'];
			$this->_parameters['items']['item'][$i]['unit_price'] = number_format($cartItem['unitPrice'], 2, '.', '') ;
			$this->_parameters['items']['item'][$i]['tax'] = $cartItem['tax'];
			$i++;
		}
	}
	
	
	/**
	 * cancel the invoice
	 * @param string $transaction the transaction id
	 * @return SofortLib_ConfirmSr
	 */
	public function cancelInvoice($transaction = '') {
		if (empty($transaction) && array_key_exists('transaction', $this->_parameters)) {
			$transaction = $this->_parameters['transaction'];
		}
		
		if (!empty($transaction)) {
			$this->_parameters = NULL;
			$this->_parameters['transaction'] = $transaction;
			$this->_parameters['items'] = array();
		}
		
		return $this;
	}
	
	
	/**
	 * confirm the invoice
	 * @param string $transaction the transaction id
	 * @return SofortLib_ConfirmSr
	 */
	public function confirmInvoice($transaction = '') {
		if (empty($transaction) && array_key_exists('transaction', $this->_parameters)) {
			$transaction = $this->_parameters['transaction'];
		}
		
		if (!empty($transaction)) {
			$this->_parameters = NULL;
			$this->_parameters['transaction'] = $transaction;
		}
		
		return $this;
	}
	
	
	/**
	 * after you you changed/confirmed an invoice you
	 * can download the new invoice-pdf with this function
	 * @return string url
	 */
	public function getInvoiceUrl() {
		return $this->_file;
	}
	
	
	protected function _parseXml() {
		$this->_file = isset($this->_response['invoice']['download_url']['@data']) ? $this->_response['invoice']['download_url']['@data'] : '';
	}
}
?>