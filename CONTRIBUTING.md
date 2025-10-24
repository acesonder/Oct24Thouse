# Contributing to Transition House

Thank you for your interest in contributing to the Transition House platform!

## Code of Conduct

This project serves a vulnerable population. All contributions must:
- Prioritize client privacy and data security
- Follow trauma-informed design principles
- Maintain PHIPA/PIPEDA compliance
- Respect the lived experience of clients and peers

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported
2. Use the bug report template
3. Include:
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details
   - Screenshots if applicable

### Suggesting Features

1. Check if the feature has been requested
2. Explain the use case
3. Describe how it benefits clients, staff, or peers
4. Consider privacy and security implications

### Pull Requests

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Update documentation
6. Submit pull request

#### Code Standards

**PHP:**
- Follow PSR-12 coding standard
- Use prepared statements for database queries
- Include PHPDoc comments
- Sanitize all inputs
- Handle errors gracefully

**JavaScript:**
- Use ES6+ features
- Keep functions small and focused
- Comment complex logic
- Follow existing code style

**Security:**
- Never commit sensitive data
- Always validate and sanitize input
- Use parameterized queries
- Follow least privilege principle
- Log security-relevant actions

**Testing:**
- Test with different user roles
- Test edge cases
- Verify security measures
- Check mobile responsiveness

### Commit Messages

Use clear, descriptive commit messages:

```
feat: Add peer engagement dashboard
fix: Correct bed assignment validation
docs: Update API documentation
security: Fix SQL injection vulnerability
```

## Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env`
4. Configure database connection
5. Import database schema
6. Start development server

## Testing

Before submitting:
- Test all affected features
- Verify no security regressions
- Check mobile compatibility
- Review code for sensitive data
- Update documentation

## Questions?

Contact the maintainers or open an issue.

## License

By contributing, you agree that your contributions will be part of this project.
