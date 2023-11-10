<?php

namespace ForumPay\PaymentGateway\PrestaShopModule\Model;

use ForumPay\PaymentGateway\PrestaShopModule\Exception\ApiHttpException;
use ForumPay\PaymentGateway\PrestaShopModule\Exception\OrderNotFoundException;
use ForumPay\PaymentGateway\PrestaShopModule\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\PrestaShopModule\Model\Payment\ForumPay;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\ApiExceptionInterface;

/**
 * @inheritdoc
 */
class CancelPayment
{
    /**
     * ForumPay payment model
     *
     * @var ForumPay
     */
    private ForumPay $forumPay;

    /**
     * @var ForumPayLogger
     */
    private ForumPayLogger $logger;

    /**
     * Constructor
     *
     * @param ForumPay $forumPay
     * @param ForumPayLogger $logger
     */
    public function __construct(
        ForumPay $forumPay,
        ForumPayLogger $logger
    ) {
        $this->forumPay = $forumPay;
        $this->logger = $logger;
    }

    public function execute(Request $request): void
    {
        try {
            $paymentId = $request->getRequired('payment_id');
            $reason = $request->get('reason', '');
            $description = $request->get('description', '');
            $this->logger->info('CancelPayment entrypoint called.', ['paymentId' => $paymentId]);

            $this->forumPay->cancelPaymentByPaymentId($paymentId, $reason, $description);

            $this->logger->info('CancelPayment entrypoint finished.');
        } catch (ApiExceptionInterface $e) {
            $this->logger->logApiException($e);
            throw new ApiHttpException($e, 5050);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $e->getTrace());
            throw new \Exception($e->getMessage(), 5100, $e);
        }
    }
}
