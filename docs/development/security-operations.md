# Security Operations Baseline

## Security headers
- CSP default-src self, frame-ancestors none, object-src none.
- X-Frame-Options: DENY.
- X-Content-Type-Options: nosniff.
- Referrer-Policy: strict-origin-when-cross-origin.

## Anti-robot controls
- Honeypot field (`website`).
- Time-to-submit check (`tts` seconds).
- CAPTCHA token requirement when provider configured.

## Sensitive file protection
- Store sensitive files in non-public storage (`storage/app/private`).
- Validate upload mime type, extension, and max size.
- Log file access and anomalous download patterns.
