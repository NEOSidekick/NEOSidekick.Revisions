[![Latest Stable Version](https://poser.pugx.org/neosidekick/revisions/v/stable)](https://packagist.org/packages/neosidekick/revisions)
[![License](https://poser.pugx.org/neosidekick/revisions/license)](LICENSE)

# NEOSidekick Revisions

## Note: The package will be commercially licensed to cover development costs.

The revisions package will automatically create revisions of pages including their content every time changes are 
published to live.

This enables you to understand which editor published which changes, and you can select existing revisions in the 
inspector for each page and revert to them.

We also offer CLI commands to list, apply and remove revisions.

## Installation

NEOSidekick.Revisions is available via packagist. `"neosidekick/revisions" : "~1.0"` to the require section of the `composer.json`
or run:

```console
composer require neosidekick/revisions
```

Then you should make sure that your database is up-to-date by running the following command:

```console
./flow doctrine:migrate
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

### Flush revisions older than a certain date

The date format is `YYYY-MM-DD`.

```console
./flow revision:flush --since=2022-04-01
```

### Flush revisions without confirmation

This can be used to flush via a cron job.

```console
./flow revision:flush --force
```

## Configuration

The following settings can be adjusted via a `Settings.yaml` file in your project:

```yaml
NEOSidekick:
  Revisions:
    compression:
      enabled: true # Enables compression of revision xml content in the database        
    revisions:
      createRevisionAfterApply: true # Create a revision after applying a revision
      applyWithoutAuthorizationChecks: true # Ignore the editor's node privileges when applying a revision

Neos:
  Neos:
    Ui:
      frontendConfiguration:
        NEOSidekick.Revisions:
          showDeleteButton: false # Show the delete button in the revisions list
```

## Applying a revision

Applying a revision publishes the restored page to live like an editor would. Every package that listens to node
or publishing signals, such as search indexing, frontend revalidation or automatic translation, behaves as for a
manual publish.

A revision is refused, with a list of the reasons, if one of its node types no longer exists or if a node it would
create, move back or retype is no longer allowed in its parent.

By default the editor's node privileges are not evaluated, as in version 1.1.0. With
`applyWithoutAuthorizationChecks: false`, applying a revision that would change a node the editor may not edit is
refused as a whole, before anything is published. The CLI command always applies without authorization checks.

Integrators who need a different behaviour during an apply can connect to the signals `revisionApplying` and
`revisionApplied` of `NEOSidekick\Revisions\Service\RevisionService`.

## License

Commercially licensed. Please contact office@neosidekick.com if you already want to use it, 
otherwise details follow once the first stable release is finished.
