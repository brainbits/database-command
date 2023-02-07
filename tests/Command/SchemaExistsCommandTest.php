<?php

declare(strict_types=1);

namespace Brainbits\Tests\DatabaseCommand\Command;

use Brainbits\DatabaseCommand\Command\SchemaExistsCommand;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(SchemaExistsCommand::class)]
class SchemaExistsCommandTest extends TestCase
{
    public function testTablesPresent(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('listTables')
            ->willReturn(['x']);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $command = new SchemaExistsCommand($connection, 0, 0);

        $tester = new CommandTester($command);
        $result = $tester->execute([], []);

        $this->assertSame(0, $result);
    }

    public function testTablesMissing(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('listTables')
            ->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $command = new SchemaExistsCommand($connection, 0, 0);

        $tester = new CommandTester($command);
        $result = $tester->execute([], []);

        $this->assertSame(255, $result);
    }

    public function testConnectionFailure(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('createSchemaManager')
            ->willThrowException(new RuntimeException('Connection failed.'));

        $command = new SchemaExistsCommand($connection, 0, 0);

        $tester = new CommandTester($command);
        $result = $tester->execute([], []);

        $this->assertSame(200, $result);
    }

    public function testRetry(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->exactly(2))
            ->method('createSchemaManager')
            ->willThrowException(new RuntimeException('Connection failed.'));

        $command = new SchemaExistsCommand($connection, 0, 1);

        $tester = new CommandTester($command);
        $result = $tester->execute([], []);

        $this->assertSame(200, $result);
    }
}
