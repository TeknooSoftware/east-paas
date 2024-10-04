<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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

/**
 * Symfony denormalizer dedicated to PaaS JobUnitInterface object.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class JobUnitDenormalizer implements DenormalizerInterface
{
    private DenormalizerInterface $denormalizer;

    public function __construct(
        private readonly bool $hierarchicalNamespacesDefaultValue = false,
    ) {
    }

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

        $hierarchicalNSDefaultValue = $context['hierarchical_namespaces'] ?? $this->hierarchicalNamespacesDefaultValue;
        $jobId = $data['id'];
        $projectResume = $data['project'];
        $prefix = $data['prefix'] ?? null;
        $denormalizer = $this->denormalizer;
        $sourceRepository = $denormalizer->denormalize(
            $data['source_repository'],
            $data['source_repository']['@class'],
            $format,
            $context
        );

        if (!$sourceRepository instanceof SourceRepositoryInterface) {
            throw new NotSupportedException('Bad denormalized source repository');
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

        $environment = $denormalizer->denormalize(
            $data['environment'],
            Environment::class,
            $format,
            $context
        );

        if (!$environment instanceof Environment) {
            throw new NotSupportedException('Bad denormalized environment');
        }

        $clusters = $denormalizer->denormalize($data['clusters'], Cluster::class . '[]', $format, $context);
        if (empty($clusters) || !is_array($clusters)) {
            throw new NotSupportedException('Bad denormalized environment');
        }

        foreach ($clusters as $cluster) {
            if (!$cluster instanceof Cluster) {
                throw new NotSupportedException('Bad denormalized cluster');
            }
        }

        $history = $denormalizer->denormalize($data['history'], History::class, $format, $context);
        if (!$history instanceof History) {
            throw new NotSupportedException('Bad denormalized history');
        }

        $variables = ($data['variables'] ?? []) + ($context['variables'] ?? []);

        $quotas = [];
        foreach ($data['quotas'] ?? [] as $quota) {
            $quotas[] = AccountQuota::create($quota);
        }

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
            extra: $data['extra'] ?? [],
            defaults: $data['defaults'] ?? [],
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
