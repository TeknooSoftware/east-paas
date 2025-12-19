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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Teknoo\East\Paas\Object\Cluster;
use Traversable;

use function array_map;
use function is_array;
use function is_bool;
use function iterator_to_array;

/**
 * Symfony form to edit East PaaS Cluster
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ClusterType extends AbstractType
{
    /**
     * @param array<string, string> $clustersTypes
     */
    public function __construct(
        private readonly array $clustersTypes,
    ) {
    }

    /**
     * @param FormBuilderInterface<Cluster> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $allowEditingOfLocked = $options['allowEditingOfLocked'] ?? false;

        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => true]);

        $builder->add('namespace', TextType::class, ['required' => true]);

        $builder->add('useHierarchicalNamespaces', CheckboxType::class, ['required' => false]);

        $builder->add('type', ChoiceType::class, ['required' => true, 'choices' => $this->clustersTypes]);

        $builder->add(
            'address',
            TextType::class,
            [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Regex('/^https:\/\/[a-zA-Z0-9-_\.]+/iS')
                ],
            ]
        );
        $builder->add('environment', EnvironmentType::class, ['required' => true]);
        $builder->add('identity', ClusterCredentialsType::class, ['required' => true]);
        $builder->add(
            'locked',
            CheckboxType::class,
            [
                'required' => false,
                'false_values' => [
                    null,
                    0,
                    false,
                    '',
                ],
            ],
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            static function (FormEvent $formEvent) use ($allowEditingOfLocked): void {
                $form = $formEvent->getForm();
                $data = $formEvent->getData();

                if (
                    $allowEditingOfLocked
                    || !$data instanceof Cluster
                    || true !== $data->isLocked()
                ) {
                    return;
                }

                foreach ($form as $children) {
                    /** @var FormInterface $children */
                    $config = $children->getConfig();
                    $options = $config->getOptions();
                    if (!isset($options['attr']) || is_array($options['attr'])) {
                        $options['attr']['readonly'] = true;
                    }

                    if ('locked' !== $children->getName() || !empty($allowEditingOfLocked)) {
                        $value = $children->getData();
                        if (empty($value) && !is_bool($value)) {
                            $options['constraints'][] = new Blank();
                        } else {
                            $options['constraints'][] = new EqualTo($value);
                        }
                    }

                    $typeClass = ($config->getType()->getInnerType())::class;

                    $form->add(
                        child: $children->getName(),
                        type: $typeClass,
                        options: $options
                    );
                }
            }
        );

        $builder->setDataMapper(new readonly class ($allowEditingOfLocked) implements DataMapperInterface {
            public function __construct(
                private bool $allowEditingOfLocked,
            ) {
            }

            /**
             * @param Traversable<string, FormInterface> $forms
             * @param ?Cluster $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof Cluster) {
                    return;
                }

                $visitors = array_map(
                    static fn (FormInterface $form): callable => $form->setData(...),
                    iterator_to_array($forms)
                );

                $data->visit($visitors);
            }

            /**
             * @param Traversable<string, FormInterface<Cluster>> $forms
             * @param ?Cluster $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                if (!$data instanceof Cluster) {
                    return;
                }

                $forms = iterator_to_array($forms);

                if ($this->allowEditingOfLocked || true !== $data->isLocked()) {
                    $data->setName((string) $forms['name']->getData());
                    $data->setNamespace((string) $forms['namespace']->getData());
                    $data->useHierarchicalNamespaces(!empty($forms['useHierarchicalNamespaces']->getData()));
                    $data->setType((string) $forms['type']->getData());
                    $data->setAddress((string) $forms['address']->getData());
                    $data->setEnvironment($forms['environment']->getData());
                    $data->setIdentity($forms['identity']->getData());

                    if ($this->allowEditingOfLocked) {
                        $data->setLocked(!empty($forms['locked']->getData()));
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['allowEditingOfLocked']);
        $resolver->setAllowedTypes('allowEditingOfLocked', ['bool']);

        $resolver->setDefaults([
            'data_class' => Cluster::class,
            'allowEditingOfLocked' => false,
        ]);
    }
}
