<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
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
