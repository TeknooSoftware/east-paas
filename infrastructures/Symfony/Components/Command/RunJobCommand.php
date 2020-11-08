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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Command;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class RunJobCommand extends Command
{
    private const FILE_ARGUMENT_NAME = 'file';

    private ManagerInterface $manager;

    private RunJobInterface $runJob;

    private ServerRequestFactoryInterface $serverRequestFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        string $name,
        string $description,
        ManagerInterface $manager,
        RunJobInterface $runJob,
        ServerRequestFactoryInterface $serverRequestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->setDescription($description);
        $this->manager = $manager;
        $this->runJob = $runJob;
        $this->serverRequestFactory = $serverRequestFactory;
        $this->streamFactory = $streamFactory;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument(static::FILE_ARGUMENT_NAME, InputArgument::REQUIRED, 'Filename');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument(static::FILE_ARGUMENT_NAME);
        if (!\is_string($file)) {
            $output->writeln('Wrong filename'); //todo
            return 1;
        }

        if (\file_exists($file)) {
            $file = \file_get_contents($file);
        }

        $stream = $this->streamFactory->createStream((string) $file);
        $request = $this->serverRequestFactory->createServerRequest(
            'PUT',
            \str_replace(':', '.', $this->getName())
        );
        $request = $request->withBody($stream);

        $client = new class ($output) implements ClientInterface {
            private OutputInterface $output;

            private ?ResponseInterface $response = null;

            public int $returnCode = 0;

            public function __construct(OutputInterface $output)
            {
                $this->output = $output;
            }

            private function getErrorOutput(): OutputInterface
            {
                if ($this->output instanceof ConsoleOutputInterface) {
                    return $this->output->getErrorOutput();
                }

                return $this->output;
            }

            public function updateResponse(callable $modifier): ClientInterface
            {
                $modifier($this, $this->response);

                return $this;
            }

            public function acceptResponse(ResponseInterface $response): ClientInterface
            {
                $this->response = $response;

                return $this;
            }

            public function sendResponse(ResponseInterface $response = null, bool $silently = false): ClientInterface
            {
                if ($response instanceof ResponseInterface) {
                    $this->acceptResponse($response);
                }

                if (true === $silently && !$this->response instanceof ResponseInterface) {
                    return $this;
                }

                if ($this->response instanceof ResponseInterface) {
                    $this->output->writeln((string) $this->response->getBody());
                }

                $this->response = null;

                return $this;
            }

            public function errorInRequest(\Throwable $throwable): ClientInterface
            {
                $this->getErrorOutput()->writeln($throwable->getMessage());

                $this->returnCode = 1;

                return $this;
            }
        };

        $workPlan = [
            'request' => $request,
            'client' => $client,
        ];

        $this->manager->read($this->runJob);
        $this->manager->process($workPlan);

        return $client->returnCode;
    }
}
