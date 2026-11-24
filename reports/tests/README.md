# Test Report

Recorded on 2026-11-23 from a clean export of the staged tree, with dependencies installed from the lockfiles.

| Suite | Tests | Passed | Failed | Skipped | Raw report |
| --- | ---: | ---: | ---: | ---: | --- |
| Backend (PHPUnit 12.5.45, PHP 8.4.28) | 39 | 39 | 0 | 0 | [backend-junit.xml](backend-junit.xml) |
| Frontend (Vitest 5.1.1, Node.js 24.22.0) | 8 | 8 | 0 | 0 | [frontend-junit.xml](frontend-junit.xml) |

Code coverage was not measured.

Reproduce with `php vendor/bin/phpunit` in `backend/` and `npm test` in `frontend/`.
