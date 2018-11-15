<?php
/*
 */

class Ebizmarts_AmazonOrders_Model_AmazonOrders extends Varien_Object
{
    const RETURN_STATUS_CREATED = "created";
    const RETURN_STATUS_OMITTED = "omitted";
    const RETURN_STATUS_UPDATED = "updated";
    const RETURN_STATUS_ITEM = "item";

    private $fields = array();
    private $last_amazon_order_id = "";

    private $existent_order_flag = 0;
	private $customer_created_flag = 0;

    public function loadFields($fields)
    {
    	$this->fields = $fields;
    }

    public function getFieldIndex($field)
    {
    	return array_search($field, $this->fields);
    }

    public function getExistentOrderFlag() {
		return $this->existent_order_flag;
    }

    public function getCustomerCreatedFlag() {
		return $this->customer_created_flag;
    }

    public function importOrder($data, $override = false, $store = 0)
    {
    	$amazon_order_id = $data[$this->getFieldIndex("order-id")];

    	if ($_SESSION['last_amazon_order_id'] == $amazon_order_id) {
			// Add the product in the previosly processed order
    		if ($this->checkOrderExists($amazon_order_id)) {
    			$this->addItemToAmazonOrder($amazon_order_id, $data);
    			return Ebizmarts_AmazonOrders_Model_AmazonOrders::RETURN_STATUS_ITEM;
    		} else {
    			Mage::throwException(
		             Mage::helper('amazonOrders')->__('Error processing this amazon order: %s', $amazon_order_id)
		        );
    		}
    	} else {
		
    		// Create or update a new amazon order
			if ($this->checkOrderExists($amazon_order_id)) {
				if (!$override){
					$this->existent_order_flag = 1;
					return Ebizmarts_AmazonOrders_Model_AmazonOrders::RETURN_STATUS_OMITTED;
				}else{
					$_SESSION['last_amazon_order_id'] = $amazon_order_id;

					$this->updateOrder($amazon_order_id, $data, $store);
					$this->existent_order_flag = 1;
					return Ebizmarts_AmazonOrders_Model_AmazonOrders::RETURN_STATUS_UPDATED;
				}
	    	}else{			
	    		$_SESSION['last_amazon_order_id'] = $amazon_order_id;
				$this->createOrder($amazon_order_id, $data, $store);
				return Ebizmarts_AmazonOrders_Model_AmazonOrders::RETURN_STATUS_CREATED;
	    	}

    	}
    }

