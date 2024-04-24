# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2024-04-24

### Added
- Added getter and setter methods for the file's last modified datetime to `FileHandler`.
- Added a feature to retrieve a list of files under a specific directory to `FileHandler`.
- Added getter and setter methods for the last modified datetime of a specified key to `DataStorage`.
- Added a feature to retrieve a list of keys under a specified initial segment to `DataStorage`.
- Newly implemented a localization (multi-language) support and content negotiation mechanism
(which includes automatically fetching data from cache or returning a `304 Not Modified` response as required).
- Enriched and comprehensive framework documentation.

### Changed
- Refactored `StandardSessionStorageFactory` to use `DataSessionStorage` by default instead of `FileSessionStorage`.

## [1.0.0] - 2019-12-05

### Added
- Initial release of the Woof framework core.
- Immutable HTTP Request and Response handling components.
