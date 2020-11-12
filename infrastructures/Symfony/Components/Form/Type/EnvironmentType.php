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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\Environment;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class EnvironmentType extends AbstractType
{
    /**
     * @param FormBuilderInterface<Environment> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => true, 'label' => 'Environment']);

        $builder->setDataMapper(new class implements DataMapperInterface {
            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?Environment $data
             */
            public function mapDataToForms($data, $forms): void
            {
                if (!$data instanceof Environment) {
                    return;
                }

                $forms = \iterator_to_array($forms);
                $forms['name']->setData($data->getName());
            }

            /**
             * @param \Traversable<string, FormInterface> $forms
             * @param ?Environment $data
             */
            public function mapFormsToData($forms, &$data): void
            {
                $forms = \iterator_to_array($forms);
                $data = new Environment(
                    (string) $forms['name']->getData()
                );
            }
        });

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Environment::class,
        ]);

        return $this;
    }
}
