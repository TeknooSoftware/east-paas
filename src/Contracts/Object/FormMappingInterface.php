<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Object;

use Teknoo\East\Paas\Contracts\Form\FormInterface;

interface FormMappingInterface
{
    /**
     * @param array<string, FormInterface> $forms
     */
    public function injectDataInto($forms): FormMappingInterface;
}
