[![codecov](https://codecov.io/gh/brainbits/database-command/branch/main/graph/badge.svg?token=DQUKMP6AF5)](https://codecov.io/gh/brainbits/database-command)

# Installation
Register the commands in your `services.yaml`.

```yaml
    Brainbits\DatabaseCommand\Command\SchemaExistsCommand: ~

    Brainbits\DatabaseCommand\Command\WaitForDatabaseCommand: ~
```

# Usage

```sh
$ bin/console brainbits:database:wait-for-database --retry-seconds 3 --retry-count 100
```

```sh
$ bin/console brainbits:database:schema-exists
```
