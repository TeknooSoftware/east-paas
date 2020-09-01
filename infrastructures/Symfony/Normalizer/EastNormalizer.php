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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Normalizer;

use Teknoo\East\FoundationBundle\Normalizer\EastNormalizer as BaseEastNormalizer;
use Teknoo\East\Paas\Object\Job;

class EastNormalizer extends BaseEastNormalizer
{
    /**
     * @param array<string, mixed> $context
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = parent::normalize($object, $format, $context);

        if (
            \is_array($data)
            && isset($context['add'])
            && \is_array($context['add'])
            && $object instanceof Job
        ) {
            $data = \array_merge($data, $context['add']);
        }

        return $data;
    }
}
