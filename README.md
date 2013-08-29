DIBS Payment Window Module for Magento
==

Implementation of the DIBS Payment Window gateway &amp; API solution.

Features
--

* HMAC security calculation
* Direct payment capture
* DIBS Payment Type selection
* Callback URL locking to prevent recording parallel payments
* Follows Magento's transaction API, and supports:
	* Per-transaction debug information
	* Partial captures of authorizations
	* Partial refunds of captured invoices
	* Payment void
	* Re-authorization of previously expired authorizations

Migrating from another DIBS module
--

This covers the easiest way to migrate from another DIBS module to this one. The migration doesn't cover the API cases that newer modules include, meaning pending authorizations made with another module needs to be captured from within the DIBS administration interface after deployment of this module.

Example migration script:

```php
$this->startSetup();

$connection = $this->getConnection();
$connection->delete($this->getTable('core_config_data'), 'path LIKE "payment/dibs%"');

$connection->update($this->getTable('sales_flat_order_payment'), array(
    'method' => 'made_dibs_paymentwindow'
), "method LIKE 'dibs%'");

$this->endSetup();

Mage::getConfig()->reinit();
Mage::app()->reinitStores();
```

After the above script has run it should be safe to remove/deactivate the other module.

License
--
This project is licensed under the 4-clause BSD License, see [LICENSE](https://github.com/madepeople/Made_Dibs/blob/master/LICENSE)
