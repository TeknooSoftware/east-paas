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
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Contracts\Form\FormInterface as PaasFormInterface;

class ClusterType extends AbstractType
{
    /**
     * @param FormBuilderInterface<Cluster> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => true]);
        $builder->add('address', TextType::class, ['required' => true]);
        $builder->add('environment', EnvironmentType::class, ['required' => true]);
        $builder->add('identity', ClusterCredentialsType::class, ['required' => true]);

        $builder->setDataMapper(new class implements DataMapperInterface {
            /**
             * @param \Traversable<string, PaasFormInterface> $forms
             * @param ?Cluster $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof Cluster) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $data->injectDataInto($forms);
            }

            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?Cluster $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                if (!$data instanceof Cluster) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $data->setName($forms['name']->getData());
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
