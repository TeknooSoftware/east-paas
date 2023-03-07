<?php

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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Normalizer;

use Teknoo\East\FoundationBundle\Normalizer\EastNormalizer as BaseEastNormalizer;
use Teknoo\East\Paas\Object\Job;

use function is_array;

/**
 * Extension of the east normalizer to add, without overwrite them, some fields on a normalized object, from the
 * context, defined at the key `add`.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class EastNormalizer extends BaseEastNormalizer
{
    /**
     * @param array<string, mixed> $context
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $data = parent::normalize($object, $format, $context);

        if (
            is_array($data)
            && isset($context['add'])
            && is_array($context['add'])
            && $object instanceof Job
        ) {
            $data = $context['add'] + $data;
        }

        return $data;
    }
}
