---
description: Initialize a new specification with detailed project description and requirements
allowed-tools: Bash, Read, Write, Glob
argument-hint: <project-description> | issue:<number> | #<number>
---

# Spec Initialization

Initialize a new specification based on the provided project description or GitHub issue:

**Input**: $ARGUMENTS

## Context Validation

### Steering Context
- Architecture context: @.kiro/steering/structure.md
- Technical constraints: @.kiro/steering/tech.md
- Product context: @.kiro/steering/product.md
- Custom steering: Load all "Always" mode custom steering files from .kiro/steering/

## Argument Patterns

1. **Direct Description**: `/kiro:spec-init "project description text"`
2. **GitHub Issue**: `/kiro:spec-init issue:1` or `/kiro:spec-init #1`

## Task: Initialize Specification Structure

**SCOPE**: This command initializes the directory structure and metadata based on the provided input (direct description or GitHub issue).

### 1. Input Analysis and Data Acquisition

**Step 1.1: Determine Input Type**
Analyze $ARGUMENTS to determine if it's a GitHub issue reference or direct description:
- Pattern `issue:\d+` or `#\d+`: Extract issue number and fetch from GitHub
- Otherwise: Treat as direct project description

**Step 1.2: GitHub Issue Data Retrieval (if applicable)**
If GitHub issue pattern detected:
1. Extract issue number from $ARGUMENTS
2. Execute: `gh api repos/:owner/:repo/issues/<number>` to fetch issue data
3. Handle potential errors:
   - GitHub CLI not authenticated: Show `gh auth login` instruction
   - Issue not found: Show error with repo/issue number
   - Permission denied: Show access rights message
4. Parse JSON response to extract:
   - `title`: Issue title
   - `body`: Issue description
   - `labels`: Issue labels array
   - `html_url`: Issue URL
   - `milestone`: Milestone information (if any)
   - `assignees`: Assigned users (if any)

**Step 1.3: Content Analysis**
For GitHub issues, analyze the body content to extract:
- **Technology Stack**: Look for technology mentions (programming languages, frameworks, databases, tools)
- **Requirements Hints**: Extract TODO items, checkboxes, and requirement statements
- **Project Structure**: Identify file/folder structure mentions and directory layouts
- **Configuration Items**: Port numbers, service names, development setup details, environment configurations

### 2. Generate Feature Name
Create a concise, descriptive feature name:
- **From GitHub Issue**: Use issue title as base (sanitized for filesystem)
- **From Description**: Use provided text as base
**Check existing `.kiro/specs/` directory to ensure the generated feature name is unique. If a conflict exists, append a number suffix (e.g., feature-name-2).**

### 2. Create Spec Directory
Create `.kiro/specs/[generated-feature-name]/` directory with:
- `spec.json` - Metadata and approval tracking
- `requirements.md` - Lightweight template with project description

**Note**: design.md and tasks.md will be created by their respective commands during the development process.

### 3. Initialize spec.json Metadata

**For Direct Description Input:**
```json
{
  "feature_name": "[generated-feature-name]",
  "created_at": "current_timestamp",
  "updated_at": "current_timestamp",
  "language": "ja",
  "phase": "initialized",
  "source": {
    "type": "direct_description",
    "input": "[original-arguments]"
  },
  "approvals": {
    "requirements": {
      "generated": false,
      "approved": false
    },
    "design": {
      "generated": false,
      "approved": false
    },
    "tasks": {
      "generated": false,
      "approved": false
    }
  },
  "ready_for_implementation": false
}
```

**For GitHub Issue Input (Enhanced):**
```json
{
  "feature_name": "[generated-from-issue-title]",
  "created_at": "current_timestamp",
  "updated_at": "current_timestamp",
  "language": "ja",
  "phase": "initialized",
  "source": {
    "type": "github_issue",
    "issue_number": "[extracted-number]",
    "url": "[issue-html-url]",
    "title": "[issue-title]",
    "labels": ["label1", "label2"],
    "milestone": "[milestone-title]",
    "assignees": ["username1", "username2"]
  },
  "extracted_info": {
    "tech_stack": {
      "backend": "[automatically-detected-backend-technologies]",
      "frontend": "[automatically-detected-frontend-technologies]",
      "infrastructure": "[automatically-detected-infrastructure-tools]",
      "tools": "[automatically-detected-development-tools]"
    },
    "requirements_hints": [
      "[automatically-extracted-requirement-hint-1]",
      "[automatically-extracted-requirement-hint-2]",
      "[automatically-extracted-requirement-hint-3]"
    ],
    "project_structure": [
      "[automatically-detected-file-path-1]",
      "[automatically-detected-file-path-2]",
      "[automatically-detected-file-path-3]"
    ],
    "services": {
      "[service-name-1]": {"port": "[detected-port]"},
      "[service-name-2]": {"port": "[detected-port]"},
      "[service-name-3]": {"api_port": "[detected-port]", "console_port": "[detected-port]"}
    },
    "todo_items": [
      "[automatically-imported-todo-item-1]",
      "[automatically-imported-todo-item-2]",
      "[automatically-imported-todo-item-3]"
    ]
  },
  "approvals": {
    "requirements": {
      "generated": false,
      "approved": false
    },
    "design": {
      "generated": false,
      "approved": false
    },
    "tasks": {
      "generated": false,
      "approved": false
    }
  },
  "ready_for_implementation": false
}
```

