# Security Policy

## Supported versions

| Version | Supported |
| --- | --- |
| 2.x | Yes |
| Earlier than 2.0 | No |

## Reporting a vulnerability

Please use GitHub's private vulnerability reporting on this repository, under **Security > Report a vulnerability**. Do not open a public issue for a suspected vulnerability. Include the affected version, the impact, and reproduction steps.

This is a personal project maintained on a best-effort basis, so no response-time commitment is made.

## Deployment checklist

- Generate a unique `APP_KEY` for each environment and never commit `.env`.
- Set `APP_ENV=production`, `APP_DEBUG=false`, and `SESSION_SECURE_COOKIE=true`.
- Serve only over HTTPS with `backend/public` as the document root.
- Configure trusted proxies if the application runs behind a load balancer.
- Keep `CORS_ALLOWED_ORIGINS` empty unless a specific origin needs API access.
- Run `composer audit` and `npm audit` before each release.
