<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Traversable;

use function iterator_to_array;

/**
 * Symfony form to edit East PaaS ClusterCredentials
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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

        $builder->add('caCertificate', TextareaType::class, ['required' => false]);
        $builder->add('clientCertificate', TextareaType::class, ['required' => false]);
        $builder->add('clientKey', TextareaType::class, ['required' => false]);
        $builder->add('token', TextareaType::class, ['required' => false]);
        $builder->add('username', TextType::class, ['required' => false]);
        $builder->add('password', TextType::class, ['required' => false]);

        $builder->setDataMapper(new class implements DataMapperInterface {
            /**
             * @param Traversable<string, FormInterface<ClusterCredentials>> $forms
             * @param ?ClusterCredentials $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof ClusterCredentials) {
                    return;
                }

                $forms = iterator_to_array($forms);
                $forms['caCertificate']->setData($data->getCaCertificate());
                $forms['clientCertificate']->setData($data->getClientCertificate());
                $forms['clientKey']->setData($data->getClientKey());
                $forms['token']->setData($data->getToken());
                $forms['username']->setData($data->getUsername());
                $forms['password']->setData($data->getPassword());
            }

            /**
             * @param Traversable<string, FormInterface<ClusterCredentials>> $forms
             * @param ?ClusterCredentials $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                $forms = iterator_to_array($forms);
                $data = new ClusterCredentials(
                    caCertificate: (string) $forms['caCertificate']->getData(),
                    clientCertificate: (string) $forms['clientCertificate']->getData(),
                    clientKey: (string) $forms['clientKey']->getData(),
                    token: (string) $forms['token']->getData(),
                    username: (string) $forms['username']->getData(),
                    password: (string) $forms['password']->getData()
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
