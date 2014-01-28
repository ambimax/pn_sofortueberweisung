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
 * @version	$Id: Discount.php 3844 2012-06-06 07:37:02Z dehn $
 */


class Paymentnetwork_Pnsofortueberweisung_Model_Order_Invoice_Total_Discount extends Mage_Sales_Model_Order_Invoice_Total_Discount
{
    /**
     * collect discout total for invoice
     * 
     * - on payment method "sofortrechnung" discount will invoice on first invoice complete including canceled items
     * 
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return Mage_Sales_Model_Order_Invoice_Total_Discount
     * @see app/code/core/Mage/Sales/Model/Order/Invoice/Total/Mage_Sales_Model_Order_Invoice_Total_Discount::collect()
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        // special case for modified sofortrechnung
        if($invoice->getOrder()->getPayment()->getMethod() == "sofortrechnung"){
            $totalDiscountAmount = - $invoice->getOrder()->getDiscountInvoiced() - $invoice->getOrder()->getDiscountAmount();
            $baseTotalDiscountAmount =  - $invoice->getOrder()->getBaseDiscountInvoiced() - $invoice->getOrder()->getBaseDiscountAmount();
            
            $invoice->setDiscountAmount($totalDiscountAmount);
            $invoice->setBaseDiscountAmount($baseTotalDiscountAmount);
            
            $invoice->setGrandTotal($invoice->getGrandTotal() - $totalDiscountAmount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $baseTotalDiscountAmount);
            return $this;
        }
        
        return parent::collect($invoice);
               
    }
}
