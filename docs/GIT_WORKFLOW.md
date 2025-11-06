# LeadSharks LMS - Git Workflow & Collaboration Guide

## 📋 Table of Contents

- [Git Workflow Overview](#git-workflow-overview)
- [Branch Strategy](#branch-strategy)
- [Commit Conventions](#commit-conventions)
- [Pull Request Process](#pull-request-process)
- [Code Review Guidelines](#code-review-guidelines)
- [Team Collaboration](#team-collaboration)
- [Release Management](#release-management)
- [Hotfix Procedures](#hotfix-procedures)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## 🔄 Git Workflow Overview

We use **Git Flow** as our branching model, which provides a robust framework for managing features, releases, and hotfixes in a team environment. This ensures code quality, facilitates collaboration, and maintains a stable production environment.

### Workflow Benefits
- **Parallel Development** - Multiple features can be developed simultaneously
- **Stable Main Branch** - Production code is always stable
- **Code Quality** - All code goes through review process
- **Traceability** - Clear history of all changes
- **Rollback Capability** - Easy to revert problematic changes

## 🌳 Branch Strategy

### Core Branches

#### `main` (Production)
- **Purpose**: Contains production-ready code
- **Protection**: Direct commits not allowed
- **Deployment**: Automatically deployed to production
- **Merge Source**: Only from `release/*` and `hotfix/*` branches

```bash
# Main branch characteristics
- Always stable and deployable
- Tagged for each release
- Protected branch with required reviews
```

#### `develop` (Integration)
- **Purpose**: Integration branch for ongoing development
- **Source**: All feature branches merge here
- **Testing**: Continuous integration testing
- **Deployment**: Deployed to staging environment

```bash
# Develop branch workflow
git checkout develop
git pull origin develop
# Start new features from here
```

### Supporting Branches

#### `feature/*` (Feature Development)
- **Naming**: `feature/feature-name`
- **Purpose**: Individual feature development
- **Lifetime**: Created from `develop`, merged back to `develop`
- **Examples**: 
  - `feature/lead-scoring-system`
  - `feature/excel-export-improvements`
  - `feature/mobile-responsive-design`

```bash
# Create feature branch
git checkout develop
git pull origin develop
git checkout -b feature/lead-scoring-system
```

#### `release/*` (Release Preparation)
- **Naming**: `release/v1.2.0`
- **Purpose**: Release preparation and minor bug fixes
- **Source**: Created from `develop`
- **Merge To**: Both `main` and `develop`

```bash
# Create release branch
git checkout develop
git checkout -b release/v1.2.0
```

#### `hotfix/*` (Critical Fixes)
- **Naming**: `hotfix/critical-security-fix`
- **Purpose**: Critical production fixes
- **Source**: Created from `main`
- **Merge To**: Both `main` and `develop`

```bash
# Create hotfix branch
git checkout main
git pull origin main
git checkout -b hotfix/critical-security-fix
```

### Branch Naming Convention

#### Feature Branches
```
feature/lead-scoring-algorithm
feature/dashboard-performance-optimization
feature/bulk-import-enhancement
feature/mobile-app-integration
```

#### Bug Fix Branches
```
feature/fix-email-notification-bug
feature/fix-dashboard-loading-issue
feature/fix-excel-import-validation
```

#### Documentation Branches
```
feature/update-api-documentation
feature/add-deployment-guide
feature/improve-readme-structure
```

## 📝 Commit Conventions

We follow [Conventional Commits](https://conventionalcommits.org/) specification for consistent commit messages.

### Commit Message Format
```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Commit Types

#### `feat`: New Features
```bash
git commit -m "feat: implement lead scoring algorithm

- Add weighted scoring based on lead attributes
- Include industry-specific scoring rules  
- Add configuration interface for score weights
- Update dashboard to display lead scores

Closes #45"
```

#### `fix`: Bug Fixes
```bash
git commit -m "fix: resolve email notification delivery issue

- Fix SMTP configuration validation
- Add error handling for failed email sends
- Update email templates for better compatibility
- Add retry mechanism for failed deliveries

Fixes #123"
```

#### `docs`: Documentation
```bash
git commit -m "docs: add API documentation for lead endpoints

- Document all lead management endpoints
- Add request/response examples
- Include authentication requirements
- Add error code descriptions"
```

#### `style`: Code Formatting
```bash
git commit -m "style: format PHP code according to PSR-12 standards

- Fix indentation in lead management classes
- Remove trailing whitespace
- Standardize variable naming conventions"
```

#### `refactor`: Code Restructuring
```bash
git commit -m "refactor: optimize database query performance

- Restructure lead search queries
- Add proper indexing for frequently accessed fields
- Optimize JOIN operations for better performance
- Remove redundant database calls"
```

#### `test`: Testing
```bash
git commit -m "test: add unit tests for lead validation

- Test lead data validation functions
- Add test cases for edge cases
- Mock external API calls
- Ensure 90% code coverage"
```

#### `chore`: Maintenance
```bash
git commit -m "chore: update composer dependencies

- Update PHPSpreadsheet to latest version
- Update PHPMailer for security patches
- Remove deprecated packages
- Update composer.lock file"
```

### Advanced Commit Examples

#### Breaking Changes
```bash
git commit -m "feat!: redesign lead status workflow

BREAKING CHANGE: Lead status values have changed
- Old statuses: New, Contacted, Qualified, Closed
- New statuses: New, Contacted, Qualified, Proposal, Won, Lost
- Migration script provided in database/migrations/
- Update any custom integrations accordingly

Closes #78"
```

#### Multiple Types
```bash
git commit -m "feat(auth): implement two-factor authentication

- Add TOTP-based 2FA support
- Create user preference for enabling 2FA
- Add backup codes for account recovery
- Update login process to handle 2FA
- Add admin panel for 2FA management

feat(security): enhance password requirements
- Minimum 12 characters with complexity rules
- Password history to prevent reuse
- Account lockout after failed attempts

Closes #56, #67"
```

## 🔀 Pull Request Process

### Creating Pull Requests

#### 1. Pre-Pull Request Checklist
```bash
# Before creating PR, ensure:
□ Feature branch is up to date with develop
□ All tests pass locally
□ Code follows project standards
□ Documentation is updated
□ No debug code or console.logs
```

#### 2. Pull Request Template
```markdown
## Description
Brief description of changes and motivation

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Manual testing completed
- [ ] Cross-browser testing (if UI changes)
- [ ] Mobile testing (if responsive changes)

## Screenshots
Add screenshots for UI changes

## Related Issues
Closes #123
Related to #456

## Checklist
- [ ] My code follows the project's style guidelines
- [ ] I have performed a self-review of my code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
```

### Pull Request Labels

#### Priority Labels
- `priority: high` - Critical fixes or urgent features
- `priority: medium` - Standard features and improvements
- `priority: low` - Nice-to-have enhancements

#### Type Labels
- `type: feature` - New functionality
- `type: bugfix` - Bug fixes
- `type: enhancement` - Improvements to existing features
- `type: documentation` - Documentation changes

#### Status Labels
- `status: in-review` - Under code review
- `status: needs-work` - Requires changes
- `status: ready-to-merge` - Approved and ready
- `status: blocked` - Blocked by dependencies

### Merge Requirements
```bash
# Before merging, ensure:
□ At least 1 approval from team lead
□ All CI/CD checks pass
□ No merge conflicts
□ Up to date with target branch
□ All conversations resolved
```

## 👥 Code Review Guidelines

### Review Responsibilities

#### As Author
1. **Provide Context** - Clear PR description and comments
2. **Self-Review** - Review your own code first
3. **Respond Promptly** - Address feedback within 24 hours
4. **Ask Questions** - Clarify unclear feedback
5. **Test Thoroughly** - Ensure functionality works as expected

#### As Reviewer
1. **Timely Reviews** - Complete reviews within 48 hours
2. **Constructive Feedback** - Provide helpful suggestions
3. **Focus on Important Issues** - Prioritize logic over style
4. **Ask Questions** - Understand the reasoning
5. **Approve When Ready** - Don't hold up good code

### Review Checklist

#### Code Quality
```bash
□ Code is readable and well-documented
□ Functions are small and focused
□ Variable names are descriptive
□ No code duplication
□ Error handling is appropriate
□ Security considerations addressed
```

#### Functionality
```bash
□ Code meets requirements
□ Edge cases are handled
□ Performance impact considered
□ Integration points tested
□ User experience is intuitive
```

#### Best Practices
```bash
□ Follows project conventions
□ Uses appropriate design patterns
□ Database queries are optimized
□ No hardcoded values
□ Proper logging implemented
```

### Review Comments

#### Constructive Feedback Examples
```markdown
# Good Examples
"Consider using a more descriptive variable name here for clarity"
"This logic could be extracted into a separate function for reusability"
"Have you considered the performance impact of this query on large datasets?"
"Great solution! This will make the code much more maintainable"

# Avoid
"This is wrong"
"Bad code"
"Change this"
```

#### Comment Categories
- **Must Fix** - Critical issues that must be addressed
- **Should Fix** - Important improvements
- **Consider** - Suggestions for improvement
- **Praise** - Acknowledge good work
- **Question** - Request clarification

## 🤝 Team Collaboration

### Team Structure
- **Project Owner**: Overall project responsibility and final decisions
- **Tech Lead**: Technical direction and architecture decisions
- **Senior Developer**: WoodpeckerLLM - Lead development and mentoring
- **Team Members**: Feature development and maintenance

### Collaboration Workflow

#### Daily Standup (Virtual)
```markdown
## Daily Standup Format
**What I did yesterday:**
- Completed feature X implementation
- Reviewed PR #123

**What I'm doing today:**
- Working on feature Y
- Code review for team member

**Blockers:**
- Need clarification on requirement Z
- Waiting for API documentation
```

#### Weekly Planning
```markdown
## Sprint Planning
- Review completed work
- Plan upcoming features
- Assign new tasks
- Discuss technical challenges
- Update project timeline
```

### Communication Channels

#### GitHub Communication
- **Issues** - Bug reports and feature requests
- **Pull Request Comments** - Code-specific discussions
- **Project Board** - Task tracking and progress
- **Wiki** - Long-form documentation

#### Code Collaboration
```bash
# Pair Programming Sessions
git checkout feature/complex-feature
git pull origin feature/complex-feature
# Work together on challenging problems

# Code Review Sessions
# Schedule regular review sessions for complex features
```

### Conflict Resolution

#### Merge Conflicts
```bash
# Resolve conflicts locally
git checkout feature/your-feature
git pull origin develop
git merge develop
# Resolve conflicts in IDE
git add .
git commit -m "resolve merge conflicts with develop"
git push origin feature/your-feature
```

#### Technical Disagreements
1. **Discussion** - Open GitHub issue for technical discussion
2. **Research** - Gather supporting information
3. **Prototype** - Create small proof of concepts
4. **Decision** - Tech lead makes final decision
5. **Documentation** - Record decision rationale

## 🚀 Release Management

### Release Process

#### 1. Release Planning
```bash
# Create release branch
git checkout develop
git pull origin develop
git checkout -b release/v1.3.0

# Update version numbers
# Update CHANGELOG.md
# Final testing
```

#### 2. Release Preparation
```markdown
## Pre-Release Checklist
□ All planned features completed
□ Critical bugs fixed
□ Documentation updated
□ Database migrations ready
□ Deployment scripts tested
□ Backup procedures verified
```

#### 3. Release Deployment
```bash
# Merge to main
git checkout main
git merge --no-ff release/v1.3.0
git tag -a v1.3.0 -m "Release version 1.3.0"

# Merge back to develop
git checkout develop
git merge --no-ff release/v1.3.0

# Push everything
git push origin main
git push origin develop
git push origin --tags

# Delete release branch
git branch -d release/v1.3.0
git push origin --delete release/v1.3.0
```

### Version Numbering
We use [Semantic Versioning](https://semver.org/):

```
MAJOR.MINOR.PATCH (e.g., 1.3.2)

- MAJOR: Breaking changes
- MINOR: New features (backward compatible)
- PATCH: Bug fixes (backward compatible)
```

### Release Notes Template
```markdown
# Release v1.3.0

## 🎉 New Features
- Lead scoring algorithm implementation
- Bulk export functionality
- Mobile-responsive dashboard

## 🐛 Bug Fixes
- Fixed email notification delivery
- Resolved dashboard loading issues
- Corrected Excel import validation

## 🔧 Improvements
- Enhanced database query performance
- Improved user interface responsiveness
- Updated security measures

## 🗃️ Database Changes
- Added lead_scores table
- Updated client_list schema
- Migration script: database/migrations/v1.3.0.sql

## 🚨 Breaking Changes
None

## 🔄 Migration Guide
1. Run database migration script
2. Update configuration files
3. Clear application cache
```

## 🚨 Hotfix Procedures

### Emergency Hotfix Process

#### 1. Hotfix Creation
```bash
# Create hotfix branch from main
git checkout main
git pull origin main
git checkout -b hotfix/critical-security-patch

# Make minimal necessary changes
# Test thoroughly
# Update version (patch increment)
```

#### 2. Hotfix Review
```markdown
## Hotfix Review Requirements
- Expedited review process (within 4 hours)
- Focus on security and stability
- Minimal scope - only critical fix
- Thorough testing required
```

#### 3. Hotfix Deployment
```bash
# Emergency deployment process
git checkout main
git merge --no-ff hotfix/critical-security-patch
git tag -a v1.2.1 -m "Hotfix: Critical security patch"

# Deploy immediately to production
# Monitor for issues

# Merge to develop
git checkout develop
git merge --no-ff hotfix/critical-security-patch

# Push and cleanup
git push origin main develop --tags
git branch -d hotfix/critical-security-patch
```

### Hotfix Communication
```markdown
## Emergency Communication Plan
1. Immediate notification to all stakeholders
2. Clear description of issue and fix
3. Deployment timeline and impact
4. Post-deployment verification steps
5. Follow-up communication with results
```

## 🎯 Best Practices

### Development Best Practices

#### Before Starting Work
```bash
□ Always start from updated develop branch
□ Create descriptive branch names
□ Understand requirements completely
□ Plan your approach
□ Set up local testing environment
```

#### During Development
```bash
□ Make atomic commits with clear messages
□ Test functionality thoroughly
□ Keep branches focused and small
□ Regular commits (at least daily)
□ Stay updated with develop branch
```

#### Before Submitting PR
```bash
□ Self-review all changes
□ Run all tests locally
□ Update relevant documentation
□ Check for merge conflicts
□ Ensure CI/CD pipeline passes
```

### Git Command Best Practices

#### Daily Commands
```bash
# Start of day
git checkout develop
git pull origin develop

# Before creating feature branch
git checkout -b feature/descriptive-name

# Regular commits
git add .
git commit -m "feat: implement specific functionality"

# Push frequently
git push origin feature/descriptive-name

# Stay updated
git checkout develop
git pull origin develop
git checkout feature/descriptive-name
git rebase develop
```

#### Useful Git Aliases
```bash
# Add to ~/.gitconfig
[alias]
    st = status
    co = checkout
    br = branch
    cm = commit -m
    ps = push
    pl = pull
    lg = log --oneline --graph --all
    unstage = reset HEAD --
    last = log -1 HEAD
    visual = !gitk
```

### Code Quality Standards

#### PHP Standards
```php
// Follow PSR-12 coding standard
// Use type hints
// Write PHPDoc comments
// Handle errors appropriately

/**
 * Calculate lead score based on multiple factors
 *
 * @param array $leadData Lead information
 * @param array $weights Scoring weights
 * @return int Calculated score (0-100)
 * @throws InvalidArgumentException If lead data is invalid
 */
public function calculateLeadScore(array $leadData, array $weights): int
{
    // Implementation here
}
```

#### JavaScript Standards
```javascript
// Use ES6+ features
// Write clear function names
// Add JSDoc comments
// Handle errors properly

/**
 * Update lead status in real-time
 * @param {number} leadId - The lead ID
 * @param {string} newStatus - New status value
 * @returns {Promise<boolean>} Success status
 */
async function updateLeadStatus(leadId, newStatus) {
    try {
        // Implementation here
    } catch (error) {
        console.error('Failed to update lead status:', error);
        throw error;
    }
}
```

## 🔧 Troubleshooting

### Common Git Issues

#### Merge Conflicts
```bash
# When you encounter merge conflicts
git status  # See conflicted files
# Edit files to resolve conflicts
git add .
git commit -m "resolve merge conflicts"
```

#### Accidentally Committed to Wrong Branch
```bash
# Move commits to correct branch
git log --oneline -5  # Find commit hash
git reset --hard HEAD~1  # Remove commit from current branch
git checkout correct-branch
git cherry-pick <commit-hash>
```

#### Push Rejected
```bash
# When push is rejected
git pull --rebase origin branch-name
# Resolve any conflicts
git push origin branch-name
```

#### Lost Work Recovery
```bash
# Find lost commits
git reflog
git checkout <commit-hash>
git branch recovery-branch  # Save recovered work
```

### Performance Issues

#### Large Repository
```bash
# Clean up repository
git gc --aggressive
git prune
git remote prune origin

# Shallow clone for faster setup
git clone --depth 1 <repository-url>
```

#### Slow Operations
```bash
# Check repository size
du -sh .git/

# Clean old branches
git branch -D old-branch-name
git push origin --delete old-branch-name
```

### Team Coordination Issues

#### Sync Issues
```markdown
## Daily Sync Protocol
1. Start day by pulling latest develop
2. Push work frequently (at least end of day)
3. Communicate when starting work on shared areas
4. Use draft PRs for work in progress
5. Coordinate on potential conflicts early
```

#### Communication Gaps
```markdown
## Communication Best Practices
- Use descriptive commit messages
- Comment on complex code sections
- Update issue status regularly
- Respond to reviews promptly
- Ask questions early and often
```

This Git workflow guide ensures smooth collaboration between team members while maintaining high code quality and project stability. Following these guidelines will help create a productive development environment for the LeadSharks LMS project.