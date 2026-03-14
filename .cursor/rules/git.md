---
description: Strict standards for commit messages and Git workflow
alwaysApply: true
scope: repository
---

# Git & Commit Conventions

All commits must follow the **Conventional Commits** specification.

## Commit Structure

Use:

git commit -m "<type>(<scope>): <subject>

<body>"

Rules:

- Subject ≤ 50 characters
- Imperative mood
- No trailing period
- Language: English

## Allowed Types

- feat
- fix
- docs
- refactor
- perf
- test
- chore

## Scope

Always specify the module affected.

Examples:

feat(api): add JWT authentication middleware  
fix(auth): resolve token refresh race condition  
docs(readme): add project setup instructions
