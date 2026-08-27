# Statamic Zapier

Send Statamic form submissions to Zapier webhooks (or any endpoint that accepts a JSON POST).

## Features

### Free Edition

- **Webhook Delivery**: Configure one or more webhook URLs per form in the control panel
- **Full Payload**: Every submission field as flat JSON, ready to map in Zapier's editor
- **Consent Management**: Optional consent field, so submissions are only sent when the visitor consented
- **Queued Delivery**: Deliveries run on your queue (or after the response on queue-less sites) and never slow down or break the visitor's form submission

### Pro Edition

- **Multi-Site Support**: Configure different webhook URLs per site
- **Site Inheritance**: Let a site inherit another site's configuration until it needs its own
- **Site-Specific Delivery**: Submissions are delivered using the configuration of the site they came from

## Requirements

- **PHP**: 8.3 or higher
- **Statamic**: 6.0 or higher
- **Zapier Account**: With a Zap using the "Webhooks by Zapier" trigger (or any endpoint that accepts a JSON POST)

## Installation

### Via Statamic Control Panel

1. Navigate to **Tools > Addons** in your Statamic control panel
2. Search for "Zapier"
3. Click **Install**

### Via Composer

```bash
composer require lwekuiper/statamic-zapier
```

The package will automatically register itself.

## Quick start

1. In Zapier, create a Zap with the "Webhooks by Zapier" trigger (Catch Hook) and copy the webhook URL.
2. In the Statamic control panel, go to Tools > Zapier, pick your form, and paste the URL.
3. Submit the form once and use the caught request in Zapier to map your fields (for example to a Google Sheets "Create Spreadsheet Row" action).

## Payload

All form fields, flat, plus reserved metadata keys:

```json
{
    "name": "...",
    "email": "...",
    "_form": "contact",
    "_site": "default",
    "_submission_id": "1755600000.1234",
    "_submitted_at": "2026-08-19T14:30:00+02:00"
}
```

Metadata keys are prefixed with an underscore so they can never collide with your form field handles.

## Pro Edition

> Unlock multi-site capabilities with the Pro edition. Requires **Statamic Pro**.

After purchasing the Pro edition, enable it in your `config/statamic/editions.php`:

```php
'addons' => [
    'lwekuiper/statamic-zapier' => 'pro',
],
```

Then go to **Tools > Zapier > Configure** to choose which sites can have their own webhook configuration, and optionally give a site an origin to inherit from.

## Documentation

- [DOCUMENTATION.md](DOCUMENTATION.md)

## License

This addon requires a license for use in production. You may use it without a license while developing locally.

## Support

- **Documentation**: [DOCUMENTATION.md](DOCUMENTATION.md)
- **Issues**: [GitHub Issues](https://github.com/lwekuiper/statamic-zapier/issues)
- **Discussions**: [GitHub Discussions](https://github.com/lwekuiper/statamic-zapier/discussions)

## Disclaimer

This addon is a third-party integration and is **not** affiliated with, endorsed by, or officially connected to Zapier, Inc. "Zapier" is a registered trademark of Zapier, Inc. All product names, logos, and brands are property of their respective owners.
