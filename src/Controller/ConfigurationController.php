<?php

declare(strict_types=1);

namespace ForumPay\PaymentGateway\PrestaShopModule\Controller;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationController extends FrameworkBundleAdminController
{
    public function index(Request $request): Response
    {
        $textFormDataHandler = $this->get(
            'forumpay.payment_gateway.presta_shop_module.form.configuration_form_data_handler'
        );

        $textForm = $textFormDataHandler->getForm();
        $textForm->handleRequest($request);

        if ($textForm->isSubmitted() && $textForm->isValid()) {
            $errors = $textFormDataHandler->save($textForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('forumpay_configuration_form');
            }

            $this->flashErrors($errors);
        }

        return $this->render('@Modules/forumpay/views/templates/admin/form.html.twig', [
            'configurationForm' => $textForm->createView(),
        ]);
    }
}
