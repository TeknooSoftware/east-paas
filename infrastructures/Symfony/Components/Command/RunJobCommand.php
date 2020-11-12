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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Command;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\FoundationBundle\Command\Client;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class RunJobCommand extends Command
{
    private const FILE_ARGUMENT_NAME = 'file';

    private ManagerInterface $manager;

    private Client $client;

    private RunJobInterface $runJob;

    private ServerRequestFactoryInterface $serverRequestFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        string $name,
        string $description,
        ManagerInterface $manager,
        Client $client,
        RunJobInterface $runJob,
        ServerRequestFactoryInterface $serverRequestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->setDescription($description);
        $this->manager = $manager;
        $this->client = $client;
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
            \str_replace(':', '.', (string) $this->getName())
        );
        $request = $request->withBody($stream);

        $client = clone $this->client;
        $client->setOutput($output);

        $workPlan = [
            'request' => $request,
            'client' => $client,
        ];

        $this->manager->read($this->runJob);
        $this->manager->process($workPlan);

        $client->sendResponse(null, true);

        return $client->returnCode;
    }
}
