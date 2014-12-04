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
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canManageRecurringProfiles = false;

    protected $_code = 'made_dibs_gateway';

    const PAYMENTWINDOW_URL = 'https://sat1.dibspayment.com/dibspaymentwindow/entrypoint';

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param Varien_Object
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    /**
     * We always just place a simple order, waiting for gateway action.
     *
     * Also, we shouldn't know/guess if it's an authorization or capture
     * that's going to happen at the gateway.
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return Mage_Payment_Model_Method_Abstract::ACTION_ORDER;
    }

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
     * DIBS doesn't like all characters when sent to them, this functions
     * removes those characters. It seems like no *urlencode, html entity
     * encode method works.
     *
     * @param $value
     */
    protected function _cleanDibsValue($value)
    {
        $search = array(
            '&',
            // Add more here
        );

        $replace = '';
        $value = str_replace($search, $replace, $value);
        return $value;
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

        $totalAmount = $this->formatAmount($order->getGrandTotal(), $order->getOrderCurrencyCode());

        $fields = new Varien_Object;
        $fields->setMerchant($this->getConfigData('merchant_id'))
                ->setCurrency($this->getDibsCurrencyCode($order->getOrderCurrencyCode()))
                ->setAmount($totalAmount)
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

        if ($this->getConfigData('payment_action') === Mage_Paygate_Model_Authorizenet::ACTION_AUTHORIZE_CAPTURE) {
            $fields->setData('capturenow', '1');
        }

        $billingAddress = $order->getBillingAddress();
        $fields->setData('billingFirstName', $this->_cleanDibsValue($billingAddress->getFirstname()));
        $fields->setData('billingLastName', $this->_cleanDibsValue($billingAddress->getLastname()));
        $fields->setData('billingAddress', $this->_cleanDibsValue($billingAddress->getStreet(1)));

        $street2 = $billingAddress->getStreet(2);
        if (!empty($street2)) {
            $fields->setData('billingAddress2', $this->_cleanDibsValue($street2));
        }

        $email = $order->getCustomerEmail()
            ?: $billingAddress->getEmail();

        $fields->setData('billingPostalCode', $this->_cleanDibsValue($billingAddress->getPostcode()));
        $fields->setData('billingPostalPlace', $this->_cleanDibsValue($billingAddress->getCity()));
        $fields->setData('billingEmail', $email);
        $fields->setData('billingMobile', $this->_cleanDibsValue($order->getTelephone()));

        $oiData = array();
        $calculatedAmount = 0;
        $i = 1;
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                // Only pass main products
                continue;
            }

            $name = $item->getName();
            if (empty($name)) {
                // Gift wraps etc don't have names (what else do they have?) DIBS needs the name.
                $name = $item->getSku();
            }

            $name = $this->_cleanDibsValue($name);
            $sku = $this->_cleanDibsValue($item->getSku());

            $amount = $this->formatAmount($item->getPriceInclTax(), $order->getOrderCurrencyCode());
            $row = (int)$item->getQtyOrdered() . ';' .
                $name . ';' .
                $amount . ';' .
                $sku;

            $oiData['oiRow' . $i++] = $row;
            $calculatedAmount += bcmul($amount, $item->getQtyOrdered());
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
                case 'giftcardaccount':
                case 'giftwrapping':
                case 'discount':
                case 'ugiftcert':
                    $value = -(abs($total->getValue()));
                    if (empty($value)) {
                        continue 2;
                    }
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

            $title = $total->getTitle();
            if (empty($title)) {
                $title = $code;
            }

            $amount = $this->formatAmount($value, $order->getOrderCurrencyCode());
            $row = '1;' . $title . ';' .
                    $amount . ';' .
                    $code;

            $oiData['oiRow' . $i++] = $row;
            $calculatedAmount += $amount;
        }

        if ($totalAmount === $calculatedAmount) {
            $fields->setData('oiTypes', 'QUANTITY;DESCRIPTION;AMOUNT;ITEMID');
            $fields->setData('oiNames', 'Quantity;Product;Amount;SKU');
            foreach ($oiData as $key => $value) {
                $fields->setData($key, $value);
            }
        }

        $hmac = $this->calculateMac($fields->toArray());
        $fields->setData('MAC', $hmac);

        return $fields;
    }
}