	public function updateShippingMethod($data) {
		
		//return;
		$amazon_order_id = $data[$this->getFieldIndex("order-id")];

    	$amazon_resource = Mage::getModel('sales/order')->getCollection();
		
    	$amazon_data = $amazon_resource //->addAttributeToSelect('order_id')
    		->addAttributeToFilter('amazon_order_id', $amazon_order_id)->load();

		if(! count($amazon_data)){
			return;
		}
    	$order = Mage::getModel('sales/order');
		foreach ($amazon_data as $amazon) {
    		$order->loadByAttribute("entity_id", $amazon->getId());
    		break; // Only the first element
    	}

		$order_weight = 0;
		$items = $order->getItemsCollection()->getItems();

		foreach( $items as $item ) {
			#cuando guarda el producto, hay que guardar el weight - bundles es dinamico! shit
			$order_weight += ($item->getQtyOrdered() * $item->getWeight());
		}
		$order->setData('weight', $order_weight);

		//$grand_total = (float)$order->getData('grand_total');
		//$shipping_amount = (float)$order->getData('shipping_amount');
		//
    	//$value = $grand_total - $shipping_amount;
		//
		//#shipping method
		//if ( $value < 60 ) {
		//	$order->setShippingDescription('Shipping - Standard');
		//	$order->setShippingMethod('tablerate_bestway');
		//} else {
		//	$order->setShippingDescription('Shipping - Express');
		//	$order->setShippingMethod('tablerate1_bestway');
		//}

		#currency
    	if ($this->getFieldIndex("currency")) {
    		$currency = $data[$this->getFieldIndex("currency")];
    		//$currency = 'GBP';
			$order->setBaseCurrencyCode($currency);
			$order->setStoreCurrencyCode($currency);
    		$order->setOrderCurrencyCode($currency);
    	}

		//#carrier
		//if($order_weight > 2.0) {
    	//	$tracks = 'interlink';
    	//} elseif ($value <= 20.0) {
		//	$tracks = 'royalstandard';
		//} elseif ($value > 20.0 && $value <= 60.0) {
		//	$tracks = 'royalrecorded';
		//} elseif ($value > 60.0) {
		//	$tracks = 'royalspecial';
		//}
		//
        //$carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers(
	    //    Mage::app()->getStore()->getId()
	    //);
		//
        //$code = $tracks;
        //$title = $carrierInstances[$code]->getConfigData('title');
		//
        //$order->setData('carrier_title', $tracks);


//        if (!$order->canShip() && $order->hasShipments()) {
//			try {
//				foreach ($order->getShipmentsCollection() as $shipment) {
//					foreach ($shipment->getAllTracks() as $tracks) {
//						$tracks->isDeleted(true);
//					}
//
//					$tracks->save();
//
//					$track = Mage::getModel('sales/order_shipment_track')
//                	   ->addData(array('title'=>$title, 'carrier_code'=>$code));
//            		$shipment->addTrack($track);
//            		$transactionSave = Mage::getModel('core/resource_transaction')
//			            ->addObject($shipment)
//			            ->addObject($shipment->getOrder())
//			            ->save();
//
//            		break; // only the first
//				}
//
//			} catch (Mage_Core_Exception $e) {
//	            $this->_getSession()->addError($e->getMessage());
//	        }
//	        catch (Exception $e) {
//	            $this->_getSession()->addError($e->getMessage());
//	        }
//
//        } else {
//        	$convertor  = Mage::getModel('sales/convert_order');
//            $shipment    = $convertor->toShipment($order);
//
//
//            foreach ($order->getAllItems() as $orderItem) {
//                if (!$orderItem->getQtyToShip()) {
//                    continue;
//                }
//                $item = $convertor->itemToShipmentItem($orderItem);
//                $qty = $orderItem->getQtyToShip();
//                $item->setQty($qty);
//            	$shipment->addItem($item);
//            }
//
//			$track = Mage::getModel('sales/order_shipment_track')
//                	   ->addData(array('title'=>$title, 'carrier_code'=>$code));
//            $shipment->addTrack($track);
//
//			$shipment->register();
//            $transactionSave = Mage::getModel('core/resource_transaction')
//	            ->addObject($shipment)
//	            ->addObject($shipment->getOrder())
//	            ->save();
// }


        $order->save();
    }

    public function checkOrderExists($amazon_order_id)
    {
    	$details_collection = Mage::getModel('sales/order')->getCollection()->addAttributeToFilter("amazon_order_id", $amazon_order_id);
		return count($details_collection) > 0;
    }

    private function checkCustomersExists($email)
    {
    	$customer = Mage::getModel('customer/customer');
    	if ($customer->getSharingConfig()->isWebsiteScope()) {
            $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        }
    	$customer->loadByEmail($email);
    	return ($customer->getId());
    }

    private function getCustomerByEmail($email)
    {
    	$customer = Mage::getModel('customer/customer');
    	if ($customer->getSharingConfig()->isWebsiteScope()) {
            $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        }
    	$customer->loadByEmail($email);
    	return $customer;
    }

