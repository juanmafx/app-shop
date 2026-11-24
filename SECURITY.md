# Security Policy

## Supported versions

| Version | Supported |
| --- | --- |
| 2.x | Yes |
| Earlier than 2.0 | No |

## Reporting a vulnerability

Do not put exploit details in a public issue. Private vulnerability reporting is not currently enabled on this repository, so contact the maintainer through their GitHub profile and ask for a private channel. Include the affected version, the impact, and reproduction steps.

This is a personal project maintained on a best-effort basis, so no response-time commitment is made.

## Deployment checklist

- Generate a unique `APP_KEY` for each environment and never commit `.env`.
- Set `APP_ENV=production` and `APP_DEBUG=false`. Production always marks session cookies as HTTPS-only.
- Serve only over HTTPS with `backend/public` as the document root.
- Set `$proxies` in `backend/app/Http/Middleware/TrustProxies.php` if the application runs behind a load balancer. Until then, client IPs and HTTPS detection reflect the proxy, which weakens per-client rate limits and disables HSTS.
- Configure a real `MAIL_MAILER`. The default `log` transport writes password reset links to the application log.
- Keep `CORS_ALLOWED_ORIGINS` empty unless a specific origin needs API access.
- Run `composer audit` and `npm audit` before each release.
