<?php

namespace ForumPay\PaymentGateway\PrestaShopModule\Model\Payment;

use ForumPay\PaymentGateway\PHPClient\Http\Exception\ApiExceptionInterface;
use ForumPay\PaymentGateway\PHPClient\PaymentGatewayApi;
use ForumPay\PaymentGateway\PHPClient\PaymentGatewayApiInterface;
use ForumPay\PaymentGateway\PHPClient\Response\CheckPaymentResponse;
use ForumPay\PaymentGateway\PHPClient\Response\GetCurrencyListResponse;
use ForumPay\PaymentGateway\PHPClient\Response\GetRateResponse;
use ForumPay\PaymentGateway\PHPClient\Response\GetTransactions\TransactionInvoice;
use ForumPay\PaymentGateway\PHPClient\Response\RequestKycResponse;
use ForumPay\PaymentGateway\PHPClient\Response\StartPaymentResponse;
use ForumPay\PaymentGateway\PrestaShopModule\Exception\ForumPayException;
use ForumPay\PaymentGateway\PrestaShopModule\Exception\OrderAlreadyConfirmedException;
use ForumPay\PaymentGateway\PrestaShopModule\Model\Configuration;
use Psr\Log\LoggerInterface;

/**
 * ForumPay payment method model
 */
class ForumPay
{
    /**
     * @var PaymentGatewayApiInterface
     */
    private PaymentGatewayApiInterface $apiClient;

    /**
     * @var Configuration
     */
    private Configuration $configuration;

    /**
     * @var OrderManager
     */
    private OrderManager $orderManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $psrLogger;

    public function __construct(
        Configuration $configuration,
        OrderManager $orderManager,
        LoggerInterface $psrLogger
    ) {
        $this->apiClient = new PaymentGatewayApi(
            $configuration->getApiUrl(),
            $configuration->getMerchantApiUser(),
            $configuration->getMerchantApiSecret(),
            sprintf(
                'fp-pgw[%s] PS %s on PHP %s',
                $configuration->getPluginVersion(),
                $configuration->getPrestaShopVersion(),
                phpversion()
            ),
            $configuration->getStoreLocale(),
            null,
            $psrLogger
        );

        $this->configuration = $configuration;
        $this->orderManager = $orderManager;
        $this->psrLogger = $psrLogger;
    }

    /**
     * Return the list of all available currencies as defined on merchant account
     *
     * @return GetCurrencyListResponse
     *
     * @throws ApiExceptionInterface
     * @throws \Exception
     */
    public function getCryptoCurrencyList(): GetCurrencyListResponse
    {
        $currency = $this->orderManager->getOrderCurrency();

        if (empty($currency)) {
            throw new \Exception('Store currency could not be determined');
        }

        return $this->apiClient->getCurrencyList($currency);
    }

    /**
     * Get rate for a requested currency
     *
     * @param string $currency
     *
     * @return GetRateResponse
     *
     * @throws \Exception
     */
    public function getRate(string $currency): GetRateResponse
    {
        return $this->apiClient->getRate(
            $this->configuration->getPosId(),
            $this->orderManager->getOrderCurrency(),
            $this->orderManager->getOrderTotal(),
            $currency,
            $this->configuration->isAcceptZeroConfirmations() ? 'true' : 'false',
            null,
            null,
            null
        );
    }

    /**
     * @return RequestKycResponse
     * @throws ApiExceptionInterface
     */
    public function requestKyc(): RequestKycResponse
    {
        return $this->apiClient->requestKyc($this->orderManager->getOrderCustomerEmail());
    }

    /**
     * Initiate a start payment and crate order on ForumPay
     *
     * @param string $currency
     * @param string $paymentId
     * @param string $kycPin
     * @return StartPaymentResponse
     *
     * @throws ApiExceptionInterface
     */
    public function startPayment(
        string $currency,
        string $paymentId,
        ?string $kycPin
    ): StartPaymentResponse {
        $response = $this->apiClient->startPayment(
            $this->configuration->getPosId(),
            $this->orderManager->getOrderCurrency(),
            $paymentId,
            $this->orderManager->getOrderTotal(),
            $currency,
            $this->orderManager->getOrderId(),
            $this->configuration->isAcceptZeroConfirmations() ? 'true' : 'false',
            $this->orderManager->getOrderCustomerIpAddress(),
            $this->orderManager->getOrderCustomerEmail(),
            $this->orderManager->getOrderCustomerId(),
            'false',
            '',
            'false',
            null,
            null,
            null,
            null,
            null,
            $kycPin
        );

        $orderId = \Context::getContext()->cookie->ForumPayOrderId;
        $this->orderManager->saveOrderMetaData(
            $orderId,
            $response->getPaymentId(),
            $response->toArray()
        );

        $this->cancelAllPayments($orderId, $response->getPaymentId());

        return $response;
    }

