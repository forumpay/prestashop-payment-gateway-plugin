<?php

declare(strict_types=1);

namespace ForumPay\PaymentGateway\PrestaShopModule\Form;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigurationFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $orderStatuses = $this->getOrderStatuses();
        $builder
            ->add('title', TextType::class, [
                'label' => $this->trans('Title:', 'Modules.ForumPay.Admin'),
                'empty_data' => 'ForumPay',
                'help' => $this->trans(
                    'This controls the title which the user sees during checkout.',
                    'Modules.ForumPay.Admin'
                ),
            ])
            ->add('description', TextType::class, [
                'label' => $this->trans('Description:', 'Modules.ForumPay.Admin'),
                'empty_data' => 'Pay with Crypto (by ForumPay)',
                'help' => $this->trans(
                    'This controls the description which the user sees during checkout.',
                    'Modules.ForumPay.Admin'
                ),
            ])
            ->add('api_url', ChoiceType::class, [
                'label' => $this->trans('Environment:', 'Modules.ForumPay.Admin'),
                'empty_data' => 'Production',
                'choices' => [
                    'Production' => 'https://api.forumpay.com/pay/v2/',
                    'Sandbox' => 'https://sandbox.api.forumpay.com/pay/v2/',
                ],
                'help' => $this->trans('ForumPay environment.', 'Modules.ForumPay.Admin'),
            ])
            ->add('api_user', TextType::class, [
                'label' => $this->trans('API User:', 'Modules.ForumPay.Admin'),
                'help' => $this->trans(
                    'You can generate API key in your ForumPay Account.',
                    'Modules.ForumPay.Admin'
                ),
            ])
            ->add('api_key', PasswordType::class, [
                'label' => $this->trans('API Key:', 'Modules.ForumPay.Admin'),
                'help' => $this->trans(
                    'You can generate API secret in your ForumPay Account.',
                    'Modules.ForumPay.Admin'
                ),
                'attr' => [
                    'placeholder' => '*****',
                ],
                'required' => false,
                'always_empty' => false,
            ])
            ->add('pos_id', TextType::class, [
                'label' => $this->trans('POS ID:', 'Modules.ForumPay.Admin'),
                'help' => $this->trans(
                    'Enter your webshop identifier (POS ID). Special characters not allowed. Allowed are: [A-Za-z0-9._-] Eg prestashop-3, prestashop-3', 'Modules.ForumPay.Admin'
                ),
            ])
            ->add('initial_order_status', ChoiceType::class, [
                'label' => $this->trans('Initial Order Status:', 'Modules.ForumPay.Admin'),
                'choices' => $orderStatuses,
                'help' => $this->trans('Order status assigned to a newly created order.', 'Modules.ForumPay.Admin'),
            ])
            ->add('cancelled_order_status', ChoiceType::class, [
                'label' => $this->trans('Canceled Order Status:', 'Modules.ForumPay.Admin'),
                'choices' => $orderStatuses,
                'help' => $this->trans('Order status assigned to cancelled order.', 'Modules.ForumPay.Admin'),
            ])
            ->add('success_order_status', ChoiceType::class, [
                'label' => $this->trans('Success Order Status:', 'Modules.ForumPay.Admin'),
                'choices' => $orderStatuses,
                'help' => $this->trans('Order status assigned to successful orders.', 'Modules.ForumPay.Admin'),
            ])
            ->add('api_url_override', TextType::class, [
                'label' => $this->trans('Custom environment URL:', 'Modules.ForumPay.Admin'),
                'help' => $this->trans(
                    'URL to the api server. This value will override default environment.',
                    'Modules.ForumPay.Admin'
                ),
                'required' => false,
            ])
            ->add('forumpay_log_debug', SwitchType::class, [
                'label' => $this->trans('Debug', 'Modules.ForumPay.Admin'),
                'help' => $this->trans('Enable debug log level.', 'Modules.ForumPay.Admin'),
                'required' => false,
            ])
            ->add('accept_zero_confirmations', SwitchType::class, [
                'label' => $this->trans('Accept Zero Confirmations', 'Modules.ForumPay.Admin'),
                'help' => $this->trans('Enable Accept Zero Confirmations.', 'Modules.ForumPay.Admin'),
                'required' => false,
            ])
            ->add('enabled', SwitchType::class, [
                'label' => $this->trans('Enabled', 'Modules.ForumPay.Admin'),
                'help' => $this->trans('Enable ForumPay Payment Module', 'Modules.ForumPay.Admin'),
                'required' => false,
            ]);
    }

    /**
     * Fetches order statuses and returns them
     *
     * @return array
     */
    private function getOrderStatuses(): array
    {
        $states = \OrderState::getOrderStates((int) \Configuration::get('PS_LANG_DEFAULT'));

        $statusChoices = [];
        foreach ($states as $status) {
            $statusChoices[$status['name']] = $status['id_order_state'];
        }

        return $statusChoices;
    }
}
