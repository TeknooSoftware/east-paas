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
use Teknoo\East\Paas\Object\GitRepository;

class GitRepositoryType extends AbstractType
{
    /**
     * @param FormBuilderInterface<GitRepository> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('pullUrl', TextType::class, ['required' => true]);
        $builder->add('defaultBranch', TextType::class, ['required' => false]);
        $builder->add('identity', SshIdentityType::class, ['required' => true]);

        $builder->setDataMapper(new class implements DataMapperInterface {
            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?GitRepository $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof GitRepository) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $forms['pullUrl']->setData($data->getPullUrl());
                $forms['defaultBranch']->setData($data->getDefaultBranch());
                $forms['identity']->setData($data->getIdentity());
            }

            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?GitRepository $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                $forms = \iterator_to_array($forms);
                $data = new GitRepository(
                    (string) $forms['pullUrl']->getData(),
                    (string) $forms['defaultBranch']->getData(),
                    $forms['identity']->getData()
                );
            }
        });

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => GitRepository::class,
            'empty_data' => null,
        ]);

        return $this;
    }
}
