# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2025-01-25

### Added
- Initial release of Nexara API Platform Voter Bundle
- `#[ApiResourceVoter]` attribute for marking API Platform resources as voter-protected
- `CrudVoter` abstract base class for implementing CRUD authorization logic
- `AutoConfiguredCrudVoter` for automatic voter configuration from resource metadata
- `OperationToVoterAttributeMapper` for mapping API Platform operations to voter attributes
- Support for custom operations with automatic attribute mapping
- `SecurityProvider` and `SecurityProcessor` for integrating with API Platform state system
- `VoterRegistry` for managing voter-resource relationships
- Maker command `make:api-resource-voter` for generating voter classes
- Comprehensive test suite with PHPUnit
- Static analysis with PHPStan (level 8)
- Code style enforcement with ECS (PSR-12, Clean Code)
- Automated refactoring with Rector
- Full documentation in README.md

### Features
- Opt-in security per resource via attribute
- CRUD operations mapped to namespaced voter attributes (`{prefix}:list`, `{prefix}:create`, etc.)
- Custom operation support with explicit voter methods
- UPDATE operations receive both new and previous object for comparison
- Configurable prefix per resource
- Optional voter class specification for targeted authorization
- Caching support for metadata resolution
- Symfony 6.4+ and 7.0+ compatibility
- PHP 8.1+ support

[Unreleased]: https://github.com/nexara-group/api-platform-voter/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/nexara-group/api-platform-voter/releases/tag/v0.1.0
