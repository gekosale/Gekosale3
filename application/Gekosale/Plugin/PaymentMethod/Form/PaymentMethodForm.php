<?php
/*
 * Gekosale Open-Source E-Commerce Platform
 *
 * This file is part of the Gekosale package.
 *
 * (c) Adam Piotrowski <adam@gekosale.com>
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */
namespace Gekosale\Plugin\PaymentMethod\Form;

use Gekosale\Core\Form;
use Gekosale\Plugin\PaymentMethod\Event\PaymentMethodFormEvent;

/**
 * Class PaymentMethodForm
 *
 * @package Gekosale\Plugin\PaymentMethod\Form
 * @author  Adam Piotrowski <adam@gekosale.com>
 */
class PaymentMethodForm extends Form
{
    public function init($payment_methodData = [])
    {
        $form = $this->addForm([
            'name' => 'payment_method'
        ]);

        $basicPane = $form->addChild($this->addFieldset([
            'name'  => 'basic_pane',
            'label' => $this->trans('Basic settings')
        ]));

        $basicLanguageData = $basicPane->addChild($this->addFieldsetLanguage([
            'name'  => 'language_data',
            'label' => $this->trans('Translations'),
        ]));

        $basicLanguageData->addChild($this->addTextField([
            'name'  => 'name',
            'label' => $this->trans('Name'),
            'rules' => [
                $this->addRuleRequired($this->trans('Name is required')),
                $this->addRuleUnique($this->trans('Name already exists'),
                    [
                        'table'   => 'payment_method_translation',
                        'column'  => 'name',
                        'exclude' => [
                            'column' => 'payment_method_id',
                            'values' => $this->getParam('id')
                        ]
                    ]
                ),
            ]
        ]));

        $basicPane->addChild($this->addCheckbox([
            'name'    => 'enabled',
            'label'   => $this->trans('Enabled'),
            'default' => '0'
        ]));

        $basicPane->addChild($this->addTextField([
            'name'    => 'hierarchy',
            'label'   => $this->trans('Hierarchy'),
            'default' => '0'
        ]));

        $costsPane = $form->addChild($this->addFieldset([
            'name'  => 'cost_data',
            'label' => $this->trans('Payment costs')
        ]));

        $type = $costsPane->AddChild($this->addSelect([
            'name'    => 'type',
            'label'   => 'Payment calculation',
            'options' => [
                new Form\Option('1', 'by cart value'),
                new Form\Option('2', 'by cart weight'),
            ],
            'rules'   => [
                $this->addRuleRequired('Cost type is required')
            ]
        ]));

        $cartValue = $costsPane->addChild($this->addRangeEditor([
            'name'            => 'cart_value',
            'label'           => 'Cart value',
            'allow_vat'       => true,
            'range_precision' => 4,
            'price_precision' => 4,
            'dependencies'    => [
                $this->addDependency(Form\Dependency::SHOW, $type, new Form\Conditions\Equals(1), null)
            ]
        ]));

        $cartWeight = $costsPane->addChild($this->addRangeEditor([
            'name'            => 'cart_weight',
            'label'           => 'Cart weight',
            'allow_vat'       => true,
            'range_precision' => 4,
            'price_precision' => 4,
            'dependencies'    => [
                $this->addDependency(Form\Dependency::SHOW, $type, new Form\Conditions\Equals(2), null)
            ]
        ]));


        $shopData = $form->addChild($this->addFieldset([
            'name'  => 'shop_data',
            'label' => $this->trans('Shops')
        ]));

        $shopData->addChild($this->addShopSelector([
            'name'  => 'shops',
            'label' => $this->trans('Shops'),
        ]));

        $form->addFilters([
            $this->addFilterTrim(),
            $this->addFilterSecure()
        ]);

        $event = new PaymentMethodFormEvent($form, $payment_methodData);

        $this->getDispatcher()->dispatch(PaymentMethodFormEvent::FORM_INIT_EVENT, $event);

        $form->populate($event->getPopulateData());

        return $form;
    }
}
