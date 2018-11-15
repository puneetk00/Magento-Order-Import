<?php

class Ebizmarts_AmazonOrders_Block_Post extends Mage_Core_Block_Template
{
	private $total_lines = 0;
	private $processed_lines = 0;
	private $pointer = 0;


	protected function initProcess() {
		$this->total_lines = count($this->getAmazonData());
	}

	protected function moveNext() {
		if ($this->pointer < $this->total_lines) {
			$data = $this->getAmazonData();
			$i = 1;
			$tmp = explode('|', $data[$this->pointer]);
			$amazon_id = $tmp[1];   // amazon_order_id
			while ($amazon_id == $tmp[1]) {
				if ($this->pointer + $i < $this->total_lines) {
					$tmp = explode('|', $data[$this->pointer + $i]);
				} else {
					$tmp = NULL;
				}
				$i++;
			}

			$request = 'fields=' . $this->getFields();
			for ($j = 0; $j < $i - 1; $j++) {
				$request .= "&data$j=" . str_replace("&", "%26;",$data[$this->pointer]);
				$this->pointer++;
				$this->processed_lines++;
			}
			$request .= '&imported=' . $this->getPostImported();
			$request .= '&store=' . $this->getPostStore();
			return str_replace("'", "\\'", $request);
		} else {
			return false;
		}
	}


    protected function incCustomersCreated() {
    	$this->customers_created++;
    }

    protected function incExistentOrders() {
    	$this->existent_orders++;
    }


    protected function getPercentProgress() {
    	return $this->processed_lines / $this->total_lines;
    }

    protected function getTotalLines() {
    	return $this->total_lines;
    }

    protected function getFields() {
    	return Mage::registry('amazon_fields');
    }

    protected function getAmazonData() {
    	return Mage::registry('amazon_data');
    }

    protected function getSkipped() {
    	return Mage::registry('amazon_skipped');
    }

    protected function getPostImported() {
    	return Mage::registry('amazon_imported');
    }

    protected function getPostStore() {
    	return Mage::registry('amazon_store');
    }



}
?>