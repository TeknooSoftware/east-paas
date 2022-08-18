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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Normalizer;

use RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Job\JobUnit;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Cluster;

use function is_array;

/**
 * Symfony denormalizer dedicated to PaaS JobUnitInterface object.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class JobUnitDenormalizer implements DenormalizerInterface
{
    private DenormalizerInterface $denormalizer;

    public function __construct(
        private bool $hierarchicalNamespacesDefaultValue = false,
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
    public function denormalize($data, $class, $format = null, array $context = array()): JobUnit
    {
        if (!is_array($data) || JobUnitInterface::class !== $class) {
            throw new RuntimeException('Error, this object is not managed by this denormalizer');
        }

        $hierarchicalNSDefaultValue = $context['hierarchical_namespaces'] ?? $this->hierarchicalNamespacesDefaultValue;
        $jobId = $data['id'];
        $projectResume = $data['project'];
        $baseNamespace = $data['base_namespace'] ?? null;
        $hierarchicalNamespaces = $data['hierarchical_namespaces'] ?? $hierarchicalNSDefaultValue;
        $denormalizer = $this->denormalizer;
        $sourceRepository = $denormalizer->denormalize(
            $data['source_repository'],
            $data['source_repository']['@class'],
            $format,
            $context
        );

        if (!$sourceRepository instanceof SourceRepositoryInterface) {
            throw new RuntimeException('Bad denormalized source repository');
        }

        $imagesRegistry = $denormalizer->denormalize(
            $data['images_repository'],
            $data['images_repository']['@class'],
            $format,
            $context
        );
        if (!$imagesRegistry instanceof ImageRegistryInterface) {
            throw new RuntimeException('Bad denormalized image repository');
        }

        $environment = $denormalizer->denormalize(
            $data['environment'],
            Environment::class,
            $format,
            $context
        );

        if (!$environment instanceof Environment) {
            throw new RuntimeException('Bad denormalized environment');
        }

        $clusters = $denormalizer->denormalize($data['clusters'], Cluster::class . '[]', $format, $context);
        if (empty($clusters) || !is_array($clusters)) {
            throw new RuntimeException('Bad denormalized environment');
        }

        foreach ($clusters as $cluster) {
            if (!$cluster instanceof Cluster) {
                throw new RuntimeException('Bad denormalized cluster');
            }
        }

        $history = $denormalizer->denormalize($data['history'], History::class, $format, $context);
        if (!$history instanceof History) {
            throw new RuntimeException('Bad denormalized history');
        }

        $variables = ($data['variables'] ?? []) + ($context['variables'] ?? []);

        return new JobUnit(
            id: $jobId,
            projectResume: $projectResume,
            environment: $environment,
            baseNamespace: $baseNamespace,
            sourceRepository: $sourceRepository,
            imagesRegistry: $imagesRegistry,
            clusters: $clusters,
            variables: $variables,
            history: $history,
            extra: $data['extra'] ?? [],
            hierarchicalNamespaces: $hierarchicalNamespaces,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) && JobUnitInterface::class === $type;
    }
}
