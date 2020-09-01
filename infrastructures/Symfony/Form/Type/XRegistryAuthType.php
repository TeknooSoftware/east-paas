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
use Teknoo\East\Paas\Object\XRegistryAuth;

class XRegistryAuthType extends AbstractType
{
    /**
     * @param FormBuilderInterface<XRegistryAuth> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('username', TextType::class, ['required' => false]);
        $builder->add('password', TextType::class, ['required' => false]);
        $builder->add('email', TextType::class, ['required' => false]);
        $builder->add('auth', TextType::class, ['required' => false]);
        $builder->add('serverAddress', TextType::class, ['required' => false]);

        $builder->setDataMapper(new class implements DataMapperInterface {
            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?XRegistryAuth $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof XRegistryAuth) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $forms['username']->setData($data->getUsername());
                $forms['password']->setData($data->getPassword());
                $forms['email']->setData($data->getEmail());
                $forms['auth']->setData($data->getAuth());
                $forms['serverAddress']->setData($data->getServerAddress());
            }

            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?XRegistryAuth $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                $forms = \iterator_to_array($forms);
                $data = new XRegistryAuth(
                    (string) $forms['username']->getData(),
                    (string) $forms['password']->getData(),
                    (string) $forms['email']->getData(),
                    (string) $forms['auth']->getData(),
                    (string) $forms['serverAddress']->getData()
                );
            }
        });

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => XRegistryAuth::class,
            'empty_data' => null,
        ]);

        return $this;
    }
}
