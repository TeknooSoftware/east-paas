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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

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
use Teknoo\East\Paas\Contracts\Form\FormInterface as PaasFormInterface;
use Traversable;

use function iterator_to_array;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ClusterType extends AbstractType
{
    /**
     * @param array<string, string> $clustersTypes
     */
    public function __construct(
        private array $clustersTypes,
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
             * @param Traversable<string, PaasFormInterface> $forms
             * @param ?Cluster $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof Cluster) {
                    return;
                }

                $forms = iterator_to_array($forms);
                $data->injectDataInto($forms);
            }

            /**
             * @param Traversable<string, FormInterface> $forms
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
