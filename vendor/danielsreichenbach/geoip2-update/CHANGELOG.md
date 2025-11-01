# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.5.1] - 2025-01-30

### Fixed
- Updated MaxMind API base URL from `https://updates.maxmind.com` to `https://download.maxmind.com` to resolve "remote server is not available" errors ([#3](https://github.com/danielsreichenbach/geoip2-update/issues/3))

### Added
- Added CHANGELOG.md documentation

## [2.5.0] - 2024-05-08

### Changed
- Allowed older PHP versions for broader compatibility

## [2.4.2] - 2024-05-02

### Fixed
- Replaced more old download code

## [2.4.1] - 2024-05-02

### Fixed
- Changed namespaces to match new project structure

## [2.4.0] - 2024-05-02

### Added
- Replaced unknown API calls with official MaxMind endpoints

### Changed
- Major refactoring of the download mechanism
- Updated to use MaxMind's official API structure

## [2.3.0] and earlier

For releases prior to v2.4.0, please see the [releases page](https://github.com/danielsreichenbach/geoip2-update/releases).

[Unreleased]: https://github.com/danielsreichenbach/geoip2-update/compare/v2.5.1...HEAD
[2.5.1]: https://github.com/danielsreichenbach/geoip2-update/compare/v2.5.0...v2.5.1
[2.5.0]: https://github.com/danielsreichenbach/geoip2-update/compare/v2.4.2...v2.5.0
[2.4.2]: https://github.com/danielsreichenbach/geoip2-update/compare/v2.4.1...v2.4.2
[2.4.1]: https://github.com/danielsreichenbach/geoip2-update/compare/v2.4.0...v2.4.1
[2.4.0]: https://github.com/danielsreichenbach/geoip2-update/releases/tag/v2.4.0