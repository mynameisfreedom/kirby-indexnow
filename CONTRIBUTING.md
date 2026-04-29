# Contributing to IndexNow for Kirby

Thank you for considering contributing to the IndexNow plugin! This document provides guidelines and instructions for contributing.

## Code of Conduct

Be respectful, inclusive, and constructive in all interactions with other contributors and maintainers.

## Getting Started

### Prerequisites
- PHP 8.2+
- Kirby 5+
- Node.js and npm (for building the Panel UI)
- Git

### Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/mynameisfreedom/kirby-indexnow.git
   cd kirby-indexnow
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Start development mode** (watch src/index.js for changes)
   ```bash
   npm run dev
   ```

4. **Build for production**
   ```bash
   npm run build
   ```

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue on GitHub with:
- A clear, descriptive title
- A detailed description of the problem
- Steps to reproduce the issue
- Expected vs. actual behavior
- Your environment (PHP version, Kirby version, OS)
- Any relevant log output from `site/logs/indexnow.log`

### Suggesting Enhancements

Enhancement suggestions are welcome! Please create an issue with:
- A clear title describing the feature
- A detailed description of the proposed enhancement
- Use cases and benefits
- Possible implementation approaches (optional)

### Submitting Changes

1. **Fork the repository** and create a new branch
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**
   - Follow the existing code style
   - Use EditorConfig (supported editors will apply settings automatically)
   - Keep changes focused and atomic

3. **Test your changes**
   - If you modified `src/index.js`, run `npm run build` and test the Panel UI
   - If you modified PHP code, test in a local Kirby installation
   - Verify logging works correctly with debug enabled/disabled

4. **Commit with clear messages**
   ```bash
   git commit -m "Fix: description of what was fixed"
   git commit -m "Feature: description of new feature"
   ```

5. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Open a Pull Request**
   - Reference any related issues
   - Provide a clear description of changes
   - Explain why the change is needed

## Code Style Guidelines

- **PHP**: Use PHP 8.2+ syntax, follow PSR-12 conventions
- **JavaScript/Vue**: Use modern ES6+, follow existing component patterns
- **CSS**: Follow Kirby's CSS conventions with `--spacing-*` and `--font-*` variables
- **Indentation**: 4 spaces for PHP, 2 spaces for JavaScript/JSON

## File Structure

```
.
├── index.php          # Plugin bootstrap and core logic
├── index.js           # Compiled Panel component (don't edit directly)
├── index.css          # Panel styles
├── src/
│   └── index.js       # Vue component source (edit this, then run npm run build)
├── README.md          # User documentation
├── CHANGELOG.md       # Version history
└── composer.json      # PHP package metadata
```

## Commit Message Convention

Follow conventional commit format:
- `fix: ` - Bug fixes
- `feature: ` or `feat: ` - New features
- `docs: ` - Documentation changes
- `style: ` - Code style changes (formatting, missing semicolons, etc.)
- `refactor: ` - Code refactoring without feature changes
- `test: ` - Test-related changes
- `chore: ` - Build, dependencies, tooling changes

Example: `fix: correct endpoint default display in panel`

## Questions?

Feel free to open an issue with your question, and we'll help you out.

Thank you for contributing! 🎉
