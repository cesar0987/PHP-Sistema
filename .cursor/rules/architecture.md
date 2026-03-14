---
description: Architectural constraints for the system
alwaysApply: true
scope: architecture
---

# Architecture Rules

General principles:

- Follow clean architecture principles.
- Separate domain, application, and infrastructure layers.
- Avoid business logic inside controllers.
- Keep functions small and focused.

Backend rules:

- Use service layer for business logic.
- Controllers should only handle request/response.
