# Contributing to LeadSharks LMS

Thank you for your interest in contributing to LeadSharks LMS! This document provides guidelines and information for contributors.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Process](#pull-request-process)
- [Testing Guidelines](#testing-guidelines)
- [Documentation Guidelines](#documentation-guidelines)
- [Issue Reporting](#issue-reporting)
- [Security Reporting](#security-reporting)

## 📜 Code of Conduct

### Our Pledge
We are committed to making participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards
Examples of behavior that contributes to creating a positive environment include:
- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

### Enforcement
Project maintainers are responsible for clarifying the standards of acceptable behavior and are expected to take appropriate and fair corrective action in response to any instances of unacceptable behavior.

## 🚀 Getting Started

### Prerequisites
Before contributing, ensure you have:
- Read the project documentation thoroughly
- Set up your development environment (see [DEVELOPMENT_SETUP.md](docs/DEVELOPMENT_SETUP.md))
- Understood the project architecture (see [PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md))
- Familiarized yourself with our Git workflow (see [GIT_WORKFLOW.md](docs/GIT_WORKFLOW.md))

### First Contribution
1. **Fork the repository** on GitHub
2. **Clone your fork** locally
3. **Set up the development environment** following our guide
4. **Create a feature branch** for your contribution
5. **Make your changes** following our coding standards
6. **Test thoroughly** to ensure functionality works
7. **Submit a pull request** with a clear description

### Areas for Contribution
We welcome contributions in the following areas:
- 🐛 **Bug Fixes** - Fix identified issues and bugs
- ✨ **New Features** - Add new functionality to the LMS
- 🔧 **Improvements** - Enhance existing features and performance
- 📚 **Documentation** - Improve documentation and guides
- 🧪 **Testing** - Add test coverage and quality assurance
- 🎨 **UI/UX** - Improve user interface and experience
- 🔒 **Security** - Enhance security measures and practices

## 🔄 Development Workflow

### Branch Strategy
We use Git Flow for our development workflow:

```
main          Production-ready code
develop       Integration branch
feature/*     Feature development
release/*     Release preparation  
hotfix/*      Critical production fixes
```

### Workflow Steps
1. **Start from develop**: Always create feature branches from `develop`
2. **Create feature branch**: Use descriptive naming (e.g., `feature/lead-scoring-system`)
3. **Develop incrementally**: Make small, focused commits
4. **Stay updated**: Regularly sync with develop branch
5. **Test thoroughly**: Ensure all functionality works correctly
6. **Create pull request**: Submit for review with clear description
7. **Address feedback**: Respond to review comments promptly
8. **Merge when approved**: Code is merged after approval

### Example Workflow
```bash
# Start new feature
git checkout develop
git pull origin develop
git checkout -b feature/improve-dashboard-performance

# Make changes and commit
git add .
git commit -m "feat: optimize database queries for dashboard

- Add database indexes for frequently accessed tables
- Implement query caching for repeated operations
- Reduce N+1 queries in lead listing
- Improve page load time by 40%

Closes #234"

# Push and create PR
git push origin feature/improve-dashboard-performance
# Create pull request on GitHub
```

## 📝 Coding Standards

### PHP Standards
We follow **PSR-12** coding standard for PHP:

#### Code Style
```php
<?php

declare(strict_types=1);

namespace LeadSharks\LMS\Classes;

/**
 * Lead management class
 */
class LeadManager
{
    private DatabaseConnection $db;
    
    public function __construct(DatabaseConnection $db)
    {
        $this->db = $db;
    }
    
    /**
     * Calculate lead score based on criteria
     *
     * @param array $leadData Lead information
     * @param array $weights Scoring weights  
     * @return int Calculated score (0-100)
     * @throws InvalidArgumentException If lead data is invalid
     */
    public function calculateScore(array $leadData, array $weights): int
    {
        if (empty($leadData['email'])) {
            throw new InvalidArgumentException('Lead email is required');
        }
        
        $score = 0;
        
        // Calculate score based on criteria
        foreach ($weights as $criterion => $weight) {
            $score += $this->evaluateCriterion($leadData, $criterion) * $weight;
        }
        
        return min(100, max(0, $score));
    }
}
```

#### Key Principles
- **Type hints**: Use type declarations for parameters and return values
- **Documentation**: Write comprehensive PHPDoc comments
- **Error handling**: Use exceptions for error conditions
- **Naming**: Use descriptive names for variables and functions
- **Single responsibility**: Each class/method should have one clear purpose

### JavaScript Standards
Use modern ES6+ JavaScript with clear, readable code:

```javascript
/**
 * Update lead status with animation and feedback
 * @param {number} leadId - The lead ID to update
 * @param {string} newStatus - New status value
 * @returns {Promise<boolean>} Success status
 */
async function updateLeadStatus(leadId, newStatus) {
    try {
        showLoadingIndicator();
        
        const response = await fetch('/admin/leads/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                lead_id: leadId,
                status: newStatus
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            updateStatusDisplay(leadId, newStatus);
            showSuccessMessage('Status updated successfully');
            return true;
        } else {
            throw new Error(result.message || 'Failed to update status');
        }
    } catch (error) {
        console.error('Error updating lead status:', error);
        showErrorMessage('Failed to update status. Please try again.');
        return false;
    } finally {
        hideLoadingIndicator();
    }
}
```

### SQL Standards
Write clear, optimized SQL queries:

```sql
-- Use descriptive aliases and proper formatting
SELECT 
    cl.id,
    cl.company_name,
    cl.contact,
    cl.status,
    u.firstname AS assigned_agent,
    COUNT(ih.id) AS interaction_count
FROM client_list cl
INNER JOIN users u ON cl.assigned_to = u.id
LEFT JOIN interaction_history ih ON cl.id = ih.client_id
WHERE cl.status IN ('qualified', 'interested')
    AND cl.date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY cl.id, cl.company_name, cl.contact, cl.status, u.firstname
ORDER BY cl.date_updated DESC
LIMIT 50;

-- Add proper indexes for performance
CREATE INDEX idx_client_status_date ON client_list(status, date_created);
CREATE INDEX idx_interaction_client ON interaction_history(client_id);
```

### HTML/CSS Standards
Use semantic HTML and organized CSS:

```html
<!-- Semantic HTML structure -->
<article class="lead-card" data-lead-id="123">
    <header class="lead-card__header">
        <h3 class="lead-card__title">Company Name</h3>
        <span class="lead-card__status lead-card__status--qualified">Qualified</span>
    </header>
    
    <div class="lead-card__content">
        <p class="lead-card__contact">john@company.com</p>
        <p class="lead-card__phone">+1-555-0123</p>
    </div>
    
    <footer class="lead-card__actions">
        <button class="btn btn-primary btn-sm" data-action="call">Call</button>
        <button class="btn btn-secondary btn-sm" data-action="email">Email</button>
    </footer>
</article>
```

```css
/* BEM methodology for CSS */
.lead-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: box-shadow 0.2s ease;
}

.lead-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.lead-card__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.lead-card__status--qualified {
    background-color: #28a745;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
}
```

## 💌 Commit Guidelines

### Commit Message Format
We use [Conventional Commits](https://conventionalcommits.org/) specification:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Commit Types
- **feat**: New feature for the user
- **fix**: Bug fix for the user
- **docs**: Documentation changes
- **style**: Formatting, missing semicolons, etc.
- **refactor**: Code change that neither fixes a bug nor adds a feature
- **perf**: Performance improvements
- **test**: Adding missing tests or correcting existing tests
- **chore**: Changes to the build process or auxiliary tools
- **ci**: Changes to CI configuration files and scripts

### Examples

#### Feature Addition
```bash
git commit -m "feat(leads): implement lead scoring algorithm

Add weighted scoring system for lead qualification:
- Industry-specific scoring rules
- Configurable weight system via admin panel  
- Real-time score calculation and display
- Integration with existing lead management workflow

The algorithm considers company size, industry, budget,
and engagement level to provide 0-100 score range.

Closes #145
Refs #134, #167"
```

#### Bug Fix
```bash
git commit -m "fix(email): resolve notification delivery failures

- Fix SMTP configuration validation
- Add connection timeout handling
- Implement retry mechanism for failed sends
- Update error logging for better debugging

Fixes #223"
```

#### Breaking Change
```bash
git commit -m "feat!: redesign user authentication system

BREAKING CHANGE: Authentication API has been redesigned

- New JWT-based authentication replaces session-based
- Updated login endpoints and response format
- Added two-factor authentication support
- Improved security with better password hashing

Migration guide available in docs/MIGRATION.md
Existing sessions will be invalidated on deployment

Closes #178"
```

## 🔀 Pull Request Process

### Before Submitting
- [ ] Code follows project style guidelines
- [ ] All tests pass locally
- [ ] Changes are documented appropriately
- [ ] Commit messages follow convention
- [ ] Branch is up to date with develop
- [ ] No merge conflicts exist
- [ ] Self-review completed

### PR Description Template
Use our PR template to provide comprehensive information:

```markdown
## Description
Brief description of the changes made.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Related Issues
Closes #123
Related to #456

## Testing
- [ ] Unit tests added/updated
- [ ] Manual testing completed
- [ ] Cross-browser testing (for UI changes)
- [ ] Performance impact evaluated

## Screenshots
[Add screenshots for UI changes]

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] Tests added/updated
- [ ] No new warnings generated
```

### Review Process
1. **Automated Checks**: CI/CD pipeline must pass
2. **Code Review**: At least one approval required
3. **Testing**: Functionality tested in review environment
4. **Documentation**: Ensure docs are updated if needed
5. **Approval**: Final approval from code owner
6. **Merge**: Squash and merge to maintain clean history

## 🧪 Testing Guidelines

### Test Categories

#### Unit Tests
Test individual functions and methods:

```php
<?php
use PHPUnit\Framework\TestCase;

class LeadManagerTest extends TestCase
{
    private LeadManager $leadManager;
    
    protected function setUp(): void
    {
        $this->leadManager = new LeadManager($this->createMock(DatabaseConnection::class));
    }
    
    public function testCalculateScoreWithValidData(): void
    {
        $leadData = [
            'email' => 'test@example.com',
            'company_size' => 'large',
            'budget' => 50000
        ];
        
        $weights = [
            'company_size' => 30,
            'budget' => 40
        ];
        
        $score = $this->leadManager->calculateScore($leadData, $weights);
        
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }
    
    public function testCalculateScoreThrowsExceptionForInvalidData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Lead email is required');
        
        $this->leadManager->calculateScore([], []);
    }
}
```

#### Integration Tests
Test component interactions:

```php
public function testLeadCreationWorkflow(): void
{
    // Test complete lead creation process
    $leadData = $this->getValidLeadData();
    
    // Create lead
    $leadId = $this->leadManager->createLead($leadData);
    $this->assertIsInt($leadId);
    
    // Verify database insertion
    $savedLead = $this->leadManager->getLeadById($leadId);
    $this->assertEquals($leadData['email'], $savedLead['email']);
    
    // Test status update
    $this->leadManager->updateStatus($leadId, 'contacted');
    $updatedLead = $this->leadManager->getLeadById($leadId);
    $this->assertEquals('contacted', $updatedLead['status']);
}
```

#### Frontend Tests
Test JavaScript functionality:

```javascript
// Using Jest for JavaScript testing
describe('Lead Status Update', () => {
    beforeEach(() => {
        // Setup DOM elements
        document.body.innerHTML = `
            <div class="lead-card" data-lead-id="123">
                <span class="status">new</span>
            </div>
        `;
    });
    
    test('should update status display after successful API call', async () => {
        // Mock fetch API
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ success: true })
        });
        
        const result = await updateLeadStatus(123, 'contacted');
        
        expect(result).toBe(true);
        expect(fetch).toHaveBeenCalledWith('/admin/leads/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                lead_id: 123,
                status: 'contacted'
            })
        });
    });
});
```

### Testing Best Practices
- **Test Coverage**: Aim for >80% code coverage
- **Test Independence**: Tests should not depend on each other
- **Descriptive Names**: Use clear, descriptive test method names
- **Edge Cases**: Test boundary conditions and error scenarios
- **Performance**: Include performance tests for critical functionality

## 📚 Documentation Guidelines

### Code Documentation

#### PHP Documentation
Use PHPDoc standards:

```php
/**
 * Calculate lead conversion probability
 *
 * Analyzes lead data and historical patterns to determine
 * the likelihood of successful conversion to client.
 *
 * @param array $leadData Lead information including contact details and preferences
 * @param array $historicalData Past conversion data for pattern analysis
 * @param int $timeframe Analysis timeframe in days (default: 30)
 * 
 * @return float Conversion probability as decimal (0.0 to 1.0)
 * 
 * @throws InvalidArgumentException When lead data is incomplete
 * @throws DatabaseException When historical data cannot be retrieved
 * 
 * @since 2.1.0
 * @see LeadAnalyzer::getHistoricalData()
 * 
 * @example
 * $probability = $analyzer->calculateConversionProbability(
 *     ['email' => 'john@company.com', 'industry' => 'tech'],
 *     $historicalData,
 *     45
 * );
 */
public function calculateConversionProbability(
    array $leadData, 
    array $historicalData, 
    int $timeframe = 30
): float {
    // Implementation here
}
```

#### JavaScript Documentation
Use JSDoc format:

```javascript
/**
 * Manages lead interaction tracking and analytics
 * @class
 */
class LeadTracker {
    /**
     * Track user interaction with lead card
     * @param {Object} interaction - Interaction details
     * @param {string} interaction.type - Type of interaction (click, hover, etc.)
     * @param {number} interaction.leadId - Lead ID being interacted with
     * @param {number} interaction.duration - Interaction duration in milliseconds
     * @param {Object} [interaction.metadata={}] - Additional interaction data
     * @returns {Promise<boolean>} Success status of tracking
     * @throws {Error} When lead ID is invalid or tracking fails
     * @example
     * const tracker = new LeadTracker();
     * await tracker.trackInteraction({
     *     type: 'click',
     *     leadId: 123,
     *     duration: 2500,
     *     metadata: { section: 'contact-info' }
     * });
     */
    async trackInteraction(interaction) {
        // Implementation here
    }
}
```

### API Documentation
Document all API endpoints:

```markdown
## Update Lead Status

Updates the status of an existing lead.

### Endpoint
```
POST /admin/leads/update_status.php
```

### Headers
```
Content-Type: application/json
X-Requested-With: XMLHttpRequest
```

### Request Body
```json
{
    "lead_id": 123,
    "status": "contacted",
    "notes": "Initial contact made via phone"
}
```

### Response
#### Success (200 OK)
```json
{
    "success": true,
    "message": "Status updated successfully",
    "data": {
        "lead_id": 123,
        "old_status": "new",
        "new_status": "contacted",
        "updated_at": "2024-11-06T10:30:00Z"
    }
}
```

#### Error (400 Bad Request)
```json
{
    "success": false,
    "error": "INVALID_STATUS",
    "message": "The provided status is not valid"
}
```

### Error Codes
- `INVALID_LEAD_ID`: Lead ID is missing or invalid
- `INVALID_STATUS`: Status value is not in allowed list
- `PERMISSION_DENIED`: User lacks permission to update lead
- `DATABASE_ERROR`: Database operation failed
```

## 🐛 Issue Reporting

### Bug Reports
When reporting bugs, provide:

1. **Clear Title**: Descriptive summary of the issue
2. **Environment**: Browser, PHP version, database version
3. **Steps to Reproduce**: Detailed steps to recreate the bug
4. **Expected Behavior**: What should happen
5. **Actual Behavior**: What actually happens
6. **Screenshots/Videos**: Visual evidence if applicable
7. **Error Messages**: Any error messages or logs
8. **Additional Context**: Any other relevant information

### Feature Requests
For feature requests, include:

1. **Problem Statement**: What problem does this solve?
2. **Proposed Solution**: Describe your suggested solution
3. **User Story**: As a [user type], I want [goal] so that [benefit]
4. **Acceptance Criteria**: How will we know it's complete?
5. **Mockups/Wireframes**: Visual designs if applicable
6. **Priority Level**: How important is this feature?

## 🔒 Security Reporting

### Reporting Security Issues
**DO NOT** report security vulnerabilities in public issues. Instead:

1. **Email directly** to the project maintainers
2. **Provide detailed information** about the vulnerability
3. **Include steps to reproduce** if possible
4. **Wait for acknowledgment** before public disclosure
5. **Follow responsible disclosure** practices

### Security Review Process
1. **Acknowledgment** within 48 hours
2. **Initial assessment** within 1 week
3. **Regular updates** on progress
4. **Coordinated disclosure** when fixed
5. **Credit given** to reporter (if desired)

---

## 🎯 Getting Help

### Resources
- **Documentation**: Check project docs first
- **Issues**: Search existing issues before creating new ones
- **Discussions**: Use GitHub Discussions for questions
- **Code Review**: Request help during PR reviews

### Response Times
- **Critical Issues**: 4 hours
- **Bug Reports**: 24 hours  
- **Feature Requests**: 48 hours
- **General Questions**: 72 hours

Thank you for contributing to LeadSharks LMS! Your contributions help make this project better for everyone. 🚀