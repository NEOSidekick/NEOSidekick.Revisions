[![Latest Stable Version](https://poser.pugx.org/codeq/revisions/v/stable)](https://packagist.org/packages/codeq/revisions)
[![License](https://poser.pugx.org/codeq/revisions/license)](LICENSE)

# IN DEVELOPMENT: Code Q Revisions

The revisions package will automatically create revisions of pages including their content every time changes are 
published to live.

This enables you to understand which editor published which changes, and you can select existing revisions in the 
inspector for each page and revert to them.

We also offer CLI commands to list, apply and remove revisions.

Note: The package will be commercially licensed to cover development costs.

## Installation

CodeQ.Revisions is available via packagist. `"codeq/revisions" : "~1.0"` to the require section of the `composer.json`
or run:

```bash
composer require codeq/revisions
```

We use semantic-versioning so every breaking change will increase the major-version number.

## CLI usage

### Create revisions for a node

```console
./flow revision:create <NodeIdentifier>
```

### List revisions for a node

```console
./flow revision:list <NodeIdentifier>
```

### Flush all revisions

```console
./flow revision:flush
```

## Configuration

The following settings can be adjusted via a `Settings.yaml` file in your project:

```yaml
CodeQ:
  Revisions:
    compression:
      enabled: true # Enables compression of revision xml content in the database
```

## License

Commercially licensed. Please contact office@codeq.at if you already want to use it, 
otherwise details follow once the first stable release is finsihed.
