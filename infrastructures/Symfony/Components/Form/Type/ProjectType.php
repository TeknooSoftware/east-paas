<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\Project;
use Traversable;

use function array_map;
use function iterator_to_array;

/**
 * Symfony form to edit East PaaS Project
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ProjectType extends AbstractType
{
    /**
     * @param FormBuilderInterface<Project> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => true]);
        $builder->add('prefix', TextType::class, ['required' => false]);
        $builder->add('sourceRepository', GitRepositoryType::class, ['required' => true]);
        $builder->add('imagesRegistry', ImageRegistryType::class, ['required' => true]);

        $allowEditingOfLocked = $options['allowEditingOfLocked'] ?? false;
        $builder->add(
            'clusters',
            CollectionType::class,
            [
                'entry_type' => ClusterType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'entry_options' => [
                    'allowEditingOfLocked' => $allowEditingOfLocked,
                ],
                'prototype_options' => [
                    'allowEditingOfLocked' => $allowEditingOfLocked,
                ],
            ]
        );

        $builder->setDataMapper(new class () implements DataMapperInterface {
            /**
             * @param Traversable<string, FormInterface> $forms
             * @param ?Project $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof Project) {
                    return;
                }

                $visitors = array_map(
                    static fn (FormInterface $form): callable => $form->setData(...),
                    iterator_to_array($forms)
                );
                $data->visit($visitors);
            }

            /**
             * @param Traversable<string, FormInterface<Project>> $forms
             * @param ?Project $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                if (!$data instanceof Project) {
                    return;
                }

                $forms = iterator_to_array($forms);
                $data->setName($forms['name']->getData());
                $data->setPrefix((string) $forms['prefix']->getData());
                $data->setSourceRepository($forms['sourceRepository']->getData());
                $data->setImagesRegistry($forms['imagesRegistry']->getData());
                $data->setClusters($forms['clusters']->getData());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['allowEditingOfLocked']);
        $resolver->setAllowedTypes('allowEditingOfLocked', 'bool');

        $resolver->setDefaults([
            'data_class' => Project::class,
            'allowEditingOfLocked' => false,
        ]);
    }
}
