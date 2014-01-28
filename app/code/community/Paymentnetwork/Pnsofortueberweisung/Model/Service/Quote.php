<?php
//bugfix for #23935
//http://www.magentocommerce.com/bug-tracking/issue/?issue=9711
class Paymentnetwork_Pnsofortueberweisung_Model_Service_Quote extends Mage_Sales_Model_Service_Quote
{
	public function submitOrder()
	{
		$order = parent::submitOrder();
		// Prevent the cart to be emptied before payment response 
		if($order->getPayment()->getMethodInstance()->getPaymentMethodType() == 'pnsofortueberweisung')
			$this->_quote->setIsActive(true);
		
		return $order;
	}
}