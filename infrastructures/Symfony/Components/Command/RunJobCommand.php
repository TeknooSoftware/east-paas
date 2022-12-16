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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Command;

use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\FoundationBundle\Command\Client;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;

use function file_exists;
use function file_get_contents;
use function is_string;

/**
 * Symfony console command to run manually a deployment fron a job serialized as json object in a file, without
 * dedicated worker and any bus
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class RunJobCommand extends Command
{
    /**
     * @var string
     */
    private const FILE_ARGUMENT_NAME = 'file';

    public function __construct(
        string $name,
        string $description,
        private readonly ManagerInterface $manager,
        private readonly Client $client,
        private readonly RunJobInterface $runJob,
        private readonly MessageFactoryInterface $messageFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly DisplayHistoryHandler $displayHistoryHandler,
        private readonly DisplayResultHandler $displayResultHandler
    ) {
        $this->setDescription($description);

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument(self::FILE_ARGUMENT_NAME, InputArgument::REQUIRED, 'Filename');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument(self::FILE_ARGUMENT_NAME);
        if (!is_string($file)) {
            $output->writeln('Wrong filename');
            return 1;
        }

        $this->displayHistoryHandler->setOutput($output);
        $this->displayResultHandler->setOutput($output);

        if (file_exists($file)) {
            $file = file_get_contents($file);
        }

        $stream = $this->streamFactory->createStream((string) $file);
        $request = $this->messageFactory->createMessage('1.1');
        $request = $request->withBody($stream);

        $client = clone $this->client;
        $client->setOutput($output);

        $workPlan = [
            'request' => $request,
            'client' => $client,
            OutputInterface::class => $output,
        ];

        $this->manager->read($this->runJob);
        $this->manager->process($workPlan);

        $client->sendResponse(null, true);

        return $client->returnCode;
    }
}