    private function copyToOrder($data, $order)
    {
    	$email = $data[$this->getFieldIndex("buyer-email")];
	//$email = "info@ishopnutrition.com";
    	if (!$this->checkCustomersExists($email)) {
    		$this->createCustomer($data);
    	}
    	$customer = $this->getCustomerByEmail($email);

		$order->setCustomerId($customer->getId());
		$order->setCustomerGroupId($customer->getGroupId());

		//order date #2008-07-01T12:05:56+00:00 ó 28/08/2007 09:49:45
		$orderdate = $data[$this->getFieldIndex("purchase-date")];
		if( strstr($orderdate, "T")) {
			$orderdate = str_replace("T", " ", substr($orderdate, 0, 19));
		} else {
			$orderdate = explode(" ", $orderdate);
			$day = explode("/", $orderdate[0]);
			$orderdate = $day[2].'-'.$day[1].'-'.$day[0].' '.$orderdate[1];
		}
		$order->setCreatedAt($orderdate);

		$shipping_address = Mage::getModel('sales/order_address');
    	$billing_address = Mage::getModel('sales/order_address');

		$shipping_address->setCustomerId($customer->getId());
		$shipping_address->setCustomerAddressId($this->getPrimaryAddressId($customer));

		$billing_address->setCustomerId($customer->getId());
		$billing_address->setCustomerAddressId($this->getPrimaryAddressId($customer));

		$customer->setDefaultBilling($this->getPrimaryAddressId($customer));
		$customer->save();

    	$remove_words = array("mrs ", "mr ", "miss ", "doc ", "md ");

    	// SHIPPING

    	$shipping_address->setOrder($order);

    	$buyer_tmp = $data[$this->getFieldIndex("buyer-name")];

    	$shipping_tmp = $data[$this->getFieldIndex("recipient-name")];

    	foreach ($remove_words as $word) {
    		$buyer_tmp = str_ireplace($word, "", $buyer_tmp);
    		$shipping_tmp = str_ireplace($word, "", $shipping_tmp);
		}
    	$i = stripos($buyer_tmp, " ");

    	$h = stripos($shipping_tmp, " ");

    	$shipping_name = substr($shipping_tmp, 0, $h);
    	$shipping_surname = substr($shipping_tmp, $h+1);

    	$billing_name = substr($buyer_tmp, 0, $i);
    	$billing_surname = substr($buyer_tmp, $i+1);

    	$shipping_address->setFirstname($shipping_name);
    	$shipping_address->setLastname($shipping_surname);

    	$shipping_address->setStreet($data[$this->getFieldIndex("ship-address-1")] . "\n" .
    									$data[$this->getFieldIndex("ship-address-2")]);
    	$shipping_address->setCity($data[$this->getFieldIndex("ship-city")]);
    	$shipping_address->setRegion($data[$this->getFieldIndex("ship-state")]);

    	if ($this->getFieldIndex("ship-zip")) {
    		$zip = $data[$this->getFieldIndex("ship-zip")];
    	} else {
    		$zip = $data[$this->getFieldIndex("ship-postal-code")];
    	}
    	$shipping_address->setPostcode($zip);
    	$shipping_address->setCountryId($data[$this->getFieldIndex("ship-country")]);

		if ($this->getFieldIndex("ship-phone-number") != 0) {
			$shipping_address->setTelephone($data[$this->getFieldIndex("ship-phone-number")]);
		}

    	$shipping_address->setEmail($data[$this->getFieldIndex("buyer-email")]);

    	// BILLING

    	$billing_address->setOrder($order);

    	$billing_address->setFirstname($billing_name);
    	$billing_address->setLastname($billing_surname);
    	$billing_address->setStreet($data[$this->getFieldIndex("ship-address-1")] . "\n" .
    									$data[$this->getFieldIndex("ship-address-2")]);

    	$billing_address->setCity($data[$this->getFieldIndex("ship-city")]);
    	$billing_address->setRegion($data[$this->getFieldIndex("ship-state")]);
    	if ($this->getFieldIndex("ship-zip")) {
    		$zip = $data[$this->getFieldIndex("ship-zip")];
    	} else {
    		$zip = $data[$this->getFieldIndex("ship-postal-code")];
    	}
    	$billing_address->setPostcode($zip);
    	$billing_address->setCountryId($data[$this->getFieldIndex("ship-country")]);

		if ($this->getFieldIndex("buyer-phone-number") != 0) {
			$billing_address->setTelephone($data[$this->getFieldIndex("buyer-phone-number")]);
		}

    	$billing_address->setEmail($data[$this->getFieldIndex("buyer-email")]);

		// ORDER

		$order->setCustomerFirstname($billing_name);
    	$order->setCustomerLastname($billing_surname);
    	$order->setCustomerIsGuest(0);
    	$order->setCustomerEmail($data[$this->getFieldIndex("buyer-email")]);

    	$order->setShippingAddress($shipping_address);
    	$order->setBillingAddress($billing_address);

    	$order->setShippingAddressId($this->getPrimaryAddressId($customer));
    	$order->setBillingAddressId($this->getPrimaryAddressId($customer));

    	if (count($order->getAllStatusHistory())==0) {
    		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
    	}
    	$payment = Mage::getModel('sales/order_payment');
    	$payment->setMethod("checkmo");
    	$order->setPayment($payment);
	 	$order = $this->addItemToOrder($data, $order);
//	    	$order->setStatus('pending');
//		$order->addStatusToHistory('pending');

		$orderStatus = $data[$this->getFieldIndex("order-status")];
                if($orderStatus!=""){
                        $order->setStatus($orderStatus);
                        $order->addStatusToHistory($orderStatus);
                }else{
                        $order->setStatus('amazon-pending');
                        $order->addStatusToHistory('amazon-pending');
                }

		#these fields are used on reports
		$order->setStoreToBaseRate('1.0000');
		$order->setStoreToOrderRate('1.0000');

    	return $order;
    }

