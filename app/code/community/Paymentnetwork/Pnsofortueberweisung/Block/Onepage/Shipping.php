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
 * @author Markus Schneider <markus.schneider[at]initos.com>
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Shipping.php 3844 2012-06-06 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Block_Onepage_Shipping extends Mage_Checkout_Block_Onepage_Shipping {
    
    
    /**
     * Return Sales Quote Address model (shipping address)
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getAddress()
    {       
        if (is_null($this->_address)) {
            $this->_address = $this->getQuote()->getShippingAddress();
        }
        if (is_null($this->_address)) {
            $this->_address = Mage::getModel('sales/quote_address');
        }

        return $this->_address;
    }
}