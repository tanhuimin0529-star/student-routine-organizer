# Section 18 — GitHub Project Setup

---

## Overview

A well-configured GitHub project enables the team to track progress, review code, and coordinate work professionally. This section defines all recommended GitHub settings before development begins.

---

## 1. Repository Labels

Create the following labels in the GitHub repository under **Issues > Labels**:

| Label Name | Color | Description |
|-----------|-------|-------------|
| `type: feature` | 🟢 Green | New feature implementation |
| `type: bug` | 🔴 Red | Bug report or fix |
| `type: documentation` | 🔵 Blue | Documentation update |
| `type: testing` | 🟣 Purple | Test case writing or execution |
| `type: refactor` | 🟡 Yellow | Code restructure without behavior change |
| `type: chore` | ⚫ Gray | Config, gitignore, setup tasks |
| `module: authentication` | 🔵 Teal | Authentication module |
| `module: exercise` | 🟠 Orange | Exercise Tracker |
| `module: diary` | 🟣 Lavender | Diary Journal |
| `module: money` | 🟢 Light Green | Money Tracker |
| `module: habit` | 🟡 Gold | Habit Tracker |
| `module: admin` | 🔴 Dark Red | Admin Dashboard |
| `priority: high` | 🔴 Red | Urgent, must be resolved first |
| `priority: medium` | 🟡 Yellow | Normal priority |
| `priority: low` | ⚫ Gray | Nice to have, non-blocking |
| `status: in-progress` | 🔵 Blue | Currently being worked on |
| `status: review-needed` | 🟣 Purple | PR opened, waiting for review |
| `status: blocked` | 🔴 Red | Cannot proceed without unblocking |

---

## 2. GitHub Milestones

Create the following Milestones under **Issues > Milestones**:

| Milestone | Due Date | Description |
|-----------|----------|-------------|
| Week 1 — Planning & Foundation | End of Week 1 | Repo setup, docs, auth system |
| Week 2 — Module Development | End of Week 2 | Exercise, Diary, Money modules |
| Week 3 — Completion & Integration | End of Week 3 | Habit, Admin, integration testing |
| Week 4 — Testing & Submission | End of Week 4 | Full testing, final docs, submission |

---

## 3. GitHub Projects Board

Create a **Project Board** (Kanban-style) with the following columns:

| Column | Description |
|--------|-------------|
| **Backlog** | All planned issues not yet started |
| **In Progress** | Issues actively being worked on this week |
| **In Review** | PR opened, awaiting code review |
| **Testing** | Merged feature undergoing testing |
| **Done** | Completed, tested, and merged |

**Recommended Issues to Create at Project Start:**

- Issue: Setup repository folder structure
- Issue: Create all planning documentation
- Issue: Design database schema
- Issue: Implement `includes/` shared layer
- Issue: Implement authentication system
- Issue: Implement Exercise Tracker module
- Issue: Implement Diary Journal module
- Issue: Implement Money Tracker module
- Issue: Implement Habit Tracker module
- Issue: Implement Admin Dashboard
- Issue: Write and execute all test cases
- Issue: Finalize documentation and report

---

## 4. Issue Template

Create a file at `.github/ISSUE_TEMPLATE/feature_request.md`:

```
---
name: Feature / Task
about: New feature or task to be implemented
title: "[Feature]: "
labels: type: feature
assignees: ''
---

## Description
What needs to be implemented or done?

## Module
Which module does this relate to?

## Acceptance Criteria
What must be true for this issue to be considered complete?

- [ ] Criterion 1
- [ ] Criterion 2

## Additional Context
Any relevant context, screenshots, or references.
```

Create a file at `.github/ISSUE_TEMPLATE/bug_report.md`:

```
---
name: Bug Report
about: Report a bug or unexpected behavior
title: "[Bug]: "
labels: type: bug
assignees: ''
---

## Bug Description
Describe the bug concisely.

## Steps to Reproduce
1. Go to ...
2. Click ...
3. See error

## Expected Behavior
What should have happened?

## Actual Behavior
What actually happened?

## Module Affected
Which module is affected?

## Priority
- [ ] High
- [ ] Medium
- [ ] Low
```

---

## 5. Pull Request Template

Create a file at `.github/PULL_REQUEST_TEMPLATE.md`:

```
## Summary
Brief description of the changes made in this PR.

## Module
Which module does this PR affect?

## Changes Made
- File 1: What changed
- File 2: What changed

## How to Test Locally
Step-by-step instructions for a reviewer to test this feature.

## Test Cases Passed
- [ ] TC-XXX-01
- [ ] TC-XXX-02

## Checklist
- [ ] Code follows project naming conventions
- [ ] Client-side validation implemented where required
- [ ] Server-side validation implemented
- [ ] No `config/db_config.php` committed
- [ ] Branch is up to date with `develop`
- [ ] PR title follows commit convention: `feat: ...`
```

---

## 6. Commit Message Convention

Format: `[type]: [short imperative description]`

| Rule | Detail |
|------|--------|
| Type prefix required | `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore` |
| Imperative mood | "add login handler" not "added login handler" |
| Max 72 characters | Keep the first line concise |
| Optional body | Add more detail after a blank line if needed |

**Examples:**
```
feat: add money transaction add handler
fix: resolve duplicate habit check-in error
docs: update ER diagram entity descriptions
style: update dashboard card hover effects
test: add auth session timeout test case
chore: add db_config.php to gitignore
```

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