    private function createCustomer($data)
    {
    	$remove_words = array("mrs ", "mr ", "miss ", "doc ", "md ");

    	$customer = $this->getCustomerByEmail($data[$this->getFieldIndex("buyer-email")]);
    	$customer->isDeleted(false);
    	$customer->cleanAllAddresses();

    	$buyer_tmp = $data[$this->getFieldIndex("buyer-name")];
    	foreach ($remove_words as $word) {
    		$buyer_tmp = str_ireplace($word, "", $buyer_tmp);
    	}
    	$i = stripos($buyer_tmp, " ");

    	$name = substr($buyer_tmp, 0, $i);
    	$surname = substr($buyer_tmp, $i+1);

    	$customer->setFirstname($name);
    	$customer->setLastname($surname);

    	$customer_address = Mage::getModel('customer/address');

    	$customer_address->setFirstname($name);
    	$customer_address->setLastname($surname);

    	$customer_address->setStreet($data[$this->getFieldIndex("ship-address-1")] . "\n" .
    									$data[$this->getFieldIndex("ship-address-2")]);
    	$customer_address->setCity($data[$this->getFieldIndex("ship-city")]);
    	$customer_address->setRegion($data[$this->getFieldIndex("ship-state")]);
    	if ($this->getFieldIndex("ship-zip")) {
    		$zip = $data[$this->getFieldIndex("ship-zip")];
    	} else {
    		$zip = $data[$this->getFieldIndex("ship-postal-code")];
    	}
    	$customer_address->setPostcode($zip);

    	$customer_address->setTelephone($data[$this->getFieldIndex("buyer-phone-number")]);

    	$customer_address->setCountryId($data[$this->getFieldIndex("ship-country")]);

    	$customer->setEmail($data[$this->getFieldIndex("buyer-email")]);

		$customer->addAddress($customer_address);

		//$customer_address->save();

		if ($customer->getSharingConfig()->isWebsiteScope()) {
            $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        }

    	//$customer->setDefaultBilling($customer_address->getId());

    	$customer->save();
		$this->customer_created_flag = 1;
    }

    public function getPrimaryAddressId($customer)
    {
        $addressId = NULL;
        if ($customer->getDefaultBillingAddress()) {
        	$addressId = $customer->getDefaultBillingAddress()->getId();
        }
        if (!$addressId) {
        	foreach ($customer->getAddresses() as $address) {
                $addressId = $address->getId();
                return $addressId;
            }
        }
        return $addressId;
    }

    private function createOrder($amazon_order_id, $data, $store)
    {

    	$new_order = Mage::getModel('sales/order');

		$new_order->reset();

    	$new_order->setIncrementId(Mage::getSingleton('eav/config')->getEntityType('order')
    										->fetchNewIncrementId($store?$store:(Mage::app()->getStore()->getStoreId())));
       	$new_order = $this->copyToOrder($data, $new_order);

       	$new_order->setStoreId($store?$store:(Mage::app()->getStore()->getId()));
		$new_order->setAmazonOrderId($amazon_order_id);
    	$new_order->save();

    	//$new_order_amazon = Mage::getModel('amazonOrders/amazonOrders_orderDetails');
    	//$new_order_amazon->setParentId($new_order->getId())
    	//		->setAmazonOrderId($amazon_order_id)->save();



    }

    private function updateOrder($amazon_order_id, $data, $store)
    {

    	$amazon_resource = Mage::getModel('sales/order')->getCollection();
    	$amazon_data = $amazon_resource//->addAttributeToSelect('entity_id')
    		->addAttributeToFilter('amazon_order_id', $amazon_order_id)->load();
			
			

    	$order = Mage::getModel('sales/order');

		foreach ($amazon_data as $amazon) {
    		$order->loadByAttribute("entity_id", $amazon->getId());
    		break; // Only the first element
    	}
		
    	$order->setSubtotal(0)
	    			->setGrandTotal(0)
	    			->setShippingAmount(0)
	    			->setTaxAmount(0);

	    $order->setTotalPaid(0);

	    $order->setStoreId($store?$store:(Mage::app()->getStore()->getId()));

        foreach ($order->getAddressesCollection() as $address) {
            $address->isDeleted(true);
        }
        foreach ($order->getAllItems() as $items) {
        	$items->isDeleted(true);
        }
        foreach ($order->getAllPayments() as $payment) {
        	$payment->isDeleted(true);
        }

    	$order = $this->copyToOrder($data, $order);
    	$order->save();

    }

