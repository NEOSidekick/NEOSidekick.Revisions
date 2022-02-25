[![Latest Stable Version](https://poser.pugx.org/codeq/revisions/v/stable)](https://packagist.org/packages/codeq/revisions)
[![License](https://poser.pugx.org/codeq/revisions/license)](LICENSE)

# CodeQ Revisions

After this plugin is installed in your Neos CMS project, it will automatically create revisions of pages including 
their content when publishing content to live.

Afterwards you can select existing revisions in the inspector for each page and revert to them.

Also CLI commands exist to list, apply and remove revisions.

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

## License

Licensed under MIT, see [LICENSE](LICENSE)

## Contribution

We will gladly accept contributions. Please send us pull requests.
