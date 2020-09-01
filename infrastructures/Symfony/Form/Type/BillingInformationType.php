<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Contracts\Form\FormInterface as PaasFormInterface;
use Teknoo\East\Paas\Object\BillingInformation;

class BillingInformationType extends AbstractType
{
    /**
     * @param FormBuilderInterface<BillingInformation> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => true]);
        $builder->add('service', TextType::class, ['required' => false]);
        $builder->add('address', TextType::class, ['required' => true]);
        $builder->add('zip', TextType::class, ['required' => true]);
        $builder->add('city', TextType::class, ['required' => true]);
        $builder->add('country', TextType::class, ['required' => true]);
        $builder->add('vat', TextType::class, ['required' => false]);

        $builder->setDataMapper(new class implements DataMapperInterface {
            /**
             * @param \Traversable<string, PaasFormInterface> $forms
             * @param ?BillingInformation $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof BillingInformation) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $data->injectDataInto($forms);
            }

            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?BillingInformation $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                if (!$data instanceof BillingInformation) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $data->setName($forms['name']->getData());
                $data->setService($forms['service']->getData());
                $data->setAddress($forms['address']->getData());
                $data->setZip($forms['zip']->getData());
                $data->setCity($forms['city']->getData());
                $data->setCountry($forms['country']->getData());
                $data->setVat($forms['vat']->getData());
            }
        });

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => BillingInformation::class,
        ]);

        return $this;
    }
}
