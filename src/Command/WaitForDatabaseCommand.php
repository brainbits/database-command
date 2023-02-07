<?php

declare(strict_types=1);

namespace Brainbits\DatabaseCommand\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function sleep;
use function sprintf;

/**
 * Check if there are any tables
 */
class WaitForDatabaseCommand extends Command
{
    private const RETRY_SECONDS = 3;
    private const RETRY_COUNT = 100;
    private const CODE_SUCCESS = 0;
    private const CODE_FAIL = 200;

    private int $failCount = 0;

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('brainbits:database:wait-for-database')
            ->setDescription('This command waits until the database server is ready.')
            ->addOption('retry-seconds', null, InputOption::VALUE_REQUIRED, 'Retry time in seconds')
            ->addOption('retry-count', null, InputOption::VALUE_REQUIRED, 'Retry count');
    }

    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $retrySeconds = (int) ($input->getOption('retry-seconds') ?? self::RETRY_SECONDS); // @phpstan-ignore-line
        $retryCount = (int) ($input->getOption('retry-count') ?? self::RETRY_COUNT); // @phpstan-ignore-line

        do {
            if ($this->isDatabaseReady($output, $retrySeconds)) {
                return self::CODE_SUCCESS;
            }
        } while (!$this->isRetryLimitReached($retryCount));

        return self::CODE_FAIL;
    }

    protected function isDatabaseReady(OutputInterface $output, int $retrySeconds): bool
    {
        try {
            $this->connection->createSchemaManager()->listTables();
        } catch (Throwable $exception) {
            $this->handleError($output, $exception, $retrySeconds);

            return false;
        }

        return true;
    }

    protected function isRetryLimitReached(int $retryCount): bool
    {
        return $this->failCount > $retryCount;
    }

    protected function handleError(OutputInterface $output, Throwable $exception, int $retrySeconds): void
    {
        ++$this->failCount;

        $output->writeln(sprintf('Database connection fail #%d: %s', $this->failCount, $exception->getMessage()));

        sleep($retrySeconds);
    }
}
