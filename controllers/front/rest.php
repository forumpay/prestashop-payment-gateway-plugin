<?php

use ForumPay\PaymentGateway\PrestaShopModule\Exception\ApiHttpException;
use ForumPay\PaymentGateway\PrestaShopModule\Exception\ForumPayException;
use ForumPay\PaymentGateway\PrestaShopModule\Exception\ForumPayHttpException;
use ForumPay\PaymentGateway\PrestaShopModule\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\PrestaShopModule\Logger\PrivateTokenMasker;
use ForumPay\PaymentGateway\PrestaShopModule\Model\CancelPayment;
use ForumPay\PaymentGateway\PrestaShopModule\Model\CheckoutTransactions;
use ForumPay\PaymentGateway\PrestaShopModule\Model\CheckPayment;
use ForumPay\PaymentGateway\PrestaShopModule\Model\Configuration;
use ForumPay\PaymentGateway\PrestaShopModule\Model\GetCurrencyList;
use ForumPay\PaymentGateway\PrestaShopModule\Model\GetCurrencyRate;
use ForumPay\PaymentGateway\PrestaShopModule\Model\Payment\ForumPay;
use ForumPay\PaymentGateway\PrestaShopModule\Model\Payment\OrderManager;
use ForumPay\PaymentGateway\PrestaShopModule\Model\Request;
use ForumPay\PaymentGateway\PrestaShopModule\Model\Response;
use ForumPay\PaymentGateway\PrestaShopModule\Model\StartPayment;
use ForumPay\PaymentGateway\PrestaShopModule\Model\Webhook;
use ForumPay\PaymentGateway\PrestaShopModule\Model\RestoreCart;

class ForumpayRestModuleFrontController extends \ModuleFrontController
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

    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /** @var bool */
    public $ajax;

    public function init()
    {
        parent::init();

        $configuration = new Configuration(
            $this->get('prestashop.adapter.legacy.configuration'),
            $this->context
        );

        $this->logger = (new ForumPayLogger())
            ->addParser(new PrivateTokenMasker())
            ->setLogDebug((bool) \Configuration::get('FORUMPAY_LOG_DEBUG'));

        $this->forumPay = new ForumPay(
            $configuration,
            new OrderManager(
                new CheckoutTransactions()
            ),
            $this->logger
        );

        $this->initRoutes();
    }

    protected function initRoutes()
    {
        $this->routes = [
            'currencies' => new GetCurrencyList($this->forumPay, $this->logger),
            'getRate' => new GetCurrencyRate($this->forumPay, $this->logger),
            'startPayment' => new StartPayment($this->forumPay, $this->logger),
            'checkPayment' => new CheckPayment($this->forumPay, $this->logger),
            'cancelPayment' => new CancelPayment($this->forumPay, $this->logger),
            'webhook' => new Webhook($this->forumPay, $this->logger),
            'restoreCart' => new RestoreCart($this->forumPay, $this->logger),
        ];
    }

    /**
     * Execute HTTP request and return serialized response
     *
     * @return string|null
     */
    public function displayAjax(): ?string
    {
        try {
            $request = new Request();

            $route = $request->getRequired('act');

            if (array_key_exists($route, $this->routes)) {
                $service = $this->routes[$route];
                $response = $service->execute(new Request());
                if ($response !== null) {
                    echo $this->serializeResponse($response);
                    exit;
                }
            }
        } catch (ApiHttpException $e) {
            $response = new Response();
            $response->setHttpResponseCode($e->getHttpCode());

            $this->serializeError($e);
        } catch (ForumPayException $e) {
            $response = new Response();
            $response->setHttpResponseCode(ForumPayHttpException::HTTP_BAD_REQUEST);

            $this->serializeError(
                new ForumPayHttpException(
                    $e->getMessage(),
                    $e->getCode(),
                    ForumPayHttpException::HTTP_BAD_REQUEST
                )
            );
        } catch (\Exception $e) {
            $response = new Response();
            $response->setHttpResponseCode(ForumPayHttpException::HTTP_INTERNAL_ERROR);

            $this->serializeError(
                new ForumPayHttpException(
                    $e->getMessage(),
                    $e->getCode(),
                    ForumPayHttpException::HTTP_INTERNAL_ERROR,
                )
            );
        }

        return null;
    }

    /**
     * @param $response
     *
     * @return false|string
     */
    private function serializeResponse($response)
    {
        return json_encode($response->toArray());
    }

    /**
     * @param ForumPayHttpException $e
     */
    private function serializeError(ForumPayHttpException $e): void
    {
        echo json_encode([
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ]);
    }
}
