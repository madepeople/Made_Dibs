<?php
/**
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Model_Observer
{
    /**
     * We use an observer to set the order to a payment pending state, making
     * sure that we follow the Magento payment flow
     *
     * @param Varien_Event_Observer $observer
     */
    public function setTransactionToPendingGateway(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()
                ->getOrder()
                ->getPayment();

        if (!($payment->getMethodInstance() instanceof Made_Dibs_Model_Payment_Gateway)) {
            return;
        }

        $payment->setIsTransactionPending(true);
    }

    /**
     * We need to call the authorize function separately in order to maintain
     * correct transaction hierarchy when using the authorize+capture action,
     * and the only way to do that seems to be from within the
     * "sales_order_payment_capture" event
     *
     * @param Varien_Event_Observer $observer
     */
    public function authorizeBeforeCapture(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $method = $payment->getMethodInstance();
        if (!($method instanceof Made_Dibs_Model_Payment_Api)) {
            return;
        }

        if ($payment->hasData('last_trans_id')
                || $method->getConfigPaymentAction() !== Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
            // If we have last_trans_id it isn't a fresh transaction and we
            // might actually be capturing a previous authorization
            return;
        }

        $invoice = $observer->getEvent()->getInvoice();

        // @see Mage_Sales_Model_Order_Payment line 379
        $amount = Mage::app()->getStore()->roundPrice($invoice->getBaseGrandTotal());
        $payment->authorize(true, $amount);

        // Our capture method requires a parent transaction, and last_trans_id
        // might actually be something else in other cases, but here we choose
        // that the parent one for capture is the last one, from authorization
        $payment->setAuthorizeTransactionId($payment->getLastTransId());
    }

    /**
     * This method cleans up old pending_gateway orders as they are probably
     * left over from customers who closed their browsers, lost internet
     * connectivity, etc.
     *
     * @param Varien_Object $observer
     */
    public function cancelOldPendingGatewayOrders($observer)
    {
        $date = date('Y-m-d H:i:s', strtotime('-1 days'));
        $orderCollection = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('state', 'payment_review')
                ->addAttributeToFilter('created_at', array('lt' => $date));

        foreach ($orderCollection as $order) {
            if (!$order->canCancel()) {
                continue;
            }

            $method = $order->getPayment()
                    ->getMethod();

            if (!strstr($method, 'made_dibs')) {
                continue;
            }

            $order->cancel();
            $order->addStatusHistoryComment('The order was automatically cancelled due to more than 24 hours of gateway inactivity.');
            $order->save();
        }
    }
}