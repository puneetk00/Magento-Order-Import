<?php

$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
	ALTER TABLE sales_flat_order ADD amazon_order_id varchar(255) after entity_id;
SQLTEXT;

$installer->run($sql);
//demo 
//Mage::getModel('core/url_rewrite')->setId(null);
//demo 
$installer->endSetup();
	 