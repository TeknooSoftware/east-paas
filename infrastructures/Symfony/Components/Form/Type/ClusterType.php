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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Teknoo\East\Paas\Object\Cluster;
use Traversable;

use function array_map;
use function iterator_to_array;

/**
 * Symfony form to edit East PaaS Cluster
 *
 * @license     http://teknoo.software/license/mit         MIT License
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
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => true]);
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

        $builder->setDataMapper(new class implements DataMapperInterface {
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
                    static fn(FormInterface $form): callable => $form->setData(...),
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
                $data->setName($forms['name']->getData());
                $data->setType($forms['type']->getData());
                $data->setAddress($forms['address']->getData());
                $data->setEnvironment($forms['environment']->getData());
                $data->setIdentity($forms['identity']->getData());
            }
        });

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Cluster::class,
        ]);

        return $this;
    }
}
