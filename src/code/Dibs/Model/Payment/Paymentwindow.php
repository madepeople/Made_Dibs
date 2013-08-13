<?php
/**
 * Basic implementation of the DIBS payment window solution
 *
 * @see http://tech.dibspayment.com/integration_methods/dibs_payment_window/
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Model_Payment_Paymentwindow extends Made_Dibs_Model_Payment_Abstract
{
    protected $_code = 'made_dibs';
    protected $_soapClient;

    const PAYMENTWINDOW_URL = 'https://sat1.dibspayment.com/dibspaymentwindow/entrypoint';

    /**
     * Get the SOAP endpoint client for concurrent API calls
     */
    protected function _getSoapClient()
    {
        if (!isset($this->_soapClient)) {
            $this->_soapClient = new SoapClient('https://api.dibspayment.com/merchant/v1/SOAP/Transaction?wsdl');
        }
        return $this->_soapClient;
    }

    /**
     * DIBS JSON endpoint API call method.
     *
     * This will fail if an HTTP 423 code is returned. It means we need to try
     * again later.
     *
     * @param type $method
     * @param type $parameters
     */
    protected function _apiCall($method, $parameters = array())
    {
        $baseEndpoint = 'https://api.dibspayment.com/merchant/v1/JSON/Transaction/';
        $endpoint = $baseEndpoint . $method;

        $httpClient = new Zend_Http_Client($endpoint);
        $httpClient->setAdapter('Zend_Http_Client_Adapter_Curl');
        $httpClient->getAdapter()
                ->setCurlOption(CURLOPT_SSLVERSION, 3);

        $httpClient->setHeaders('Content-Type', 'application/x-www-form-urlencoded');

        $parameters = array_merge(array(
            'merchantId' => $this->getConfigData('merchant_id'),
        ), $parameters);

        $mac = $this->calculateMac($parameters);
        $parameters['MAC'] = $mac;

        $httpClient->setParameterPost('request',
                Mage::helper('core')->jsonEncode($parameters));

        $httpClient->setMethod(Zend_Http_Client::POST);
        $response = $httpClient->request();
        if ($response->getStatus() !== 200) {
            Mage::throwException('An error occurred when communicating with DIBS. HTTP status code: ' . $response->getCode());
        }

        $result = Mage::helper('core')->jsonDecode($response->getBody());

        switch ($result['status']) {
            case 'ACCEPT':
                $message = 'Successfully issued ' . $method . ' for transaction "' . $parameters['transactionId'] . '" at DIBS.';
                Mage::getSingleton('adminhtml/session')->addSuccess($message);
                break;
            case 'PENDING':
                $message = $method . ' successfully issued for transaction "' . $parameters['transactionId'] . '", but it is pending batch processing at DIBS.';
                Mage::getSingleton('adminhtml/session')->addSuccess($message);
                break;
            case 'DECLINE':
                $message = $method . ' was declined: ' . $result['declineReason'];
                Mage::throwException($message);
                break;
            case 'ERROR':
                $message = 'There was an error issuing ' . $method . ' for the transaction: ' . $result['declineReason'];
                Mage::throwException($message);
                break;
        }

        return $result;
    }

    /**
     * Capture an authorized payment. This should only be available if there
     * is an open authorized transaction already.
     *
     * Requires API details entered in the admin interface
     *
     * @param Varien_Object $payment
     * @param int|float $amount
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();

        $parameters = array(
            'transactionId' => $payment->getParentTransactionId(),
            'amount' => $this->formatAmount($amount, $order->getOrderCurrencyCode()),

            // Their endpoint needs booleans as strings
            'doReAuthIfExpired' => (bool)$this->getConfigData('reauth_expired')
                    ? 'true' : 'false',
        );

        $this->_apiCall('CaptureTransaction', $parameters);

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();

        list($transactionId,) = explode('-', $payment->getParentTransactionId());
        $parameters = array(
            'transactionId' => $transactionId,
            'amount' => $this->formatAmount($amount, $order->getOrderCurrencyCode()),
        );

        $this->_apiCall('RefundTransaction', $parameters);

        return $this;
    }

    /**
     * Void previously *authorized* payment.
     *
     * Requires API details entered in the admin interface
     *
     * @param Varien_Object $payment
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        list($transactionId,) = explode('-', $payment->getParentTransactionId());
        $parameters = array(
            'transactionId' => $transactionId,
        );

        $this->_apiCall('CancelTransaction', $parameters);

        return $this;
    }

    /**
     * Order placement gateway form POST redirect in-the-middle URL
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('made_dibs/paymentwindow/redirect',
                array('_secure' => true));
    }

    /**
     * The success return URL for a completed payment
     *
     * @return string
     */
    public final function getReturnUrl()
    {
        return Mage::getUrl('made_dibs/paymentwindow/return',
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
        return Mage::getUrl('made_dibs/paymentwindow/cancel',
                array('_secure' => true));
    }

    /**
     * The callback URL used to finalize a payment's status
     *
     * @return string
     */
    public final function getCallbackUrl()
    {
        return Mage::getUrl('made_dibs/paymentwindow/callback',
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

        if (!$order) {
            throw new Mage_Payment_Exception('Cannot load order with increment id "' . $orderIncrementId. '"');
        }

        $language = Mage::getStoreConfig('general/locale/code')
                 ?: 'en_GB';

        $fields = new Varien_Object;
        $fields->setMerchant($this->getConfigData('merchant_id'))
                ->setCurrency($this->getDibsCurrencyCode($order->getOrderCurrencyCode()))
                ->setAmount($order->getGrandTotal()*100)
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

        $hmac = $this->calculateMac($fields->toArray());
        $fields->setData('MAC', $hmac);

        return $fields;
    }
}