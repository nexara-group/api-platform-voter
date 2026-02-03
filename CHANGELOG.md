# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Strict Mode**: Throw exception when no voter supports an attribute (configurable via `strict_mode`)
- **Debug Mode Integration**: Full integration of VoterDebugger with SecurityProvider/Processor
- **Audit Logging System**: Complete audit trail for authorization decisions with Monolog integration
- **PHP Parser Integration**: Replaced regex parsing with nikic/php-parser for reliable class extraction
- **GraphQL Support**: Basic GraphQL query/mutation support with GraphQLCrudVoter
- **Multi-Tenancy**: Complete multi-tenancy implementation with TenantContext and TenantAwareVoterTrait
- **Performance**: PHP class parser for faster and more reliable file scanning

### Changed
- Removed unused SubjectResolver strategy classes (ChainSubjectResolver, DefaultSubjectResolverStrategy, etc.)
- Improved type safety and null handling in SecurityProvider and SecurityProcessor
- Enhanced error messages with NoVoterFoundException

### Fixed
- Custom operation name mapping now checks operation name BEFORE default CRUD mapping
- Proper integration of debug and audit logging into security layer

## [0.2.0] - 2024-01-XX

### Breaking Changes
- Namespace reorganization (see UPGRADE-0.2.md)
- Renamed `ApiResourceVoter` attribute to `Secured`

### Added
- AutoConfiguredCrudVoter for automatic configuration
- VoterRegistry for voter-to-resource mapping
- Comprehensive documentation and examples
- Maker command improvements

## [0.1.0] - 2023-XX-XX

### Added
- Initial release
- Basic CRUD voter functionality
- API Platform 3 integration
- Custom operation support
