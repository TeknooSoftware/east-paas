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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\GitRepository;
use Traversable;

use function iterator_to_array;

/**
 * Symfony form to edit East PaaS GitRepository
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
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

        $builder->setDataMapper(new class () implements DataMapperInterface {
            /**
             * @param Traversable<string, FormInterface<GitRepository>> $forms
             * @param ?GitRepository $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof GitRepository) {
                    return;
                }

                $forms = iterator_to_array($forms);
                $forms['pullUrl']->setData($data->getPullUrl());
                $forms['defaultBranch']->setData($data->getDefaultBranch());
                $forms['identity']->setData($data->getIdentity());
            }

            /**
             * @param Traversable<string, FormInterface<GitRepository>> $forms
             * @param GitRepository $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                $forms = iterator_to_array($forms);
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
