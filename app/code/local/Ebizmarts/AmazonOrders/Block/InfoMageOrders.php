<?php
/*
 * Created on Jul 7, 2008
 *
 */

class Ebizmarts_AmazonOrders_Block_InfoMageOrders extends Mage_Adminhtml_Block_Sales_Order_View_Info
{
    public function getOrderStoreName()
    {
    	// Overloading this method to show the Amazon id of the order instead of the name of the Magento store

        if ($this->getOrder()) {
				
				$name = array();
			  //$amazon_resource = Mage::getResourceModel('sales/order')->load($this->getOrder()->getId());
				if($this->getOrder()->getAmazonOrderId()){
					$name = array(
						Mage::helper('amazonOrders')->__('Imported from Amazon'),
						"#" . $this->getOrder()->getAmazonOrderId()
					);
				}

        	if (count($name)) {
            	return implode('<br/>', $name);
        	} else {
        		return parent::getOrderStoreName();
        	}
        }
        return null;
    }

}


?>
