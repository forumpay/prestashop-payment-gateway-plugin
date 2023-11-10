<?php

class ForumPayRedirectModuleFrontController extends \ModuleFrontController
{
    public function initContent(): void
    {
        if (!\Configuration::get('FORUMPAY_CONFIG_ENABLED')) {
            \Tools::redirect($this->context->link->getPageLink('cart', true));
        }

        parent::initContent();

        $this->context->controller->addJS($this->module->getPathUri() . '/js/forumpay_widget.js');
        $this->context->controller->addJS($this->module->getPathUri() . '/js/forumpay.js');

        $this->context->controller->addCSS($this->module->getPathUri() . '/css/forumpay.css');
        $this->context->controller->addCSS($this->module->getPathUri() . '/css/forumpay_widget.css');

        $orderId = $this->createNewOrder();

        $this->context->smarty->assign([
            'apiBase' => $this->context->shop->getBaseURL() . 'module/forumpay/rest?ajax',
            'returnUrl' => $this->getReturnUrl($orderId),
            'cancelUrl' => $this->context->link->getPageLink('cart', true),
        ]);

        $this->setTemplate('module:forumpay/views/templates/hook/payment.twig');
    }

    /**
     * @return string
     */
    private function createNewOrder(): string
    {
        $cart = $this->context->cart;
        $cartId = $cart->id;

        if ($cartId) {
            $forumpay = new \ForumPay();
            $amount = number_format($cart->getOrderTotal(true, \Cart::BOTH), 2);

            $newOrderStatus = (int) \Configuration::get('FORUMPAY_INITIAL_ORDER_STATUS');
            $forumpay->validateOrder(
                $cartId,
                $newOrderStatus,
                $amount,
                $forumpay->displayName,
                null,
                [],
                null,
                false,
                $cart->secure_key
            );

            $order = new Order((int) \Order::getOrderByCartId($cartId));
            $this->context->cookie->ForumPayOrderId = $order->id;

            return $order->id;
        }

        return $this->context->cookie->ForumPayOrderId;
    }

    /**
     * @param string $orderId
     *
     * @return string
     */
    private function getReturnUrl(string $orderId): string
    {
        return $this->context->link->getPageLink(
            'order-confirmation',
            true,
            null,
            [
                'id_cart' => $this->context->cart->id,
                'id_module' => $this->module->id,
                'id_order' => $orderId,
                'key' => $this->context->customer->secure_key,
                'action' => 'details',
            ]
        );
    }
}
