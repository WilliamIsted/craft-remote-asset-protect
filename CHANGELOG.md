# Remote Asset Protect Changelog

## [Unreleased]

### Added

- None.

### Changed

- None.

### Removed

- None.

### Pending

* Ability to customise the error message displayed to developers who attempt to delete protected assets.

## [2.0.1] - 2023-03-29

### Changed

* Fixed issue detecting local volumes. This caused protection to apply to both local and remote volume types. ([#10](https://github.com/WilliamIsted/craft-remote-asset-protect/issues/10))

[Unreleased]: https://github.com/WilliamIsted/craft-remote-asset-protect/compare/2.0.1...HEAD
[2.0.1]: https://github.com/WilliamIsted/craft-remote-asset-protect/releases/tag/2.0.1

## [2.0.0] - 2023-03-17

### Added

* Initial release of the Remote Asset Protect plugin. ([#1](https://github.com/WilliamIsted/craft-remote-asset-protect/issues/1))
* Ability to prevent deletion of assets on remote volumes in use by the production site. ([#1](https://github.com/WilliamIsted/craft-remote-asset-protect/issues/1))
* Ability to specify which environment(s) the protection should be disabled on. Protection is on by default for non-production sites. ([#1](https://github.com/WilliamIsted/craft-remote-asset-protect/issues/1))
* Ability to block uploading and/or deletion of assets to protected volumes including on production site. ([#3](https://github.com/WilliamIsted/craft-remote-asset-protect/issues/3))

[2.0.0]: https://github.com/WilliamIsted/craft-remote-asset-protect/releases/tag/2.0.0