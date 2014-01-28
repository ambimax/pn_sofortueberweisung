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
 * @version	$Id: Edit.php 3844 2012-04-18 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Block_Adminhtml_Pnsofortueberweisung_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
	{
		parent::__construct();
				 
		$this->_objectId = 'id';
		$this->_blockGroup = 'pnsofortueberweisung';
		$this->_controller = 'adminhtml_pnsofortueberweisung';
		
		$this->_updateButton('save', 'label', Mage::helper('pnsofortueberweisung')->__('Save Item'));
		$this->_updateButton('delete', 'label', Mage::helper('pnsofortueberweisung')->__('Delete Item'));
		
		$this->_addButton('saveandcontinue', array(
			'label'	 => Mage::helper('adminhtml')->__('Save And Continue Edit'),
			'onclick'   => 'saveAndContinueEdit()',
			'class'	 => 'save',
		), -100);

		$this->_formScripts[] = "
			function toggleEditor() {
				if (tinyMCE.getInstanceById('pnsofortueberweisung_content') == null) {
					tinyMCE.execCommand('mceAddControl', false, 'pnsofortueberweisung_content');
				} else {
					tinyMCE.execCommand('mceRemoveControl', false, 'pnsofortueberweisung_content');
				}
			}

			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
		";
	}

	public function getHeaderText()
	{
		if( Mage::registry('pnsofortueberweisung_data') && Mage::registry('pnsofortueberweisung_data')->getId() ) {
			return Mage::helper('pnsofortueberweisung')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('pnsofortueberweisung_data')->getTitle()));
		} else {
			return Mage::helper('pnsofortueberweisung')->__('Add Item');
		}
	}
}