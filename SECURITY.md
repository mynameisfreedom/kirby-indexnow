# Security Policy

## Reporting a Vulnerability

**Do not** open a public GitHub issue for security vulnerabilities. This could expose the vulnerability before a fix is available.

Instead, please email security issues to:
- **support@thezero.club**

Please include:
- A description of the vulnerability
- Steps to reproduce or proof of concept
- Affected versions (or "latest" if unsure)
- Your suggested fix (if you have one)

We will:
- Acknowledge receipt within 48 hours
- Work on a fix and provide a timeline
- Credit you in the advisory (unless you prefer anonymity)
- Release a security patch in a timely manner

## Supported Versions

| Version | Supported | Security Updates |
|---------|-----------|------------------|
| 1.x     | ✅ Yes    | Yes              |
| 0.x     | ❌ No     | No               |

## Security Considerations for Users

### Key Management
- Store your IndexNow key securely
- Keep your key file (`keyFile`) in your web root but treat it as sensitive
- Rotate your key periodically if you suspect compromise
- Never commit your key to version control

### Configuration Security
- Use environment-specific config files to avoid exposing keys
- Set `indexnow.enabled => false` in development environments
- Regularly review the `site/logs/indexnow.log` for unusual activity

### Panel Access
- Restrict Kirby Panel access to authorized users only
- The IndexNow view is only accessible to authenticated Panel users
- All Panel API requests are protected with CSRF tokens

## Dependency Security

This plugin has minimal dependencies:
- **Kirby 5+** - Vendored with your Kirby installation
- **KirbyUp** (dev only) - Used for building the Panel component

We recommend:
- Keeping Kirby updated to the latest stable version
- Running `composer audit` regularly to check for dependency vulnerabilities
- Using a Web Application Firewall (WAF) to protect your site

## Responsible Disclosure

We appreciate responsible disclosure of security vulnerabilities. Please allow us reasonable time to:
1. Verify and assess the vulnerability
2. Develop and test a fix
3. Release the security update
4. Credit the finder (if desired)

Before publicly disclosing any details.

## Additional Resources

- [Kirby Security Documentation](https://getkirby.com/docs)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CVE Databases](https://cve.mitre.org/)
