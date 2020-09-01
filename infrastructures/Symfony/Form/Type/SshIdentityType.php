<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\SshIdentity;

class SshIdentityType extends AbstractType
{
    /**
     * @param FormBuilderInterface<SshIdentity> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => true, 'label' => 'Identity name']);
        $builder->add('privateKey', TextareaType::class, ['required' => true]);

        $builder->setDataMapper(new class implements DataMapperInterface {
            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?SshIdentity $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof SshIdentity) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $forms['name']->setData($data->getName());
                $forms['privateKey']->setData($data->getPrivateKey());
            }

            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?SshIdentity $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                $forms = \iterator_to_array($forms);
                $data = new SshIdentity(
                    (string) $forms['name']->getData(),
                    (string) $forms['privateKey']->getData()
                );
            }
        });

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => SshIdentity::class,
            'empty_data' => null,
        ]);

        return $this;
    }
}
