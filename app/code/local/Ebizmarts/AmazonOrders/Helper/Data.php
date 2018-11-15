<?php
/*
 * Created on Jun 26, 2008
 *
 */

class Ebizmarts_AmazonOrders_Helper_Data extends Mage_Core_Helper_Abstract {

public function amazonOrderId($order, $orderID){
	$amazon_resource = Mage::getResourceModel('amazonOrders/amazonOrders_orderDetails_Collection');
        $amazon_data = $amazon_resource->addAttributeToSelect('amazon_order_id')
        	->setOrderFilter($orderID)->load();

        foreach ($amazon_data as $amazon) {
        	return $amazon->getAmazonOrderId();
        	break; // Only the first element
        }

//	return $orderID;
}

}
?>
