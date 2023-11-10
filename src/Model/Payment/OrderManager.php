<?php

namespace ForumPay\PaymentGateway\PrestaShopModule\Model\Payment;

use ForumPay\PaymentGateway\PrestaShopModule\Exception\OrderAlreadyConfirmedException;
use ForumPay\PaymentGateway\PrestaShopModule\Model\CheckoutTransactions;

/**
 * Manages internal states of the order and provides
 * and interface for dealing with PrestaShop internal
 */
class OrderManager
{
    private CheckoutTransactions $checkoutTransactions;

    public function __construct(CheckoutTransactions $checkoutTransactions)
    {
        $this->checkoutTransactions = $checkoutTransactions;
    }

    /**
     * Get currency customer used when creating order
     *
     * @return string
     */
    public function getOrderCurrency(): string
    {
        return \Context::getContext()->currency->iso_code;
    }

    /**
     * Get order total by order id from db
     *
     * @return string
     */
    public function getOrderTotal(): string
    {
        $orderId = $this->getOrderId();

        $order = new \Order($orderId);

        return number_format($order->total_paid, 2, '.', '');
    }

    /**
     * Get customer IP address that was used when order is created
     *
     * @return string
     */
    public function getOrderCustomerIpAddress(): string
    {
        return \Tools::getRemoteAddr();
    }

    /**
     * Get customer email address that was used when order is created
     *
     * @return string
     */
    public function getOrderCustomerEmail(): string
    {
        return \Context::getContext()->customer->email;
    }

    /**
     * Get customer ID if registered customer or construct one for guests
     *
     * @return int
     */
    public function getOrderCustomerId(): int
    {
        return (int) \Context::getContext()->customer->id;
    }

    /**
     * Update order with new status
     *
     * @param string $orderId
     * @param string $newStatus
     * @throws OrderAlreadyConfirmedException
     */
    public function updateOrderStatus(string $orderId, string $newStatus): void
    {
        $newStatus = strtolower($newStatus);
        $order = new \Order($orderId);

        if ($newStatus === 'confirmed') {
            $newHistory = new \OrderHistory();
            $newHistory->id_order = (int) $orderId;
            $newHistory->changeIdOrderState(
                (int) \Configuration::get('FORUMPAY_SUCCESS_ORDER_STATUS'),
                $order
            );
        } elseif ($newStatus === 'cancelled') {
            if ((int) $order->current_state === (int) \Configuration::get('FORUMPAY_SUCCESS_ORDER_STATUS')) {
                throw new OrderAlreadyConfirmedException();
            }
            $newHistory = new \OrderHistory();
            $newHistory->id_order = (int) $orderId;
            $newHistory->changeIdOrderState(
                (int) \Configuration::get('FORUMPAY_CANCELLED_ORDER_STATUS'),
                $order
            );
        }
    }

    /**
     * Save metadata to order
     *
     * @param string $orderId
     * @param string $paymentId
     * @param array $data
     * @param bool $update
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function saveOrderMetaData(string $orderId, string $paymentId, array $data, bool $update = false): bool
    {
        return $update
                ? $this->checkoutTransactions->update($orderId, $paymentId, $data)
                : $this->checkoutTransactions->insert($orderId, $paymentId, $data);
    }

    /**
     * Fetch metadata from order
     *
     * @param string $paymentId
     *
     * @return array|null
     * @throws \Exception
     */
    public function getOrderMetaData(string $paymentId): ?array
    {
        return $this->checkoutTransactions->getData($paymentId);
    }

    /**
     * @param string $paymentId
     *
     * @return int|false
     */
    public function getOrderIdByPaymentId(string $paymentId)
    {
        return $this->checkoutTransactions->getOrderId($paymentId);
    }

    /**
     *
     * @return string|false
     */
    public function getOrderId()
    {
        return \Context::getContext()->cookie->ForumPayOrderId;
    }
}
