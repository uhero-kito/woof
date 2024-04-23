# Contributing to Woof

Thank you for your interest in contributing to Woof (Well Object-Oriented Framework)!

Woof is not just a lightweight framework; it is a project driven by a distinct and unyielding design philosophy. Before you suggest a feature, report a bug, or write any code, we strongly request that you deeply understand and respect the core concepts below.

## Our Core Philosophy: Web Applications as "Pure Functions"

The foundational policy of Woof is to express a web application as a "pure function" that takes an HTTP request as an input and returns an HTTP response as an output.

To maintain and evolve this philosophy, we strictly enforce the following architectural rules:

1. **Complete Isolation of Side Effects**: Any side effects, such as retrieving the system time, generating random numbers, database operations, and file I/O, must be completely decoupled from the business logic and encapsulated within the `Environment` context.
2. **Immutability of State**: Primary objects, including requests and responses, must be strictly immutable to prevent unintended state modifications.
3. **Minimalist Core**: Woof deliberately avoids built-in features like an ORM, an advanced router, or a template engine. It aims to remain a solid, robust foundation that integrates beautifully with other external libraries.

If a feature proposal or code change conflicts with this core concept of "functional purity" or "isolation of side effects," it may be declined, regardless of how convenient it might seem. We look forward to ideas that further refine the elegance of Woof's ecosystem.

## How to Contribute

### 1. Discuss Major Changes via Issues First
If you plan to introduce new components or make significant architectural modifications, please open an issue before you start writing code. Let us discuss beforehand how your proposal aligns with Woof's philosophy and how it can advance the project together.

### 2. Submitting Pull Requests
- Create a feature-specific topic branch branching off from the main branch.
- Keep your commit messages clear, concise, and descriptive.

## Coding Standards

To ensure code readability and consistency across the framework, we require all contributions to follow standard PHP conventions.

- **PSR-12 / PER Coding Style**: All PHP code must strictly adhere to the PSR-12 or PER Coding Style specification.

## Mandatory Testing

Woof's greatest strength is its exceptionally high testability, enabling 100% deterministic and robust unit testing without relying on complex external mocking libraries.

When fixing a bug or introducing a new feature, you must always accompany your changes with appropriate unit tests using PHPUnit. Before submitting your Pull Request, run the test suite locally and ensure that all tests pass:

```bash
vendor/bin/phpunit
```
