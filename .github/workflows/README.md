# GitHub Action Workflows

## Release Process

Building new releases is done by manually triggering the `Release: Preparation 🚀` workflow. It will create a pull requests that prepares the new release and that can be merged to actually trigger the release process.

The workflow will merge `master` into `stable`, therefor the workflow should only be used for:

  * new stable releases
  * new release candidates
  * hotfix releases, that are very close to the previous release

The workflow should not be used for hotfixes that are only meant to introduce a single patch.

The `Release: Tag, Build & Deploy` workflow is triggered everytime something is pushed to the `stable` branch. It will create a new tag, build the release and deploy it.

This means you can apply hotfix patches directly to `stable`. Be sure to update the `VERSION` file and increase the update version number in your patch commit!

**Important** updating `old-stable` is currently not automated. You have to manually update the `old-stable` branch before a new stable release.