    /**
     * Get detailed payment information for ForumPay
     *
     * @param string $paymentId
     *
     * @return CheckPaymentResponse
     *
     * @throws ForumPayException
     * @throws ApiExceptionInterface
     * @throws \Exception
     */
    public function checkPayment(string $paymentId): CheckPaymentResponse
    {
        $meta = $this->orderManager->getOrderMetaData($paymentId);
        $cryptoCurrency = $meta['currency'];
        $address = $meta['address'];

        $response = $this->apiClient->checkPayment(
            $this->configuration->getPosId(),
            $cryptoCurrency,
            $paymentId,
            $address
        );

        $orderId = $this->orderManager->getOrderIdByPaymentId($paymentId);

        if (strtolower($response->getStatus()) === 'cancelled') {
            try {
                $this->orderManager->updateOrderStatus($orderId, $response->getStatus());
            } catch (OrderAlreadyConfirmedException $e) {
                return $response;
            }
            if (!$this->checkAllPaymentsAreCanceled($orderId)) {
                return $response;
            } else {
                $this->restoreCart($orderId);
            }
        }

        $updatedData = array_merge($meta, $response->toArray());

        $this->orderManager->saveOrderMetaData(
            $orderId,
            $paymentId,
            $updatedData,
            true
        );

        $this->orderManager->updateOrderStatus($orderId, $response->getStatus());

        return $response;
    }

    /**
     * Cancel give payment on ForumPay
     *
     * @param string $paymentId
     * @param string $reason
     * @param string $description
     *
     * @throws ApiExceptionInterface
     */
    public function cancelPaymentByPaymentId(
        string $paymentId,
        string $reason = '',
        string $description = ''
    ): void {
        $meta = $this->orderManager->getOrderMetaData($paymentId);
        $currency = $meta['currency'];
        $address = $meta['address'];
        $this->cancelPayment($paymentId, $currency, $address, $reason, $description);
    }

    /**
     * Cancel give payment on ForumPay
     *
     * @param string $paymentId
     * @param string $currency
     * @param string $address
     * @param string $reason
     * @param string $description
     *
     * @throws ApiExceptionInterface
     */
    public function cancelPayment(
        string $paymentId,
        string $currency,
        string $address,
        string $reason = '',
        string $description = ''
    ): void {
        $this->apiClient->cancelPayment(
            $this->configuration->getPosId(),
            $currency,
            $paymentId,
            $address,
            $reason,
            substr($description, 0, 255),
        );
    }

    /**
     * Restores cart to the previous state.
     *
     * @param string|null $orderId
     *
     * @return void
     */
    public function restoreCart(string $orderId = null): void
    {
        if ($orderId === null) {
            $orderId = $this->orderManager->getOrderId();
        }
        $oldCartId = \Cart::getCartIdByOrderId($orderId);
        $oldCart = new \Cart($oldCartId);

        $dup = $oldCart->duplicate();
        $newCart = new \Cart($dup['cart']->id);
        \Context::getContext()->cart->delete();
        \Context::getContext()->cart = $newCart;
        \Context::getContext()->cookie->id_cart = $newCart->id;
    }

    /**
     * Cancel all except existingPayment on ForumPay
     *
     * @param string $orderId
     * @param string $existingPaymentId
     *
     * @throws ApiExceptionInterface
     */
    private function cancelAllPayments(string $orderId, string $existingPaymentId): void
    {
        try {
            $existingPayments = $this->apiClient->getTransactions(null, null, $orderId);
        } catch (\Exception $e) {
            throw $e;
        }

        /** @var TransactionInvoice $existingPayment */
        foreach ($existingPayments->getInvoices() as $existingPayment) {
            if (
                $existingPayment->getPaymentId() === $existingPaymentId
                || strtolower($existingPayment->getStatus()) !== 'waiting'
            ) {
                // newly created
                continue;
            }

            $this->cancelPayment(
                $existingPayment->getPaymentId(),
                $existingPayment->getCurrency(),
                $existingPayment->getAddress()
            );
        }
    }

    /**
     * Check if all payments for a given order are canceled on ForumPay
     *
     * @param string $orderId
     *
     * @return bool
     *
     * @throws ApiExceptionInterface
     */
    private function checkAllPaymentsAreCanceled(string $orderId): bool
    {
        try {
            $existingPayments = $this->apiClient->getTransactions(null, null, $orderId);
        } catch (\Exception $e) {
            throw $e;
        }

        /** @var TransactionInvoice $existingPayment */
        foreach ($existingPayments->getInvoices() as $existingPayment) {
            if (
                strtolower($existingPayment->getStatus()) !== 'cancelled'
                && $existingPayment->getPosId() === \Configuration::get('FORUMPAY_POS_ID')
            ) {
                return false;
            }
        }

        return true;
    }
}
