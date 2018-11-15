<?php
/*
 * Module upgraded by Synapse India
 * Developer : puneet puneetk00@gmail.com
 */

class Ebizmarts_AmazonOrders_Block_GridMageOrders extends Mage_Adminhtml_Block_Sales_Order_Grid
{
	protected function _prepareCollection()
    {
        parent::_prepareCollection();

		foreach ($this->getCollection() as $item) {
				
				$order = Mage::getModel("sales/order")->load($item->getEntityId());
				
				if($order->getAmazonOrderId()){
				$item->setIncrementId($item->getIncrementId() .
							' <div style="font-size:80%" > Amazon Id: ' . $order->getAmazonOrderId()
				           . '</div>');
				}
		}

        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

    protected function _prepareColumns() {
    	parent::_prepareColumns();
    	$col = $this->getColumn('real_order_id');
    	$data = $col->getData();
    	$data['type'] = "text";
    	$col->setData($data);

    }


}
?>
