<?php
/**
 * Basic redirect and success actions for the DIBS payment window implementation
 *
 * @author jonathan@madepeople.se
 */
class Made_Dibs_PaymentWindowController extends Mage_Core_Controller_Front_Action
{
    protected $_order;

    /**
     * When Magento claims the order has been successfully placed
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setDibsQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('made_dibs/paymentwindow_redirect')->toHtml());
        $session->unsQuoteId();
        $session->unsRedirectUrl();
    }

    /**
     * When a customer cancels payment in the DIBS gateway
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getDibsQuoteId(true));
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
        }
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

    /**
     * We have returned from the DIBS gateway and they claim everything is
     * epic. Since there is a callback functionality and we need to handle
     * it the same way as this, we just us the callbackAction to sort the
     * order information
     */
    public function returnAction()
    {
        // We unset the cart because things are successful
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getDibsQuoteId(true));
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();

        try {
            $this->callbackAction();
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } catch (Exception $e) {
            $order = $this->_initOrder();
            $order->addStatusHistoryComment('CAUTION! This order could have been paid, please inspect the DIBS administration panel. Error when returning from gateway: ' . $e->getMessage());
            $order->cancel()
                    ->save();

            Mage::getSingleton('core/session')->addError($e->getMessage());
            Mage::logException($e);

            $this->_redirect('checkout/onepage/failure');
        }
    }

    /**
     * Initialize the order object for the current transaction
     *
     * @throws Mage_Payment_Exception
     */
    protected function _initOrder()
    {
        if (!$this->_order) {
            $fields = $this->getRequest()->getPost();

            if (!isset($fields['orderId'])) {
                throw new Mage_Payment_Exception('Required field Order ID is missing');
            }

            $order = Mage::getModel('sales/order')
                    ->loadByIncrementId($fields['orderId']);

            if (!$order) {
                throw new Mage_Payment_Exception('Order with ID "' . $fields['orderID'] . '" could not be found');
            }

            $this->_order = $order;
        }

        return $this->_order;
    }

    /**
     * Handle the callback information from DIBS, needs to be synchronous in
     * case the gateway sends the user to the success page the same time as
     * the DIBS callback calls us.
     *
     * We have everything within a transaction to prevent race conditions.
     *
     * @TODO: Use row-level locking on the order row instead, so we can scale
     *
     * @return void
     */
    public function callbackAction()
    {
        $write = Mage::getSingleton('core/resource')
                    ->getConnection('core_write');

        try {
            $write->beginTransaction();
            $order = $this->_initOrder();
            if ($order->getState() !== Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
                // Order is not in review state. It's possible that the payment has
                // already been registered via a callback or similar.
                $write->rollback();
                return;
            }

            $methodInstance = $order->getPayment()
                    ->getMethodInstance();

            if (!($methodInstance instanceof Made_Dibs_Model_Payment_Paymentwindow)) {
                throw new Mage_Payment_Exception('Order isn\'t a DIBS order');
            }

            $fields = $this->getRequest()->getPost();
            $mac = $methodInstance->calculateMac($fields);
            if ($mac != $fields['MAC']) {
                throw new Mage_Payment_Exception('MAC verification failed for order "' . $fields['orderID'] . '"');
            }

            $payment = $order->getPayment();
            $payment->setTransactionId($fields['transaction'])
                    ->setIsTransactionApproved(true)
                    ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $fields);

            if (empty($fields['capturenow'])) {
                // Leave the transaction open for captures/refunds/etc
                $payment->setPreparedMessage('DIBS - Payment Authorized.');
                $payment->setIsTransactionClosed(0);
                $payment->authorize(false, $order->getGrandTotal());
            } else {
                // Order has been fully paid, we can't do any extra API magic
                $payment->setPreparedMessage('DIBS - Payment Successful.');
                $payment->registerCaptureNotification($order->getGrandTotal());
            }

            $newOrderStatus = $methodInstance->getConfigData('order_status');
            if (!empty($newOrderStatus)) {
                $order->setStatus($newOrderStatus);
            }

            $order->save();
            $write->commit();
        } catch (Exception $e) {
            $write->rollback();
            throw $e;
        }
    }
}