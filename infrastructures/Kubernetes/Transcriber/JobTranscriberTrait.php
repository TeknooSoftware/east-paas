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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\CompletionMode;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;

/**
 * Trait to factorise jobs' features transcribing
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait JobTranscriberTrait
{
    use CommonTrait;
    use PodsTranscriberTrait;

    private const VOLUME_SUFFIX = '-volume';
    private const SECRET_SUFFIX = '-secret';
    private const MAP_SUFFIX = '-map';

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     * @return array<string, mixed>
     */
    protected static function writeJobSpec(
        Job $job,
        Pod $pod,
        array $images,
        array $volumes,
        callable $prefixer,
        string $requireLabel,
        DefaultsBag $defaultsBag,
    ): array {
        $specs = [
            'metadata' => [],
            'spec' => [
                'completions' => $job->getCompletionsCount(),
                'completionMode' => match ($job->getCompletion()) {
                    CompletionMode::Common => 'NonIndexed',
                    CompletionMode::Indexed => 'Indexed',
                },
                'parallelism' => match ($job->isParallel()) {
                    true => $job->getCompletionsCount(),
                    false => 1,
                },
                'template' => [
                    'spec' => self::podTemplateSpecWriting(
                        pod: $pod,
                        images: $images,
                        volumes: $volumes,
                        prefixer: $prefixer,
                        requireLabel: $requireLabel,
                        defaultsBag: $defaultsBag,
                    )
                ]
            ],
        ];

        if (!empty($timeLimit = $job->getTimeLimit())) {
            $specs['spec']['activeDeadlineSeconds'] = $timeLimit;
        }

        if (null !== ($successCondition = $job->getSuccessCondition())) {
            $rules = [];

            foreach (['successExitCode', 'failureExistCode'] as $code) {
                if (!empty($successCondition->{$code})) {
                    $rule = [
                        'action' => match ($code) {
                            'successExitCode' => 'Ignore',
                            'failureExistCode' => 'FailJob',
                        },
                        'onExitCodes' => [
                            'operator' => 'In',
                            'values' => $successCondition->{$code},
                        ]
                    ];

                    if (!empty($successCondition->containerName)) {
                        $rule['onExitCodes']['containerName'] = $successCondition->containerName;
                    }

                    $rules[] = $rule;
                }
            }

            $specs['spec']['podFailurePolicy']['rules'] = $rules;
        }

        return $specs;
    }
}
