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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ClusterCredentialsType extends AbstractType
{
    /**
     * @param FormBuilderInterface<ClusterCredentials> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('serverCertificate', TextareaType::class, ['required' => false]);
        $builder->add('privateKey', TextareaType::class, ['required' => false]);
        $builder->add('publicKey', TextareaType::class, ['required' => false]);
        $builder->add('username', TextType::class, ['required' => false]);
        $builder->add('password', TextType::class, ['required' => false]);

        $builder->setDataMapper(new class implements DataMapperInterface {
            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?ClusterCredentials $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof ClusterCredentials) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $forms['serverCertificate']->setData($data->getServerCertificate());
                $forms['privateKey']->setData($data->getPrivateKey());
                $forms['publicKey']->setData($data->getPublicKey());
                $forms['username']->setData($data->getPrivateKey());
                $forms['password']->setData($data->getPublicKey());
            }

            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?ClusterCredentials $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                $forms = \iterator_to_array($forms);
                $data = new ClusterCredentials(
                    (string) $forms['serverCertificate']->getData(),
                    (string) $forms['privateKey']->getData(),
                    (string) $forms['publicKey']->getData(),
                    (string) $forms['username']->getData(),
                    (string) $forms['password']->getData()
                );
            }
        });

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => ClusterCredentials::class,
            'empty_data' => null,
        ]);

        return $this;
    }
}
