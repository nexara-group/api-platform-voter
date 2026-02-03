# Contributing to API Platform Voter

Thank you for your interest in contributing to API Platform Voter!

## Development Setup

1. **Clone the repository**
```bash
git clone https://github.com/nexara-group/api-platform-voter.git
cd api-platform-voter
```

2. **Install dependencies**
```bash
composer install
```

3. **Run quality checks**
```bash
composer qa  # Runs all checks (PHPStan, ECS, Rector, Tests)
```

## Code Quality Standards

### PHPStan (Level 9)
```bash
composer phpstan
```

We maintain the highest PHPStan level for maximum type safety.

### Coding Standards (ECS)
```bash
composer ecs       # Check
composer ecs-fix   # Fix
```

We follow PSR-12 and Clean Code standards.

### Rector
```bash
composer rector       # Check
composer rector-fix   # Apply
```

### Tests
```bash
composer test
```

All new features must include tests.

## Contribution Guidelines

### Pull Request Process

1. **Fork and create a branch**
```bash
git checkout -b feature/your-feature-name
```

2. **Make your changes**
   - Follow existing code style
   - Add tests for new features
   - Update documentation

3. **Run all quality checks**
```bash
composer qa
```

4. **Commit with clear messages**
```bash
git commit -m "Add: Feature description"
```

Use prefixes: `Add:`, `Fix:`, `Change:`, `Remove:`

5. **Push and create PR**
```bash
git push origin feature/your-feature-name
```

### What We Look For

✅ **Good PRs:**
- Focused on a single feature/fix
- Include tests
- Pass all quality checks
- Have clear commit messages
- Update documentation

❌ **Avoid:**
- Multiple unrelated changes in one PR
- Breaking changes without migration path
- Code without tests
- Ignoring code style

## Architecture Guidelines

### Adding New Features

1. **Keep it simple** - Prefer simple solutions over complex ones
2. **Follow SOLID principles** - Single responsibility, Open/closed, etc.
3. **Type safety** - Use strict types and avoid `mixed` when possible
4. **Backward compatibility** - Don't break existing APIs

### Testing

- **Unit tests** for isolated functionality
- **Integration tests** for component interaction
- **Functional tests** for end-to-end scenarios

### Documentation

- Update README.md for new features
- Add examples in `docs/examples/`
- Update CHANGELOG.md
- Add PHPDoc for public APIs

## Reporting Issues

### Bug Reports

Include:
- PHP version
- Symfony version
- API Platform version
- Steps to reproduce
- Expected vs actual behavior
- Stack trace if available

### Feature Requests

Explain:
- What problem does it solve?
- How would it work?
- Example use case
- Backward compatibility concerns

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Focus on the code, not the person
- Assume good intentions

## Questions?

- 💬 [GitHub Discussions](https://github.com/nexara-group/api-platform-voter/discussions)
- 🐛 [Issue Tracker](https://github.com/nexara-group/api-platform-voter/issues)

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
