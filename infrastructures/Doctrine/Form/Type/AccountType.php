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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Doctrine\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Common\Object\User;
use Teknoo\East\Paas\Object\Account;
use Traversable;

use function array_map;
use function iterator_to_array;

/**
 * Symfony Form dedicated to manage translatable Account Object in a Symfony Website.
 * This form is placed in this namespace to use the good Symfony Form Doctrine Type to link an account to an user.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class AccountType extends AbstractType
{
    /**
     * @param FormBuilderInterface<Account> $builder
     * @param array<string, string|bool> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => true]);

        $builder->add(
            'namespace',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'readonly' => !empty($options['namespace_in_readonly']),
                ],
            ]
        );

        $builder->add(
            'prefix_namespace',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'readonly' => !empty($options['namespace_in_readonly']),
                ],
            ]
        );

        $builder->add(
            'use_hierarchical_namespaces',
            CheckboxType::class,
            [
                'required' => false,
                'attr' => [
                    'readonly' => !empty($options['namespace_in_readonly']),
                ],
            ]
        );

        $builder->add(
            'users',
            (string) $options['doctrine_type'],
            [
                'class' => User::class,
                'required' => true,
                'multiple' => true,
            ]
        );

        $builder->setDataMapper(dataMapper: new class implements DataMapperInterface {
            /**
             * @param Traversable<string, FormInterface> $forms
             * @param ?Account $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof Account) {
                    return;
                }

                $visitors = array_map(
                    fn (FormInterface $form): callable => $form->setData(...),
                    iterator_to_array($forms)
                );
                $data->visit($visitors);
            }

            /**
             * @param Traversable<string, FormInterface<AccountType>> $forms
             * @param ?Account $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                if (!$data instanceof Account) {
                    return;
                }

                $forms = iterator_to_array($forms);
                $data->setName($forms['name']->getData());
                $data->setNamespace($forms['namespace']->getData());
                $data->setPrefixNamespace($forms['prefix_namespace']->getData());
                $data->setUseHierarchicalNamespaces($forms['use_hierarchical_namespaces']->getData());
                $data->setUsers($forms['users']->getData());
            }
        });

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Account::class,
            'doctrine_type' => '',
            'namespace_in_readonly' => false,
        ]);

        return $this;
    }
}
