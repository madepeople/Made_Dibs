DIBS Payment Module
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