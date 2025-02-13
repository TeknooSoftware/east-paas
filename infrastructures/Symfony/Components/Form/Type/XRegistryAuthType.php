<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Traversable;

use function iterator_to_array;

/**
 * Symfony form to edit East PaaS XRegistryAuth
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
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
        $builder->add('auth', TextType::class, ['required' => false]);
        $builder->add(
            'clear',
            CheckboxType::class,
            [
                'required' => false,
                'mapped' => false,
                'false_values' => [
                    null,
                    0,
                    false,
                    '',
                ],
            ],
        );

        $builder->setDataMapper(
            new class implements DataMapperInterface {
                private string $currentPassword = '';

                /**
                 * @param Traversable<string, FormInterface<XRegistryAuth>> $forms
                 * @param ?XRegistryAuth $data
                 */
                public function mapDataToForms($data, $forms): void
                {
                    if (!$data instanceof XRegistryAuth) {
                        return;
                    }

                    $forms = iterator_to_array($forms);
                    $forms['username']->setData($data->getUsername());
                    $this->currentPassword = $data->getPassword();
                    $forms['auth']->setData($data->getAuth());
                }

                /**
                 * @param Traversable<string, FormInterface<XRegistryAuth>> $forms
                 * @param ?XRegistryAuth $data
                 */
                public function mapFormsToData($forms, &$data): void
                {
                    $forms = iterator_to_array($forms);
                    $toClear = !empty($forms['clear']->getData());

                    $password = (string) $forms['password']->getData();
                    if (!$toClear && empty($password)) {
                        $password = $this->currentPassword;
                    }

                    $data = new XRegistryAuth(
                        username: (string) $forms['username']->getData(),
                        password: $password,
                        email: '',
                        auth: (string) $forms['auth']->getData(),
                        serverAddress: ''
                    );
                }
            }
        );

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
