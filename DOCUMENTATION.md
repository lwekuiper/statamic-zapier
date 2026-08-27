# Statamic Zapier Documentation

## Table of Contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuring webhooks](#configuring-webhooks)
- [Multiple webhooks per form](#multiple-webhooks-per-form)
- [Consent field](#consent-field)
- [Queue behavior](#queue-behavior)
- [Payload reference](#payload-reference)
- [Multi-Site Support (Pro)](#multi-site-support-pro)
- [Permissions](#permissions)
- [Known limitations](#known-limitations)
- [Troubleshooting](#troubleshooting)

---

## Overview

Statamic Zapier sends a form submission's data to one or more webhook URLs as soon as the submission is created. It is aimed squarely at Zapier's "Webhooks by Zapier" trigger, but the payload is plain JSON over HTTP POST, so any endpoint that accepts a JSON body works.

## Requirements

- PHP 8.3+
- Statamic 6.0+
- Statamic Pro, for the addon's Pro edition (multi-site)

## Installation

```bash
composer require lwekuiper/statamic-zapier
```

The addon auto-registers via Laravel's package discovery. No configuration file needs to be published; webhook settings are configured per form in the control panel.

## Configuring webhooks

1. In the control panel, go to **Tools > Zapier**.
2. The listing shows every form, with an indicator for whether it already has a configuration.
3. Click a form to open its edit screen and add one or more webhook URLs.
4. Save. A configured form can also be deleted from the edit screen, which removes its webhook configuration entirely (the form and its submissions are untouched).

Webhook URLs are validated before they are saved and must use `http` or `https`.

On the Pro edition with multi-site enabled, the edit screen also shows a site selector. See [Multi-Site Support (Pro)](#multi-site-support-pro).

## Multiple webhooks per form

A form can have any number of webhook URLs. Every submission that passes the consent check is posted, independently, to each configured URL for that form. One URL failing or timing out has no effect on the others: each delivery is its own queued job.

## Consent field

The **Consent Field** setting is optional and lets you pick any field from the form's blueprint. If set, a submission is only delivered when that field's submitted value is truthy; if the field is missing or falsy, the submission is saved as normal but no webhook is called.

Truthiness is evaluated with PHP's `FILTER_VALIDATE_BOOLEAN`, which treats `1`, `"1"`, `"true"`, `"yes"`, and `"on"` as true, and empty, `0`, `false`, and `"false"` as false. This works naturally with a toggle field. If the field's value is an array (for example a checkboxes field), the first element of the array is what gets evaluated.

If no consent field is configured, every submission of that form is delivered. If the stored consent setting is malformed (for example an array, which the control panel will not produce but a hand-edited YAML file can), delivery is skipped and a warning is written to the log.

## Queue behavior

Each webhook delivery is dispatched as a separate job (`SendWebhook`). On a real queue connection the job runs through your worker as usual. If your queue connection is the `sync` driver, the job is dispatched to run after the HTTP response has been sent to the visitor, so the form submission still completes immediately.

Either way, the visitor's form submission is never blocked or failed by a webhook. Delivery uses a 2 second connection timeout and a 5 second total timeout. There is no retry logic: a failed delivery (a non-2xx response, a timeout, or a connection error) is written once to the Laravel log and not attempted again.

## Payload reference

Every field from the form submission is included at the top level, using the field's handle as the key, plus these reserved metadata keys:

| Key | Type | Example |
|---|---|---|
| `_form` | string | `"contact"` |
| `_site` | string | `"default"` |
| `_submission_id` | string | `"1755600000.1234"` |
| `_submitted_at` | string (ISO 8601) | `"2026-08-19T14:30:00+02:00"` |

Metadata keys are prefixed with an underscore so they can't collide with a form field handle. Array-valued fields (for example a checkboxes field) are sent as JSON arrays, not flattened to a string. A file upload field is sent as the asset's path, not a URL.

## Multi-Site Support (Pro)

With the Pro edition and Statamic's multi-site enabled, each site can have its own webhook configuration for the same form. A submission is delivered using the configuration of the site it was submitted from.

### Enabling sites

Go to **Tools > Zapier > Configure**. Every site in your installation is listed, and you choose which ones the addon is active on. A site that is switched off has no configuration and receives no deliveries.

Each enabled site can also be given an **origin**: another enabled site whose configuration it inherits. A site with an origin uses that site's webhook URLs until you give it its own, which saves repeating the same URL across every locale. At least one enabled site must have no origin, an origin must itself be enabled, and sites cannot inherit from each other in a loop.

Switching a site off deletes its form configurations. The forms and their submissions are untouched.

### Configuring a site

The form's edit screen shows a site selector once more than one site is enabled. Pick a site to load its configuration; unsaved changes are confirmed before switching.

On a site that inherits from an origin, each field shows the inherited value and is locked. Use the link icon next to a field's label to unlink it and give that site its own value; the icon unlinks and relinks the field. Only the fields you unlink are stored for that site, so the rest keep following the origin, and relinking a field drops the site's own value and returns it to the inherited one.

Configuration files are stored per site (`resources/zapier/<site>/<form>.yaml`). On a single-site installation, or on the Free edition, files stay at `resources/zapier/<form>.yaml` and no site selector appears.

### Which site a submission belongs to

On the Pro edition the site is resolved from the page the form was submitted from. On the Free edition the default site is always used. Either way the resolved handle is sent as `_site` in the payload.

Note that this resolution reads the request's referring URL, so a submission made from outside a site's own pages (a headless front end, or an API client that sends no referrer) falls back to the default site.

## Permissions

Configuring webhooks requires the **`configure forms`** permission, or super user status. This applies to every control panel action: viewing the Zapier listing, opening a form's edit screen, saving a configuration, deleting one, and (on the Pro edition) the per-site settings screen. A user without this permission (and without super user status) cannot see or change any webhook configuration.

## Known limitations

Webhook URLs must be `http` or `https`; every other scheme is rejected. The host, however, is not restricted.

In practice this means a user who can configure webhooks could point one at an internal or loopback address (for example `http://127.0.0.1/...`, an address on a private network, or a cloud metadata endpoint), and the server will send a POST request with submission data to it. This is a server-side request forgery consideration.

The mitigating factor is that configuring webhooks already requires the `configure forms` permission, the same trusted-role permission that governs a form's other settings, including its email notifications. Grant `configure forms` only to users you trust with that capability, exactly as you would for any other form configuration setting.

There is no delivery retry logic and no delivery-log screen in the control panel. Zapier's own task history (or your endpoint's own logs) is the place to check whether a specific delivery arrived; the Laravel log only records failures, not successful deliveries.

A file upload field is sent as the asset's path, not a URL. Zapier cannot fetch a file from a path, so an attachment cannot be forwarded on to another service.

## Troubleshooting

**A webhook doesn't seem to be firing**

- Check `storage/logs/laravel.log` for a `Zapier webhook delivery failed.` entry. It includes the form handle and the webhook's position in that form's list (`webhook: 1` is the first URL on the edit screen), plus the response status when the endpoint replied. The webhook URL is never logged, since it is a credential.
- If a consent field is configured, confirm the field actually has a truthy value in the submission that didn't deliver.
- Confirm the webhook URL is still saved against the form in **Tools > Zapier**.

**A webhook fires but the data looks wrong in Zapier**

- Check Zapier's task history for the Zap; it shows the raw payload as received, which is the fastest way to confirm what was actually sent.
- Remember array-valued fields (checkboxes, multi-select) arrive as JSON arrays, not comma-separated strings.

**Nothing is being queued at all**

- Confirm the form actually has a saved webhook configuration; forms with no configuration are silently skipped.
- If your queue connection is not `sync`, confirm a queue worker is running.