    private function addItemToAmazonOrder($amazon_order_id, $data)
    {

    	$amazon_resource = Mage::getResourceModel('salese/Order_Collection');
    	$amazon_data = $amazon_resource//->addAttributeToSelect('order_id')
    		->addAttributeToFilter('amazon_order_id', $amazon_order_id)->load();

    	$order = Mage::getModel('sales/order');

		foreach ($amazon_data as $amazon) {
    		$order->loadByAttribute("entity_id", $amazon->getParentId());
    		break; // Only the first element
    	}

    	$order = $this->addItemToOrder($data, $order);
    	$order->save();

    }

    private function addItemToOrder($data, $order)
    {
    	$catalog = Mage::getModel('catalog/product');


        $sku = $data[$this->getFieldIndex("sku")];

        //$productId = $catalog->getIdBySku($sku);
        //$product = $catalog->load($productId);
		$product = Mage::getModel('catalog/product')->loadByAttribute('product_code', $sku);

        if (method_exists($product,'getId') && $product->getId() ) {

			$item = Mage::getModel('sales/order_item')->setProductId($product->getId())
	            ->setSku($product->getSku())
	            ->setName($product->getName())
	            ->setWeight($product->getWeight())
	            ->setTaxClassId($product->getTaxClassId())
	            ->setCost($product->getCost())
	            ->setOriginalPrice($product->getPrice())
	            ->setIsQtyDecimal($product->getIsQtyDecimal());

	        if($product->getSuperProduct()) {
	            $item->setSuperProductId($product->getSuperProduct()->getId());
	            if ($product->getSuperProduct()->isConfigurable()) {
	                $item->setName($product->getSuperProduct()->getName());
	            }
	        }
/*
	        if ($this->getFieldIndex("price")) {
	    		$price = $data[$this->getFieldIndex("price")];
	    	} else {
	    		#the new feed contain the row total instead of item price
	    		$price = $data[$this->getFieldIndex("item-price")];
	    		$price = $price / $data[$this->getFieldIndex("quantity-purchased")];
	    	}

	    	if ($this->getFieldIndex("shipping-fee")) {
	    		$shipping = $data[$this->getFieldIndex("shipping-fee")];
	    	} else {
	    		$shipping = $data[$this->getFieldIndex("shipping-price")];
	    	}

	    	if ($this->getFieldIndex("VAT")) {
	    		$tax = $data[$this->getFieldIndex("VAT")];
	    	} else {
	    		$tax = $data[$this->getFieldIndex("shipping-tax")] + $data[$this->getFieldIndex("item-tax")];
	    	}
*/

		$price = $product->getPrice();
		$shipping = 0;
		if($data[$this->getFieldIndex("ship-country")]=="CA"){
			$rate = 12;
			$tax = $price * $rate / 100;
		}else{
			$rate = 0;
			$tax = 0;
		}

//			$rate = (((float) ($price + $tax)) * 100 ) / $price;
//			$rate = round($rate - 100, 3);

	        $item->setProduct($product)
	        		->setPrice($price)
	        		->setQtyOrdered($data[$this->getFieldIndex("quantity-purchased")])
	        		->setTaxAmount($tax)
	        		->setTaxPercent($rate)
	        		->setRowTotal((float)$price * (float)$data[$this->getFieldIndex("quantity-purchased")])
	        		->setRowWeight((float)$product->getWeight() * (float)$data[$this->getFieldIndex("quantity-purchased")]);

	    	$order->addItem($item);

		    	// Update totals
		    	$order->setSubtotal($order->getSubtotal() + $item->getRowTotal())
		    			->setBaseSubtotal($order->getBaseSubtotal() + $item->getRowTotal())
		    			->setGrandTotal($order->getGrandTotal() + $item->getRowTotal() +
		    									(float)$shipping + (float)$tax)
						->setBaseGrandTotal($order->getBaseGrandTotal() + $item->getRowTotal() +
		    									(float)$shipping + (float)$tax)
		    			->setShippingAmount($order->getShippingAmount() +
		    									(float)$shipping)
		    			->setTaxAmount($order->getTaxAmount() +
		    									(float)$tax);

	    	$order->setTotalPaid($order->getGrandTotal());

	    	return $order;

        } else {
        	Mage::throwException(
	             Mage::helper('amazonOrders')->__('Product sku not found in the store: %s', $sku)
	        );

        }
    }

}
?>
