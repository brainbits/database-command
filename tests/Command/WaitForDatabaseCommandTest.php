<?php

declare(strict_types=1);

namespace Brainbits\Tests\DatabaseCommand\Command;

use Brainbits\DatabaseCommand\Command\WaitForDatabaseCommand;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function preg_match_all;

#[CoversClass(WaitForDatabaseCommand::class)]
class WaitForDatabaseCommandTest extends TestCase
{
    public function testConstruction(): void
    {
        $connection = $this->createMock(Connection::class);
        $command = new WaitForDatabaseCommand($connection);

        /** @phpstan-ignore method.alreadyNarrowedType */
        $this->assertInstanceOf(Command::class, $command);
    }

    public function testDatabaseImmediatlyAvailable(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('listTables')
            ->willReturn(['x']);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $command = new WaitForDatabaseCommand($connection);

        $tester = new CommandTester($command);
        $result = $tester->execute([], []);

        $this->assertSame(0, $result);
    }

    public function testDatabaseAvailableAfterRetry(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->any())
            ->method('listTables')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new RuntimeException('Connection failed.')),
                $this->returnValue(['x']),
            ));

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->any())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $command = new WaitForDatabaseCommand($connection);

        $tester = new CommandTester($command);
        $result = $tester->execute(['--retry-seconds' => 0, '--retry-count' => 3], []);

        $this->assertSame(0, $result);

        preg_match_all('/Connection failed/', $tester->getDisplay(), $match);
        $this->assertCount(1, $match[0]);
    }

    public function testDatabaseFailAfterRetry(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->any())
            ->method('listTables')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new RuntimeException('Connection failed.')),
                $this->throwException(new RuntimeException('Connection failed.')),
                $this->throwException(new RuntimeException('Connection failed.')),
                $this->throwException(new RuntimeException('Connection failed.')),
            ));

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->any())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $command = new WaitForDatabaseCommand($connection);

        $tester = new CommandTester($command);
        $result = $tester->execute(['--retry-seconds' => 0, '--retry-count' => 3], []);

        $this->assertSame(200, $result);

        preg_match_all('/Connection failed/', $tester->getDisplay(), $match);
        $this->assertCount(4, $match[0]);
    }
}
