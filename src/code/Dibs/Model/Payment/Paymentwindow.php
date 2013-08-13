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

    const PAYMENTWINDOW_URL = 'https://sat1.dibspayment.com/dibspaymentwindow/entrypoint';

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
            $fields->setData('captureNow', '1');
        }

        $hmac = $this->calculateMac($fields->toArray());
        $fields->setData('MAC', $hmac);

        return $fields;
    }
}