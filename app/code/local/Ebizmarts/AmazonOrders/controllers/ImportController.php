<?php
/*
 * Created on Jun 26, 2008
 *
 */


class Ebizmarts_AmazonOrders_ImportController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout(array(
                'default',
                'amazon_import_index'
            ));
        $this->renderLayout();
    }

    public function processAction()
    {
    	$block = $this->getLayout()->createBlock('amazonOrders/process');

		$post = $this->getRequest()->getPost();
        try {
			$arr_amazonShippingIds = array();
            if (empty($post)) {
            	$block->setResult('ERROR');
		    	$block->setExistentOrder(0);
		    	$block->setCustomerCreated(0);
		    	$block->setMessage($this->__('Invalid form data.'));
            } else {

	            $csvData = array();
	            $csvData[0] = explode("|", $post['fields']);
	            $i = 0;
	            while (isset($post['data' . $i])) {
	            	$csvData[1 + $i] = explode("|", str_replace("&;","&", $post['data' . $i]));
	            	$i++;
	            }

		        $amazonObject = Mage::getModel('amazonOrders/amazonOrders');
				$has_errors = false;

		        while (list($key, $value) = each($csvData)) {
					if ($key==0) {
		        		$amazonObject->loadFields($value);
		        	} else {
		        		try {
			        		$override = ($post["imported"] == "update");
			        		$store = $post["store"];

							$amazon_order_id = $value[$amazonObject->getFieldIndex("order-id")];
							

							if ( !$amazonObject->checkOrderExists($amazon_order_id) || ( $amazonObject->checkOrderExists($amazon_order_id) && $override ) ) {
								$arr_amazonShippingIds[] = $value;
							}
		
							$result = $amazonObject->importOrder($value, $override, $store);

							if ($result != Ebizmarts_AmazonOrders_Model_AmazonOrders::RETURN_STATUS_ITEM) {
								$block->setResult('SUCCESS');
								$block->setExistentOrder($amazonObject->getExistentOrderFlag());
						    	$block->setCustomerCreated($amazonObject->getCustomerCreatedFlag());
						    	switch ($result) {
						    		case Ebizmarts_AmazonOrders_Model_AmazonOrders::RETURN_STATUS_OMITTED:
						    			$message = $this->__('Amazon order #%s was omitted.', $value[$amazonObject->getFieldIndex("order-id")]);
						    			break;
						    		case Ebizmarts_AmazonOrders_Model_AmazonOrders::RETURN_STATUS_CREATED:
						    			$message = $this->__('Amazon order #%s was imported successfully.', $value[$amazonObject->getFieldIndex("order-id")]);
						    			break;
						    		case Ebizmarts_AmazonOrders_Model_AmazonOrders::RETURN_STATUS_UPDATED:
						    			$message = $this->__('Amazon order #%s was updated successfully.', $value[$amazonObject->getFieldIndex("order-id")]);
						    			break;
						    	}

						    	$block->setMessage($message);
							}
		        		} catch (Exception $e) {
		        			$has_errors = true;
				            $block->setResult('ERROR');
					    	$block->setExistentOrder($amazonObject->getExistentOrderFlag());
					    	$block->setCustomerCreated($amazonObject->getCustomerCreatedFlag());
					    	$block->setMessage($e->getMessage());
				        }
		        	}
		        	if ($has_errors) break;
		        }

				#shipping / carrier
		        array_unique($arr_amazonShippingIds);
		        foreach ( $arr_amazonShippingIds as $value ) {
					$amazonObject->updateShippingMethod($value);
		        }

//	            if (!$has_errors) {
//		            $message = $this->__('Your form has been submitted successfully.');
//		            Mage::getSingleton('adminhtml/session')->addSuccess($message);
//	            }
            }
        } catch (Exception $e) {
        	$block->setResult('ERROR');
	    	$block->setExistentOrder(0);
	    	$block->setCustomerCreated(0);
	    	$block->setMessage($e->getMessage());
        }

        $this->getResponse()->setBody($block->toHtml());
    }

    public function postAction()
    {
		#this is used for orders that has two or more products
    	$_SESSION['last_amazon_order_id'] = "";

        $this->loadLayout(array('default','amazon_import_post'));

        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $fileName   = $_FILES['report']['tmp_name'];
	        $csvObject  = new Varien_File_Csv();
	        $csvObject->setDelimiter("\t");
	        $csvData = $csvObject->getData($fileName);

			$skipped = 0;
			$post_imported = $post["imported"];
			$post_store = $post["store"];
			$data = array();

	        while (list($key, $value) = each($csvData)) {
	        	if ($key==0) $fields = implode('|', $value);
	        	else {
	        		try {
		        		if (is_array($value) && count($value)>1) {
		        			foreach($value as $key => $va) {
		        				if ( $key == 1 && ($va*1) == 0 ) $va = $value[0]; #this is a fixing for telebid.txt (it`s the same as amazon)
		        				$value[$key] = str_replace(array("%", '£', '&pound;', '|'), "", htmlentities($va));
							//Mage::log($value[$key]);
		        			}
		        			$data[] = implode('|', $value);
		        		} else {
		        			$skipped++;
		        		}
	        		} catch (Exception $e) {
	        			$has_errors = true;
			            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			            $this->_redirect('*/*');
			        }
	        	}
	        }

			Mage::register('amazon_fields', $fields);
			Mage::register('amazon_data', $data);
			Mage::register('amazon_skipped', $skipped);
			Mage::register('amazon_imported', $post_imported);
			Mage::register('amazon_store', $post_store);

	    } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*');
        }

        $this->renderLayout();

    }


	public function _validateFormKey() {
		return true;
	}
}
?>
