# Installation

Register the commands in your `services.yaml`.

```yaml
    Tspm\DatabaseCommand\Command\SchemaExistsCommand: ~

    Tspm\DatabaseCommand\Command\WaitForDatabaseCommand: ~
```

For older symfony systems use:
```yaml
        Tspm\DatabaseCommand\Command\SchemaExistsCommand:
            arguments:
                $connection: '@doctrine.dbal.default_connection'
            tags:
                - { name: 'console.command' }

        Tspm\DatabaseCommand\Command\WaitForDatabaseCommand:
            arguments:
                $connection: '@doctrine.dbal.default_connection'
            tags:
                - { name: 'console.command' }
```
