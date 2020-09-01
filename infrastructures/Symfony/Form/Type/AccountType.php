<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Website\Object\User;
use Teknoo\East\Paas\Contracts\Form\FormInterface as PaasFormInterface;
use Teknoo\East\Paas\Object\Account;

class AccountType extends AbstractType
{
    /**
     * @param FormBuilderInterface<Account> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => true]);
        $builder->add('owner', DocumentType::class, ['class' => User::class, 'required' => true]);

        $builder->add('billingInformation', BillingInformationType::class, ['required' => true]);
        $builder->add('paymentInformation', PaymentInformationType::class, ['required' => false]);

        $builder->setDataMapper(new class implements DataMapperInterface {
            /**
             * @param \Traversable<string, PaasFormInterface> $forms
             * @param ?Account $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof Account) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $data->injectDataInto($forms);
            }

            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?Account $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                if (!$data instanceof Account) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $data->setName($forms['name']->getData());
                $data->setOwner($forms['owner']->getData());
                $data->setBillingInformation($forms['billingInformation']->getData());
                $data->setPaymentInformation($forms['paymentInformation']->getData());
            }
        });

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Account::class,
        ]);

        return $this;
    }
}
