<?php

declare(strict_types=1);

namespace Brainbits\DatabaseCommand\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function count;
use function sleep;
use function sprintf;

/**
 * Check if there are any tables
 */
class SchemaExistsCommand extends Command
{
    private const CODE_SUCCESS = 0;
    private const CODE_FAIL = 200;
    private const CODE_NO_TABLES = 255;

    private int $failCount = 0;

    public function __construct(
        private readonly Connection $connection,
        private readonly int $retryTime = 3,
        private readonly int $retryCount = 100,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('brainbits:database:schema-exists')
            ->setDescription('This command returns an error code of '.self::CODE_NO_TABLES.' if a schema does not exist.'); // phpcs:ignore
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if ($this->hasTables()) {
                return self::CODE_SUCCESS;
            }

            return self::CODE_NO_TABLES;
        } catch (Throwable $e) {
            return $this->handleError($input, $output, $e);
        }
    }

    protected function hasTables(): bool
    {
        $tables = $this->connection->createSchemaManager()->listTables();

        return count($tables) > 0;
    }

    protected function handleError(InputInterface $input, OutputInterface $output, Throwable $e): int
    {
        $this->failCount += 1;

        $output->writeln(sprintf('Database connection Fail #%d: %s', $this->failCount, $e->getMessage()));

        if ($this->failCount <= $this->retryCount) {
            return $this->retry($input, $output);
        }

        return self::CODE_FAIL;
    }

    protected function retry(InputInterface $input, OutputInterface $output): int
    {
        sleep($this->retryTime);

        return $this->execute($input, $output);
    }
}
