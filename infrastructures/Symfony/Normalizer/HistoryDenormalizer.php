<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Object\History;

class HistoryDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     * @return History
     */
    public function denormalize($data, string $class, string $format = null, array $context = array())
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

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return \is_array($data) && History::class === $type;
    }
}
