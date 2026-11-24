# Test Report

Recorded on 2026-11-23 from a clean export of the tree in this commit, with dependencies installed from the lockfiles. The XML files are unedited tool output.

| Suite | Tests | Passed | Failed | Skipped | Raw report |
| --- | ---: | ---: | ---: | ---: | --- |
| Backend (PHPUnit 12.5.45, PHP 8.4.28) | 59 | 59 | 0 | 0 | [backend-junit.xml](backend-junit.xml) |
| Frontend (Vitest 5.1.1, Node.js 24.22.3) | 10 | 10 | 0 | 0 | [frontend-junit.xml](frontend-junit.xml) |

The recorded backend run used SQLite. The same backend suite also passed once on MySQL 8.4. Code coverage was not measured.

Reproduce with `APP_KEY="base64:$(openssl rand -base64 32)" php vendor/bin/phpunit` in `backend/` and `npm test` in `frontend/`.
