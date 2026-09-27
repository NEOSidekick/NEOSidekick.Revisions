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

### Apply a revision

```console
./flow revision:apply <RevisionIdentifier>
```

Content moved to another page since the revision is moved back, and content moved to the page since then is
removed. A file with resolutions, see [Applying a revision](#applying-a-revision), decides otherwise, and is required
if a node type of the revision no longer fits:

```console
./flow revision:apply <RevisionIdentifier> --resolutions=resolutions.json
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

Some changes since the revision need a decision before it can be applied. The inspector lists them in a dialog, the
CLI command prints them:

- A node type of the revision no longer exists or is abstract, or a node would be created, moved back or retyped
  where it is no longer allowed or where its parent no longer exists: skip the node.
- Content was moved to another page: move it back, or leave it there.
- Content was moved to this page and already existed when the revision was created: keep it, or remove it. Content
  created after the revision is removed without asking.

Skipping leaves the node and everything inside it as it is in live. Each decision is a resolution keyed by node
identifier, with exactly one of `__skip`, `__moveBack` or `__remove` set to `true`:

```json
{
    "<node identifier>": {"__skip": true},
    "<identifier of content moved to another page>": {"__moveBack": true},
    "<identifier of content moved to this page>": {"__remove": true}
}
```

A revision whose document node no longer fits is refused, because the document itself is never resolved.

By default the editor's node privileges are not evaluated, as in version 1.1.0. With
`applyWithoutAuthorizationChecks: false`, applying a revision that would change a node the editor may not edit is
refused as a whole, before anything is published. The CLI command always applies without authorization checks.

Integrators who need a different behaviour during an apply can connect to the signals `revisionApplying` and
`revisionApplied` of `NEOSidekick\Revisions\Service\RevisionService`.

## Checks

Every pull request and every push to `main` runs six checks in GitHub Actions (`.github/workflows/ci.yml`), in seven
jobs because PHP lint runs for two PHP versions. None of them needs a database. To run them locally:

PHP lint, run in CI with PHP 7.4 and 8.3:

```console
find Classes Migrations -name '*.php' -print0 | xargs -0 -n1 php -l
```

PHPStan with the configuration and baseline of this repository, in a throwaway Neos distribution that contains the
package (here next to a checkout in `NEOSidekick.Revisions`). Only findings that are not in the baseline fail:

```console
mkdir revisions-phpstan && cd revisions-phpstan
composer init --no-interaction --name=ci/distribution --type=project --stability=dev
composer config prefer-stable true
composer config allow-plugins.neos/composer-plugin true
composer config repositories.package path ../NEOSidekick.Revisions
composer require 'neosidekick/revisions:*@dev' 'phpstan/phpstan:2.2.16'
vendor/bin/phpstan analyse --configuration ../NEOSidekick.Revisions/phpstan.neon
```

A finding that no longer occurs has to be removed from the baseline as well. Regenerate it with
`--generate-baseline ../NEOSidekick.Revisions/phpstan-baseline.neon` added to the last command.

Composer manifest:

```console
composer validate --no-check-publish
```

Translation files, which must be well-formed XML:

```console
find Resources/Private/Translations -name '*.xlf' -print0 | xargs -0 xmllint --noout
```

Inspector build, with the Node version from `.nvmrc`. The committed `Plugin.js` must be exactly what the source builds
to:

```console
cd Resources/Private/JavaScript/InspectorPlugin
npm install --no-package-lock
node build.js
git diff --exit-code -- ../../../Public/Assets/Plugin.js
```

ESLint on the inspector source, from the repository root:

```console
npm install --no-package-lock
npm run lint
```

## License

Commercially licensed. Please contact office@neosidekick.com if you already want to use it, 
otherwise details follow once the first stable release is finished.
