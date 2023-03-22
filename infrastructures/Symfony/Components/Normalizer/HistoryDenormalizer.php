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

use DateTime;
use DateTimeInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\Exception\NotSupportedException;
use Teknoo\East\Paas\Object\History;

use function is_array;

/**
 * Symfony denormalizer dedicated to PaaS History object.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class HistoryDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function denormalize($data, $class, $format = null, array $context = []): History
    {
        if (!is_array($data) || History::class !== $class) {
            throw new NotSupportedException('Error, this object is not managed by this denormalizer');
        }

        $previous = null;
        if (isset($data['previous'])) {
            $previous = $this->denormalize($data['previous'], History::class, $format, $context);
        }

        $date = new DateTime();
        if (isset($data['date'])) {
            $date = DateTime::createFromFormat(History::DATE_FORMAT, $data['date']);
        }

        if (!$date instanceof DateTimeInterface) {
            throw new NotSupportedException('Bad denormalized date');
        }

        return new History(
            previous: $previous,
            message: $data['message'] ?? '',
            date: $date,
            isFinal: !empty($data['is_final']),
            extra: $data['extra'] ?? [],
            serialNumber: $data['serial_number'] ?? 0,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) && History::class === $type;
    }
}
