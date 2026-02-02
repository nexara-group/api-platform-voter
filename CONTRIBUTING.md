# Contributing to Nexara API Platform Voter

Thank you for considering contributing to this project! We welcome contributions from the community.

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please be respectful and constructive in all interactions.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected behavior** vs actual behavior
- **Environment details** (PHP version, Symfony version, API Platform version)
- **Code samples** if applicable

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, include:

- **Clear title and description**
- **Use case** and motivation
- **Proposed solution** or API design
- **Alternative solutions** you've considered

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Follow the coding standards** (see below)
3. **Add tests** for any new functionality
4. **Update documentation** as needed
5. **Ensure all tests pass** and quality checks succeed
6. **Write clear commit messages**

## Development Setup

```bash
# Clone your fork
git clone https://github.com/your-username/api-platform-voter.git
cd api-platform-voter

# Install dependencies
composer install

# Run tests
composer test

# Run quality checks
composer qa
```

## Coding Standards

This project follows strict coding standards:

### PHP Standards

- **PSR-12** coding style
- **PHP 8.1+** features and type declarations
- **Strict types** declaration in all files
- **PHPStan level 8** compliance

### Running Quality Checks

```bash
# Run all quality checks
composer qa

# Individual checks
composer phpstan        # Static analysis
composer ecs            # Code style check
composer ecs-fix        # Fix code style issues
composer rector         # Check for refactoring opportunities
composer rector-fix     # Apply refactorings
composer test           # Run tests
```

### Code Style Guidelines

- Use **type declarations** for all parameters and return types
- Use **readonly properties** where applicable
- Prefer **constructor property promotion**
- Use **named arguments** for better readability when appropriate
- Add **PHPDoc blocks** only when they provide additional value beyond type declarations
- Keep methods **focused and small**
- Use **early returns** to reduce nesting

### Testing Guidelines

- Write tests for all new functionality
- Aim for high code coverage
- Use descriptive test method names
- Follow the **Arrange-Act-Assert** pattern
- Mock external dependencies appropriately

### Documentation

- Update README.md for user-facing changes
- Update CHANGELOG.md following [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
- Add inline comments for complex logic
- Keep documentation clear and concise

## Commit Messages

Follow these guidelines for commit messages:

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters or less
- Reference issues and pull requests when applicable

### Commit Message Format

```
<type>: <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Example:**
```
feat: add support for custom operation attributes

Implement automatic mapping of custom API Platform operations
to voter attributes using the operation name.

Closes #123
```

## Release Process

Releases are managed by maintainers:

1. Update CHANGELOG.md with release notes
2. Update version in relevant files
3. Create a git tag following semantic versioning
4. Push tag to trigger release workflow
5. Publish to Packagist

## Questions?

Feel free to open an issue for questions or reach out to the maintainers.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
