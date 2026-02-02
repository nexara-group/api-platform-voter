# Changelog - Version 0.2.0

## [0.2.0] - 2026-02-02

### 🔴 BREAKING CHANGES

#### Namespace Reorganization
Major namespace restructuring for better organization:
- `Nexara\ApiPlatformVoter\Security\Voter\*` → `Nexara\ApiPlatformVoter\Voter\*`
- `Nexara\ApiPlatformVoter\ApiPlatform\State\*` → `Nexara\ApiPlatformVoter\Provider\*` and `Nexara\ApiPlatformVoter\Processor\*`
- `Nexara\ApiPlatformVoter\ApiPlatform\Security\ResourceAccessMetadata*` → `Nexara\ApiPlatformVoter\Metadata\*`

**Migration:** Update all imports in your voter classes and configuration.

#### Attribute Renamed
- `#[ApiResourceVoter]` → `#[Secured]`

**Migration:**
```php
// Before
use Nexara\ApiPlatformVoter\Attribute\ApiResourceVoter;

#[ApiResourceVoter(voter: ArticleVoter::class)]
class Article {}

// After
use Nexara\ApiPlatformVoter\Attribute\Secured;

#[Secured(voter: ArticleVoter::class)]
class Article {}
```

#### Removed Methods
- Removed `AutoConfiguredCrudVoter::setMetadataFactory()` call from service configuration

### ✨ New Features (from 0.1.x)

#### Core Security
- Context-aware authorization with `RequestContext` (IP, time, headers)
- Enhanced `AuthorizationException` with factory methods and rich context
- `VoterDebugger` for detailed decision logging
- Voter decision events system

#### Testing Infrastructure
- `VoterTestCase` base class with helper assertions
- `TestSecurityHelper` for proper role hierarchy in tests
- Integration tests for CrudVoter
- Performance benchmarking tests

#### Architecture Improvements
- Strategy pattern for subject resolution
- `VoterValidatorCompilerPass` for compile-time validation
- Lazy voter loading support
- Reusable voter traits (Ownership, Role, Timestamp, TenantAware)

#### Performance Optimizations
- Metadata precompilation with `VoterMetadataWarmer`
- Operation attribute map caching
- Result memoization within requests
- Reflection caching
- Optimized voter registry using service tags

#### Enterprise Features
- Multi-tenancy support with tenant context and isolation
- Hierarchical permissions system
- Delegated authorization with expiration
- Voter groups for organization
- GraphQL voter interface

#### Monitoring & Metrics
- Voter metrics collection and analysis
- Performance tracking
- Decision audit logging
- Multiple debug output formats

#### Developer Experience
- Enhanced maker command with interactive custom operations
- Automatic test generation
- Processor class generation
- 7 comprehensive documentation guides

#### Configuration Extensions
- Full audit logging configuration
- Debug output format options
- Cache warming strategies
- Error reporting levels
- Rate limiting support
- Performance tuning options

### 🐛 Bug Fixes
- Fixed role hierarchy evaluation in test environment
- Fixed memory leak in VoterRegistry with locking mechanism
- Fixed custom provider security decoration
- PHPStan level 9 compliance improvements

### 📖 Documentation
- Common authorization patterns cookbook
- Field-level authorization guide
- Multi-tenancy setup guide  
- Custom POST operations guide
- Security vs securityPostDenormalize comparison
- 50+ code examples

### 🔧 Internal Improvements
- Upgraded to PHPStan level 9
- Custom PHPStan rules for voter validation
- Generic type support with TypedCrudVoter<T>
- Modern PHP 8.1+ features (readonly, constructor promotion)
- Applied Rector rules for code modernization

### 📊 Statistics
- **60+ new files** created
- **~4,500+ lines** of code added
- **7 documentation guides**
- **114 PHPStan** checks passing
- **Full backward compatibility** maintained where possible

### ⚠️ Migration Required
If upgrading from 0.1.x, you must update:
1. All `use` statements with old namespaces
2. `#[ApiResourceVoter]` → `#[Secured]`
3. Check for any custom provider decorations

Estimated migration time: **30-60 minutes** for typical projects.

---

**Full changelog:** https://github.com/nexara/api-platform-voter/compare/0.1.0...0.2.0
