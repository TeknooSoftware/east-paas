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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Serializing;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Normalizer implements NormalizerInterface
{
    private SymfonyNormalizerInterface $normalizer;

    public function __construct(SymfonyNormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @param mixed $object
     */
    public function normalize(
        $object,
        PromiseInterface $promise,
        string $format = null,
        array $context = []
    ): NormalizerInterface {
        try {
            $promise->success($this->normalizer->normalize($object, $format, $context));
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
