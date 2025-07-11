# GitHub Issue Templates & Common Responses

## Issue Templates

### Bug Report Template
```markdown
## Bug Description
[Clear and concise description of the bug]

## Steps to Reproduce
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior
[What you expected to happen]

## Actual Behavior
[What actually happened]

## Environment
- **Plugin Version**: [e.g., 1.1.19]
- **WordPress Version**: [e.g., 6.6.1]
- **BMLT Version**: [e.g., 3.x]
- **PHP Version**: [e.g., 8.1]
- **Browser**: [e.g., Chrome 120]

## Additional Context
- Screenshots if applicable
- Error messages from browser console
- WordPress debug log entries
- Any customizations or other plugins that might be relevant
```

### Feature Request Template
```markdown
## Feature Description
[Clear and concise description of the feature you'd like to see]

## Problem Statement
[Describe the problem this feature would solve]

## Proposed Solution
[Describe how you envision this feature working]

## Alternative Solutions
[Any alternative solutions you've considered]

## Additional Context
- Screenshots or mockups if applicable
- Related issues or discussions
- Impact on existing functionality
```

### Enhancement Request Template
```markdown
## Current Behavior
[Describe how the current feature works]

## Desired Enhancement
[Describe the improvement you'd like to see]

## Benefits
[How this enhancement would improve the user experience]

## Implementation Considerations
[Any thoughts on how this might be implemented]

## Additional Context
- Screenshots showing current vs desired behavior
- Related features or workflows
```

## Pull Request Templates

### Feature PR Template
```markdown
## Description
[Brief description of the feature being added]

## Related Issue
Closes #[issue-number]

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [x] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [x] PHPUnit tests added/updated
- [x] TestCafe E2E tests added/updated
- [x] Manual testing completed
- [x] Tested on different BMLT versions (2.x, 3.x)

## Checklist
- [x] Code follows project coding standards
- [x] Self-review of code completed
- [x] Code is commented where necessary
- [x] Documentation updated
- [x] Version numbers updated (if applicable)
- [x] CHANGELOG.md updated

## Screenshots
[If applicable, add screenshots showing the new feature]
```

### Bug Fix PR Template
```markdown
## Description
[Brief description of the bug fix]

## Related Issue
Fixes #[issue-number]

## Type of Change
- [x] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Root Cause
[Brief explanation of what caused the bug]

## Solution
[Brief explanation of how the fix works]

## Testing
- [x] PHPUnit tests added/updated
- [x] TestCafe E2E tests added/updated
- [x] Manual testing completed
- [x] Verified fix resolves the reported issue

## Checklist
- [x] Code follows project coding standards
- [x] Self-review of code completed
- [x] Code is commented where necessary
- [x] Version numbers updated (if applicable)
- [x] CHANGELOG.md updated

## Before/After
[If applicable, add screenshots showing before and after the fix]
```

## Common Issue Responses

### Acknowledging a Bug Report
```markdown
Thank you for reporting this issue! I can confirm this is a bug in the BMLT Workflow plugin.

**Issue Confirmed**: [Brief description of the confirmed issue]

**Next Steps**:
1. I'll create a branch to investigate and fix this issue
2. The fix will be tested thoroughly before release
3. I'll update this issue with progress and the fix will be included in the next release

**Timeline**: I expect to have a fix ready within [timeframe]

Please let me know if you have any additional information or if you'd like to test the fix once it's ready.
```

### Requesting More Information
```markdown
Thank you for your report! To help me investigate this issue effectively, could you please provide:

**Additional Information Needed**:
- [ ] WordPress debug log entries (if any errors are shown)
- [ ] Browser console errors (F12 → Console tab)
- [ ] Steps to reproduce the issue more precisely
- [ ] Screenshots showing the problem
- [ ] Information about your BMLT server version
- [ ] Any customizations or other plugins that might be relevant

**Environment Details**:
- Plugin Version: [Please confirm]
- WordPress Version: [Please confirm]
- BMLT Version: [Please confirm]
- PHP Version: [Please confirm]

Once I have this information, I'll be able to investigate and provide a solution more quickly.
```

### Feature Request Response
```markdown
Thank you for your feature request! This is an interesting idea that could improve the BMLT Workflow plugin.

**Evaluation Process**:
1. I'll review this request against our current roadmap
2. Consider the impact on existing functionality
3. Assess the development effort required
4. Determine if it aligns with the plugin's goals

**Next Steps**:
- [ ] Add to feature request backlog
- [ ] Evaluate priority and feasibility
- [ ] Update this issue with decision and timeline

**Questions for Clarification**:
- [Any specific questions about the feature]

I'll keep you updated on the progress of this request. Thank you for contributing to the plugin's development!
```

### Closing Resolved Issues
```markdown
This issue has been resolved in version [version-number].

**Resolution**:
- [Brief description of the fix]

**Release Information**:
- The fix is now available in version [version-number]
- You can download the latest version from [WordPress.org link]
- The fix has been tested and verified

**Next Steps**:
1. Update to the latest version
2. Test the fix in your environment
3. Let me know if you encounter any issues

Thank you for your patience and for helping improve the BMLT Workflow plugin!
```

### Release Announcement
```markdown
## 🎉 BMLT Workflow v[version-number] Released!

**What's New**:
- [List of new features]

**Bug Fixes**:
- [List of bug fixes]

**Improvements**:
- [List of improvements]

**Download**: [WordPress.org link]

**Full Changelog**: [Link to CHANGELOG.md]

Thank you to everyone who contributed to this release through bug reports, feature requests, and testing!

---

**Upgrade Instructions**:
1. Backup your WordPress site
2. Update the plugin through WordPress admin
3. Test the new functionality
4. Report any issues in a new GitHub issue

**Support**: If you encounter any issues, please create a new GitHub issue with detailed information.
```

## Issue Labels

### Recommended Labels
- `bug` - Something isn't working
- `enhancement` - New feature or request
- `documentation` - Improvements or additions to documentation
- `good first issue` - Good for newcomers
- `help wanted` - Extra attention is needed
- `invalid` - Something doesn't seem right
- `question` - Further information is requested
- `wontfix` - This will not be worked on
- `duplicate` - This issue or pull request already exists
- `bmlt2x` - Related to BMLT 2.x compatibility
- `bmlt3x` - Related to BMLT 3.x compatibility
- `frontend` - Frontend/UI related
- `backend` - Backend/PHP related
- `testing` - Testing related
- `release` - Release related
- `critical` - Critical issue requiring immediate attention
- `low-priority` - Low priority issue
- `high-priority` - High priority issue 