# Deploy on Laravel Cloud; object storage and Cloudflare PDF follow

The MVP needs a Laravel scheduler for Charge generation (ADR-0005), durable uploads for
Announcement attachments and payment screenshots, and a printable bill. We deploy on **Laravel
Cloud** (Starter), not a VPS the association would have to patch. That choice rules out a
persistent local disk and dompdf (Laravel Cloud's docs: Dompdf does not work there), so uploads
use Laravel Object Storage and PDFs use `spatie/laravel-pdf` with the Cloudflare Browser Rendering
driver — superseding the pure-PHP dompdf preference from the PDF research when that research assumed
a host with a real filesystem.

## Considered Options

A VPS (Forge, DigitalOcean, and the like) would keep dompdf and local disk viable. Rejected for
MVP: officer turnover makes a box and card tied to one person a failure mode, and the association
does not want to administer OS patching for a first cut.

Shared or "whatever the association already pays for" hosting was rejected unless it can run
Laravel 13, PHP 8.5, a scheduler, and durable file storage — nothing on the table today does.

Builder-held Cloud billing for go-live was accepted as temporary custody, not steady state.
Permanent Treasurer-personal accounts were rejected.

Production-only for MVP; staging waits until Officers rehearse a real levy. A Philippine Data
Privacy Act compliance program is acknowledged as needed later and is not product work in this
map.

## Consequences

Printable bill work assumes Cloudflare PDF, not dompdf. Feature code must not rely on
`storage:link` or a durable local `storage/` tree for member-facing files. Cloud Scheduler must
stay enabled for the levy cron. Account handoff to association-owned email and payment method is
an explicit later ops task — not an Officer's personal card as the long-term home.
