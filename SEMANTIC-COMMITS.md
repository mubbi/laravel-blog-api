# Semantic Commits & Automated Releases Setup

This document outlines the semantic commit workflow and automated release process for the Laravel Blog API project.

## 🚀 Quick Start

### Option 1: Local Setup (Requires Node.js)
```bash
# Install commit tools
make install-commit-tools

# Setup git hooks
make setup-git-hooks

# Complete setup
make setup-dev
```

### Option 2: Docker-based Setup (Recommended)
```bash
# Setup development environment with Docker
make docker-setup-dev

# Use Docker-based commit
make docker-commit
```

## 📝 Commit Workflow

### Making Commits

**Interactive Commit (Recommended):**
```bash
# Local
make commit

# Docker
make docker-commit
```

**Manual Commit (Validated):**
```bash
git add .
git commit -m "feat(auth): add user authentication"
```

### Commit Message Format

We follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

#### Types
- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation only changes
- `style`: Changes that do not affect the meaning of the code
- `refactor`: A code change that neither fixes a bug nor adds a feature
- `test`: Adding missing tests or correcting existing tests
- `chore`: Changes to the build process or auxiliary tools
- `perf`: A code change that improves performance
- `ci`: Changes to CI configuration files and scripts
- `build`: Changes that affect the build system
- `revert`: Reverts a previous commit

#### Examples
```bash
feat(api): add user registration endpoint
fix(auth): resolve token validation issue
docs: update API documentation
refactor(user): simplify user model relationships
test(api): add integration tests for auth endpoints
chore(deps): update Laravel to v11
```

## 🔒 Enforcement

### Git Hooks
The project uses git hooks to enforce semantic commits:

- **prepare-commit-msg**: Guides users to use proper commit tools
- **commit-msg**: Validates commit messages against conventional commit format
- **pre-commit**: Runs code linting and static analysis

### CI/CD Validation
- Pull requests validate all commit messages
- Invalid commits will fail CI checks
- Only properly formatted commits can be merged

## 📦 Automated Releases

### How It Works
1. **Commits**: All commits follow semantic commit format
2. **Analysis**: Release Please analyzes commit history
3. **PR Creation**: Automatically creates release PR with:
   - Updated version numbers
   - Generated CHANGELOG.md
   - Git tags
4. **Release**: Merging the PR triggers:
   - GitHub release creation
   - Docker image building (optional)
   - Deployment (optional)

### Version Bumping
- `feat`: Minor version bump (1.0.0 → 1.1.0)
- `fix`: Patch version bump (1.0.0 → 1.0.1)
- `BREAKING CHANGE`: Major version bump (1.0.0 → 2.0.0)

### Manual Release
```bash
# Local
make release

# Docker
docker-compose -f containers/docker-compose.dev.yml exec dev-tools npm run release
```

## 🛠️ Available Commands

### Local Commands
```bash
make commit                  # Interactive semantic commit
make validate-commit         # Validate last commit message
make setup-dev              # Complete development setup
make install-commit-tools    # Install Node.js dependencies
make setup-git-hooks        # Install git hooks
make release                # Create a release
```

### Docker Commands
```bash
make docker-setup-dev        # Setup Docker development environment
make docker-commit           # Docker-based interactive commit
make docker-validate-commit  # Validate commit in Docker
make docker-cleanup-dev      # Clean up Docker environment
```

### Laravel Commands (Existing)
```bash
make docker-lint            # Run Pint linter
make docker-analyze         # Run static analysis
make docker-test            # Run tests
```

## 🚨 Troubleshooting

### Common Issues

1. **"npm not found" error**
   - Use Docker commands: `make docker-commit`
   - Or install Node.js locally

2. **Commit rejected**
   - Use `make commit` for guided commit creation
   - Check commit message format against examples above

3. **Git hooks not working**
   - Run `make setup-git-hooks`
   - Ensure hooks are executable: `chmod +x .git/hooks/*`

### Bypassing Validation (Emergency Only)
```bash
# Only for emergency fixes
git commit --no-verify -m "emergency fix"
```

## 📊 Benefits

1. **Consistent History**: All commits follow the same format
2. **Automated Changelogs**: No manual changelog maintenance
3. **Semantic Versioning**: Automatic version bumping based on changes
4. **Better Collaboration**: Clear commit messages improve code review
5. **Release Automation**: Streamlined release process
6. **Docker Integration**: Works seamlessly with existing Docker workflow

## 🔗 References

- [Conventional Commits](https://www.conventionalcommits.org/)
- [Release Please](https://github.com/googleapis/release-please)
- [Commitizen](https://github.com/commitizen/cz-cli)
- [Commitlint](https://commitlint.js.org/)
