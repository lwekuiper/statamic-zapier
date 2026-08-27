# Changelog

## 1.0.0 (2026-08-27)

### What's new

- Per-form webhook configuration in the control panel
- Full-payload JSON delivery to multiple webhooks per form
- Optional consent field gating
- Queued delivery with logged failures

### Pro edition

- Per-site webhook configuration, with an optional origin site to inherit from
- A settings screen under Tools > Zapier > Configure for choosing which sites are enabled
- A site selector on the form config screen
- Requires Statamic Pro

### Notes

- Webhook URLs must use `http` or `https`. The host is not restricted, so treat `configure forms` as the trusted permission it is.
- Delivery failures are logged with the webhook's position in the form's list. The URL is never logged, since a Zapier hook URL is a credential.
- A consent field that is missing or falsy skips delivery; a malformed one (only reachable by hand-editing the YAML) skips delivery and logs a warning.
