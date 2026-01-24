# Security Policy

## Supported Versions

We release patches for security vulnerabilities. Which versions are eligible for receiving such patches depends on the CVSS v3.0 Rating:

| Version | Supported          |
| ------- | ------------------ |
| 0.1.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within this package, please send an email to security@nexara.sk. All security vulnerabilities will be promptly addressed.

Please do not publicly disclose the issue until it has been addressed by the team.

### What to Include

When reporting a security issue, please include:

- A description of the vulnerability
- Steps to reproduce the issue
- Potential impact of the vulnerability
- Any suggested fixes (if available)

### Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Timeline**: Depends on severity, typically within 30 days for critical issues

## Security Best Practices

When using this bundle:

1. **Always implement proper authorization logic** in your voter classes
2. **Never bypass voter checks** by disabling the bundle in production
3. **Use the `voter` parameter** in `#[ApiResourceVoter]` to ensure only specific voters can grant access
4. **Review custom operations** carefully to ensure they have appropriate authorization
5. **Keep dependencies updated** to receive security patches
6. **Enable caching** in production to prevent metadata resolution performance issues
7. **Test your voters thoroughly** with different user roles and permissions

## Known Security Considerations

### Voter Precedence

When multiple voters support the same attribute, Symfony's voter system uses a consensus strategy by default. Ensure your voter configuration aligns with your security requirements.

### Custom Operations

Custom operations must be explicitly handled in your voter's `canCustomOperation` method or via dedicated `can{OperationName}` methods. Operations without explicit handling will be denied by default.

### Metadata Caching

The bundle caches resource metadata for performance. Ensure your cache is cleared when deploying security-related changes to voter attributes or resource configurations.
