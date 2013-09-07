<?php
/**
 * Implementation of the DIBS payment window solution
 *
 * @see http://tech.dibspayment.com/integration_methods/dibs_payment_window/
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Model_Payment_Gateway extends Made_Dibs_Model_Payment_Abstract
{
    protected $_isGateway = true;
    protected $_canUseInternal = false;
    protected $_canManageRecurringProfiles = false;

    protected $_code = 'made_dibs_gateway';

    const PAYMENTWINDOW_URL = 'https://sat1.dibspayment.com/dibspaymentwindow/entrypoint';

    /**
     * Order placement gateway form POST redirect in-the-middle URL
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('made_dibs/gateway/redirect',
                array('_secure' => true));
    }

    /**
     * The success return URL for a completed payment
     *
     * @return string
     */
    public final function getReturnUrl()
    {
        return Mage::getUrl('made_dibs/gateway/return',
                array('_secure' => true));
    }

    /**
     * The cancel URL for a payment where the customer has pressed the
     * cancel button in the gateway. This does _not_ involve cases where
     * a browser has been closed down, a session has time out, etc.
     *
     * @return string
     */
    public final function getCancelUrl()
    {
        return Mage::getUrl('made_dibs/gateway/cancel',
                array('_secure' => true));
    }

    /**
     * The callback URL used to finalize a payment's status
     *
     * @return string
     */
    public final function getCallbackUrl()
    {
        return Mage::getUrl('made_dibs/gateway/callback',
                array('_secure' => true));
    }

    /**
     * Builds a Varien_Object containing all fields necessary to render
     * a payment redirect form
     *
     * @param int $orderIncrementId
     * @throws Mage_Payment_Exception
     * @return Varien_Object
     */
    final public function getCheckoutFormFields($orderIncrementId = null)
    {
        if (null === $orderIncrementId) {
            $orderIncrementId = Mage::getSingleton('checkout/session')
                    ->getLastRealOrderId();
        }

        $order = Mage::getModel('sales/order')
                ->loadByIncrementId($orderIncrementId);

        if (!$order || !$order->getId()) {
            throw new Mage_Payment_Exception('Cannot load order with increment id "' . $orderIncrementId. '"');
        }

        $language = Mage::getStoreConfig('general/locale/code')
                 ?: 'en_GB';

        $amount = $this->formatAmount($order->getGrandTotal(), $order->getOrderCurrencyCode());

        $fields = new Varien_Object;
        $fields->setMerchant($this->getConfigData('merchant_id'))
                ->setCurrency($this->getDibsCurrencyCode($order->getOrderCurrencyCode()))
                ->setAmount($amount)
                ->setLanguage($language)
                ->setData('orderId', $order->getIncrementId())
                ->setData('acceptReturnUrl', $this->getReturnUrl())
                ->setData('cancelReturnUrl', $this->getCancelUrl())
                ->setData('callbackUrl', $this->getCallbackUrl())
                ;

        if (trim($this->getConfigData('payment_type')) !== '') {
            $fields->setData('payType', $this->getConfigData('payment_type'));
        }

        if ($this->getConfigData('test')) {
            $fields->setTest('1');
        }

        if ($this->getConfigData('capture_now')) {
            $fields->setData('capturenow', '1');
        }

        $billingAddress = $order->getBillingAddress();
        $fields->setData('billingFirstName', $billingAddress->getFirstname());
        $fields->setData('billingLastName', $billingAddress->getLastname());
        $fields->setData('billingAddress', $billingAddress->getStreet(1));

        $street2 = $billingAddress->getStreet(2);
        if (!empty($street2)) {
            $fields->setData('billingAddress2', $street2);
        }

        $fields->setData('billingPostalCode', $billingAddress->getPostcode());
        $fields->setData('billingPostalPlace', $billingAddress->getCity());
        $fields->setData('billingEmail', $order->getCustomerEmail());
        $fields->setData('billingMobile', $order->getTelephone());

        $fields->setData('oiTypes', 'QUANTITY;DESCRIPTION;AMOUNT;ITEMID');
        $fields->setData('oiNames', 'Quantity;Product;Amount;SKU');

        $i = 1;
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                // Only pass main products
                continue;
            }

            $row = (int)$item->getQtyOrdered() . ';' .
                    $item->getName() . ';' .
                    $this->formatAmount($item->getPriceInclTax(), $order->getOrderCurrencyCode()) . ';' .
                    $item->getSku();

            $fields->setData('oiRow' . $i++, $row);
        }

        // Shipping, giftcards and discounts needs to be separate rows, use the
        // quote totals to determine what to print and exclude values that
        // are already included from other places
        $quoteId = $order->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->collectTotals();

        $totalsToExclude = array('grand_total', 'subtotal', 'tax', 'klarna_tax');

        foreach ($quote->getTotals() as $code => $total) {
            if (in_array($code, $totalsToExclude)) {
                continue;
            }

            switch ($code) {
                case 'discount':
                case 'giftcardaccount':
                case 'ugiftcert':
                    $value = -(abs($total->getValue()));
                    break;
                case 'shipping':
                    // We have to somehow make sure that we use the correctly
                    // calculated value, we can't rely on the shipping tax
                    // being part of the quote totals
                    $value = $order->getShippingTaxAmount()
                        + $order->getShippingAmount();
                    break;
                default:
                    $value = $total->getValue();
            }
            $amount = $this->formatAmount($value, $order->getOrderCurrencyCode());

            $row = '1;' . $total->getTitle() . ';' .
                    $amount . ';' .
                    $total->getCode();

            $fields->setData('oiRow' . $i++, $row);
        }

        $hmac = $this->calculateMac($fields->toArray());
        $fields->setData('MAC', $hmac);

        return $fields;
    }
}