### 4. Create Requirements Template

**For Direct Description Input:**
```markdown
# Requirements Document

## Project Description (Input)
$ARGUMENTS

## Requirements
<!-- Will be generated in /kiro:spec-requirements phase -->
```

**For GitHub Issue Input (Enhanced):**
```markdown
# Requirements Document

## GitHub Issue Information

**Issue**: [#N]([issue-url]) - [issue-title]
**Labels**: [label1], [label2]
**Milestone**: [milestone-title]
**Assignees**: @[username1], @[username2]

### Original Issue Description
[issue-body-content]

## Extracted Information

### Technology Stack
[Automatically detected technologies from issue content]
**Backend**: [detected-backend-technologies]
**Frontend**: [detected-frontend-technologies]
**Infrastructure**: [detected-infrastructure-tools]
**Tools**: [detected-development-tools]

### Project Structure
[Automatically extracted file/folder structure from issue]
```
[detected-project-structure]
```

### Development Services Configuration
[Automatically detected service configurations and ports]
- [Service Name]: [Port/Configuration]
- [Service Name]: [Port/Configuration]

### Requirements Hints
Based on issue analysis:
[Automatically extracted requirement hints from issue content]
- [Requirement hint 1]
- [Requirement hint 2]
- [Requirement hint 3]

### TODO Items from Issue
[Automatically imported TODO items and checkboxes from issue]
- [ ] [TODO item 1]
- [ ] [TODO item 2]
- [ ] [TODO item 3]

## Requirements
<!-- Will be generated in /kiro:spec-requirements phase -->
```

### 5. Update CLAUDE.md Reference
Add the new spec to the active specifications list with the generated feature name and a brief description.

## Next Steps After Initialization

Follow the strict spec-driven development workflow:
1. **`/kiro:spec-requirements <feature-name>`** - Create and generate requirements.md
2. **`/kiro:spec-design <feature-name>`** - Create and generate design.md (requires approved requirements)
3. **`/kiro:spec-tasks <feature-name>`** - Create and generate tasks.md (requires approved design)

**Important**: Each phase creates its respective file and requires approval before proceeding to the next phase.

## Error Handling

### GitHub CLI Issues
- **Not installed**: Guide user to install GitHub CLI (`brew install gh` on macOS)
- **Not authenticated**: Show authentication command: `gh auth login`
- **Permission denied**: Explain need for repository access permissions
- **Rate limit exceeded**: Show retry guidance and rate limit status

### API Response Issues
- **Issue not found**: Verify issue number and repository access
- **Empty issue body**: Use only title and available metadata
- **Malformed JSON**: Provide user-friendly error with debugging steps
- **Network connectivity**: Show offline mode limitations

### File System Issues
- **Permission errors**: Clear guidance on directory permissions
- **Disk space**: Check available space before file creation
- **Existing specifications**: Handle naming conflicts gracefully

### Recovery Strategies
- **Partial failure**: Clean up created files and restart
- **Validation errors**: Provide specific error messages with correction guidance
- **Backup and restore**: Maintain rollback capability for failed operations

## Enhanced Output Format

### For Successful GitHub Issue Processing:
After initialization, provide:
1. **Issue Information**: `Issue #N: [title]` with GitHub URL
2. **Generated feature name and rationale**: Based on issue title
3. **Extracted information summary**: Tech stack, structure, requirements
4. **Created files**: spec.json and requirements.md paths
5. **Clear next step**: `/kiro:spec-requirements <feature-name>`
6. **Explanation**: Enhanced initialization with GitHub issue context

### For Direct Description Processing:
After initialization, provide:
1. **Input summary**: Brief description provided
2. **Generated feature name and rationale**: Based on description analysis
3. **Created files**: spec.json and requirements.md paths
4. **Clear next step**: `/kiro:spec-requirements <feature-name>`
5. **Explanation**: Standard initialization process completed

### For Error Cases:
1. **Clear error message**: What went wrong and why
2. **Suggested resolution**: Specific steps to resolve the issue
3. **Alternative approaches**: Fallback options (e.g., use direct description instead)
4. **Support information**: Where to get help if issue persists
