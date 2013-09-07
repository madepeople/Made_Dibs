<?php

$this->startSetup();

$connection = $this->getConnection();

$connection->update($this->getTable('core_config_data'), array(
    'path' => new Zend_Db_Expr("REPLACE(path, 'paymentwindow', 'gateway')")
), 'path LIKE "payment/made_dibs_paymentwindow%"');

$connection->update($this->getTable('sales_flat_order_payment'), array(
    'method' => 'made_dibs_gateway'
), "method = 'made_dibs_paymentwindow'");

$this->endSetup();
