# Section 12 — Git Branch Strategy

---

## Overview

The project uses a **Feature Branch Workflow** derived from the Gitflow model, scaled for a 4-person team over a 4-week timeline.

---

## Branch Structure

```
main
└── develop
    ├── feature/authentication
    ├── feature/exercise
    ├── feature/diary
    ├── feature/money
    ├── feature/habit
    └── feature/admin
```

---

## Branch Descriptions

| Branch | Owner | Purpose |
|--------|-------|---------|
| `main` | Member 1 (Team Lead) | Production-ready, stable code. Only merged from `develop` at milestones. |
| `develop` | All Members | Integration branch. All feature branches merge here for testing. |
| `feature/authentication` | Member 1 | Login, register, logout, session management, shared includes |
| `feature/exercise` | Member 2 | Exercise Tracker module and assets |
| `feature/diary` | Member 3 | Diary Journal module and assets |
| `feature/money` | Member 3 | Money Tracker module and assets |
| `feature/habit` | Member 4 | Habit Tracker module and assets |
| `feature/admin` | Member 4 | Admin dashboard and user management |

---

## Branch Rules

### `main`
- Never directly commit to `main`.
- Only the Team Lead (Member 1) merges into `main`.
- Merges to `main` happen at the end of each major milestone (end of Week 2 and end of Week 4).
- The `main` branch should always represent the latest stable, tested build.
- Tag each `main` merge with a version: `v0.1.0` (mid-project), `v1.0.0` (final submission).

### `develop`
- Integration branch. All feature branches merge here via Pull Requests.
- Developers should pull from `develop` regularly to stay up to date.
- `develop` may contain work-in-progress code and is not always stable.

### `feature/*` branches
- Each developer creates their branch from `develop`.
- Never branch off from `main` for features.
- Work is committed frequently in small, focused increments.
- When a feature is complete and tested locally, a Pull Request is opened to merge into `develop`.

---

## When to Merge

| Trigger | Action |
|---------|--------|
| Feature complete and locally tested | Open PR from `feature/*` → `develop` |
| PR reviewed and approved by at least 1 team member | Merge `feature/*` → `develop` |
| All modules integrated and integration-tested | Merge `develop` → `main` (tagged release) |
| Bug fix needed on a merged feature | Create `hotfix/bug-description` branch from `develop`, fix, merge back |

---

## Pull Request (PR) Workflow

1. Developer pushes their feature branch to GitHub.
2. Developer opens a Pull Request from `feature/[name]` to `develop`.
3. PR title follows commit convention (see below).
4. PR description must include:
   - What was implemented.
   - Files changed.
   - How to test the feature locally.
   - Any known issues or blockers.
5. At least **one other team member** reviews and approves the PR.
6. After approval, the author merges (or the Team Lead merges) into `develop`.
7. The feature branch may be deleted after merging.

---

## Commit Message Convention

Format: `[type]: [short description]`

| Type | When to Use | Example |
|------|-------------|---------|
| `feat` | New feature added | `feat: add exercise add handler` |
| `fix` | Bug fix | `fix: correct session timeout redirect` |
| `docs` | Documentation only change | `docs: update ER diagram planning` |
| `style` | CSS/UI changes, no logic change | `style: update dashboard card layout` |
| `refactor` | Code restructure, no feature change | `refactor: extract db connect to include` |
| `test` | Adding or fixing tests | `test: add diary CRUD test cases` |
| `chore` | Config, gitignore, non-logic tasks | `chore: update gitignore for db config` |

---

## `.gitignore` Key Entries

The following must be excluded from version control:

```
config/db_config.php
*.log
.DS_Store
Thumbs.db
/vendor/
*.swp
*.bak
```

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
