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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\SshIdentity;
use Traversable;

use function iterator_to_array;

/**
 * Symfony form to edit East PaaS SshIdentity
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SshIdentityType extends AbstractType
{
    /**
     * @param FormBuilderInterface<SshIdentity> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, ['required' => false, 'label' => 'Identity name']);
        $builder->add('privateKey', TextareaType::class, ['required' => false]);
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
            new class () implements DataMapperInterface {
                private string $currentPrivateKey = '';

                /**
                 * @param Traversable<string, FormInterface<SshIdentity>> $forms
                 * @param ?SshIdentity $data
                 */
                public function mapDataToForms($data, $forms): void
                {
                    if (!$data instanceof SshIdentity) {
                        return;
                    }

                    $forms = iterator_to_array($forms);
                    $forms['name']->setData($data->getName());
                    $this->currentPrivateKey = $data->getPrivateKey();
                }

                /**
                 * @param Traversable<string, FormInterface<SshIdentity>> $forms
                 * @param SshIdentity $data
                 */
                public function mapFormsToData($forms, &$data): void
                {
                    $forms = iterator_to_array($forms);
                    $toClear = !empty($forms['clear']->getData());

                    $pkey = (string) $forms['privateKey']->getData();
                    if (!$toClear && empty($pkey)) {
                        $pkey = $this->currentPrivateKey;
                    }


                    $data = new SshIdentity(
                        (string) $forms['name']->getData(),
                        $pkey,
                    );
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => SshIdentity::class,
            'empty_data' => null,
        ]);
    }
}
