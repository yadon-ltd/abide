# Contributing to Abide

Thank you for your interest in contributing. Abide has a strong point of view  
and a deliberate philosophy — please read this document before opening a pull request.

---

## Philosophy alignment

Abide exists to be a disciplined starting point. Every decision in the codebase  
is made with that purpose in mind. Before contributing, ask yourself:

- Does this make Abide simpler or more complex?
- Does it belong in `config.php` (behavior) or `style.css` (appearance) — or does it not belong in core at all?
- Would a developer returning to PHP after years away understand this immediately?
- Does it add a dependency? If so, is that dependency genuinely unavoidable?

If a feature doesn't pass those questions, it probably belongs in a downstream  
project built on Abide, not in Abide itself.

---

## What we welcome

- Bug fixes with clear reproduction steps
- Documentation improvements — clarity, accuracy, completeness
- Security improvements — especially in the auth module
- Performance improvements that don't add complexity
- Accessibility improvements
- New module additions that follow the self-contained, opt-in pattern of `modules/auth/`

---

## What we do not accept

- Framework-style abstractions or routing layers
- Build steps, package managers, or compiled assets
- External font loading
- Features that belong in `config.php` but are hardcoded
- Features that belong in a page file but are pushed into `core/`
- Anything that makes a fresh install heavier without a clear, unavoidable reason

---

## CLA requirement

A Contributor License Agreement is required before any contribution can be  
merged. See `CLA.md` for the full text and signing instructions.  

Contributions submitted without a signed CLA will not be reviewed.

---

## How to contribute

1. Fork the repository
2. Create a branch: `git checkout -b fix/brief-description`
3. Make your changes — keep commits focused and descriptive
4. Test against a clean install, not just your local environment
5. Open a pull request with a clear description of what changed and why
6. Sign the CLA if you have not already done so

---

## Code style

- PHP: match the conventions already in the file you are editing
- Comments: explain *why*, not *what* — the code shows what
- No short tags (`<?`) — use `<?php` throughout
- Indent with 4 spaces
- Every function gets a docblock
- New pages follow the pattern in `public/pages/example.php`

---

## Contact

Questions before contributing? Open an issue or reach out via the repository.
