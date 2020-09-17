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

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Object\History;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class HistoryDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     * @return History
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (!\is_array($data) || History::class !== $class) {
            throw new \RuntimeException('Error, this object is not managed by this denormalizer');
        }

        $previous = null;
        if (isset($data['previous'])) {
            $previous = $this->denormalize($data['previous'], History::class, $format, $context);
        }

        $message = '';
        if (isset($data['message'])) {
            $message = $data['message'];
        }

        $date = new \DateTime();
        if (isset($data['date'])) {
            $date = \DateTime::createFromFormat(History::DATE_FORMAT, $data['date']);
        }

        if (!$date instanceof \DateTimeInterface) {
            throw new \RuntimeException('Bad denormalized date');
        }

        $isFinal = !empty($data['is_final']);

        $extra = [];
        if (isset($data['extra'])) {
            $extra = $data['extra'];
        }

        return new History($previous, $message, $date, $isFinal, $extra);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return \is_array($data) && History::class === $type;
    }
}
