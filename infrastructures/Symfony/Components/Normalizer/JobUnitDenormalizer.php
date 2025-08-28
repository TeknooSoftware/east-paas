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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Normalizer\Exception\NotSupportedException;
use Teknoo\East\Paas\Job\JobUnit;
use Teknoo\East\Paas\Object\AccountQuota;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;

use function is_array;
use function is_iterable;
use function is_scalar;
use function is_string;

/**
 * Symfony denormalizer dedicated to PaaS JobUnitInterface object.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class JobUnitDenormalizer implements DenormalizerInterface
{
    private DenormalizerInterface $denormalizer;

    public function setDenormalizer(DenormalizerInterface $denormalizer): self
    {
        $this->denormalizer = $denormalizer;

        return $this;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize($data, $class, $format = null, array $context = []): JobUnit
    {
        if (!is_array($data) || JobUnitInterface::class !== $class) {
            throw new NotSupportedException('Error, this object is not managed by this denormalizer');
        }

        if (!isset($data['id']) || !is_scalar($data['id'])) {
            throw new NotSupportedException('Wrong id format for this denormalizer, must be a string');
        }

        $jobId = (string) $data['id'];

        if (
            !isset($data['project'])
            || !is_array($data['project'])
            || !isset($data['project']['id'])
            || !is_string($data['project']['id'])
            || !isset($data['project']['name'])
            || !is_string($data['project']['name'])
        ) {
            throw new NotSupportedException(
                'Wrong project format for this denormalizer, must be an array{id: string, name: string}'
            );
        }

        $projectResume = $data['project'];

        $prefix = $data['prefix'] ?? null;
        if (null !== $prefix && !is_string($prefix)) {
            throw new NotSupportedException('Wrong prefix format for this denormalizer, must be a string');
        }

        $denormalizer = $this->denormalizer;

        if (
            empty($data['source_repository'])
            || !is_array($data['source_repository'])
            || empty($data['source_repository']['@class'])
            || !is_string($data['source_repository']['@class'])
        ) {
            throw new NotSupportedException('Bad denormalized source repository');
        }

        $sourceRepository = $denormalizer->denormalize(
            $data['source_repository'],
            $data['source_repository']['@class'],
            $format,
            $context
        );

        if (!$sourceRepository instanceof SourceRepositoryInterface) {
            throw new NotSupportedException('Bad denormalized source repository');
        }

        if (
            empty($data['images_repository'])
            || !is_array($data['images_repository'])
            || empty($data['images_repository']['@class'])
            || !is_string($data['images_repository']['@class'])
        ) {
            throw new NotSupportedException('Bad denormalized image repository');
        }

        $imagesRegistry = $denormalizer->denormalize(
            $data['images_repository'],
            $data['images_repository']['@class'],
            $format,
            $context
        );
        if (!$imagesRegistry instanceof ImageRegistryInterface) {
            throw new NotSupportedException('Bad denormalized image repository');
        }

        if (
            empty($data['environment'])
            || !is_array($data['environment'])
        ) {
            throw new NotSupportedException('Bad denormalized environment');
        }

        $environment = $denormalizer->denormalize(
            $data['environment'],
            Environment::class,
            $format,
            $context
        );

        if (!$environment instanceof Environment) {
            throw new NotSupportedException('Bad denormalized environment');
        }

        $clusters = $denormalizer->denormalize($data['clusters'] ?? [], Cluster::class . '[]', $format, $context);
        if (empty($clusters) || !is_array($clusters)) {
            throw new NotSupportedException('Bad denormalized clusters');
        }

        foreach ($clusters as $cluster) {
            if (!$cluster instanceof Cluster) {
                throw new NotSupportedException('Bad denormalized cluster');
            }
        }

        /** @var array<Cluster> $clusters */

        $history = $denormalizer->denormalize($data['history'] ?? [], History::class, $format, $context);
        if (!$history instanceof History) {
            throw new NotSupportedException('Bad denormalized history');
        }

        if (empty($data['variables']) || !is_array($data['variables'])) {
            $data['variables'] = [];
        }

        if (empty($context['variables']) || !is_array($context['variables'])) {
            $context['variables'] = [];
        }

        /** @var array<string, string> $variables */
        $variables = $data['variables'] + $context['variables'];

        $dQuotas = $data['quotas'] ?? [];
        if (!is_iterable($dQuotas)) {
            throw new NotSupportedException('Wrong quotas format, must be an array or an iterable');
        }

        $quotas = [];
        foreach ($dQuotas as $quota) {
            /** @var array{category: string, type: string, capacity: string, requires: string} $quota */
            $quotas[] = AccountQuota::create($quota);
        }

        $extra = $data['extra'] ?? [];
        if (!is_array($extra)) {
            throw new NotSupportedException('Wrong extra format, must be an array');
        }
        /** @var array<string, mixed> $extra */

        $defaults = $data['defaults'] ?? [];
        if (!is_array($defaults)) {
            throw new NotSupportedException('Wrong defaults format, must be an array');
        }
        /** @var array<string, mixed> $defaults */

        return new JobUnit(
            id: $jobId,
            projectResume: $projectResume,
            environment: $environment,
            prefix: $prefix,
            sourceRepository: $sourceRepository,
            imagesRegistry: $imagesRegistry,
            clusters: $clusters,
            variables: $variables,
            history: $history,
            extra: $extra,
            defaults: $defaults,
            quotas: $quotas,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_array($data) && JobUnitInterface::class === $type;
    }

    /**
     * @return array<class-string|'*'|'object'|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false,
        ];
    }
}
