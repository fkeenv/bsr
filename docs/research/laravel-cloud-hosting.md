# Laravel Cloud Hosting Constraints — Research

**Scope:** Laravel 13 / PHP 8.5 HOA member platform MVP needing PDF generation, persistent uploads (announcement attachments, payment screenshots), monthly Charge generation via scheduler, and queues.

**Method:** Primary sources only — Laravel Cloud docs at `cloud.laravel.com` / `laravel.com/cloud`. Research date: 2026-09-09. Figures can drift; re-check pricing before budgeting.

**Related:** [docs/research/pdf-generation.md](./pdf-generation.md) (package comparison; already notes Cloud’s Dompdf caveat).

---

## 1. Answers (short)

| Question | Answer from official docs |
| --- | --- |
| Scheduler? | **Yes.** Enable “Scheduler” on App or Worker cluster; Cloud runs `schedule:run` every minute. |
| Persistent local filesystem? | **No.** Local FS is **ephemeral**. Use **Laravel Object Storage** (S3-compatible, Cloudflare R2). |
| Dompdf on Cloud? | Docs state Dompdf **does not work** (in Cashier context) and recommend **spatie/laravel-pdf** with **Cloudflare** driver (or direct Cloudflare Browser Rendering API). |
| Pricing (public)? | **Starter $5/mo + usage**, **Growth $20/mo + usage**, **Business $200/mo + usage**, **Enterprise** custom. Each paid plan includes **$5** monthly usage credits. Starter first month free. |
| Queues? | **Yes** — Managed queues (recommended), Worker clusters (Growth+), or App-cluster background processes. |

---

## 2. Scheduler

**Supported.** Laravel Cloud supports Laravel’s task scheduler without managing cron yourself.

- Toggle **Scheduler** on the environment’s **App** compute cluster (or a **Worker** cluster for separate infra).
- After deploy, `schedule:run` runs **every minute**.
- Multiple replicas: use Laravel’s `onOneServer()` or tasks run on every replica.
- **Scale to Zero:** sleeping Laravel environments **wake for scheduled tasks**. Cloud captures `php artisan schedule:list` **at deploy time** to know when to wake — schedule changes need a **redeploy**. Do not schedule intervals shorter than the sleep timeout (keeps env awake). `withoutOverlapping` / `onOneServer` / sub-minute tasks use the cache store and may keep DB/cache awake.

Sources:

- https://cloud.laravel.com/docs/scheduled-tasks
- https://cloud.laravel.com/docs/compute (Scale to Zero / scheduled tasks)

**MVP note (monthly Charges):** A once-monthly schedule is compatible with Scale to Zero. Prefer dispatching Charge generation to a **managed queue** if the job can outlive the sleep-timeout wake window.

---

## 3. Filesystem / uploads

### Local disk: ephemeral

Official Environments docs:

> Environment filesystems are **ephemeral**, meaning files may not persist across requests or jobs. New deployments or re-deployments will reset the filesystem. In addition, each replica of your compute cluster has its own filesystem. Thus, you should treat the filesystem as temporary, unshared disk space that is only consistent during a single request or job.

- Ephemeral disk size: **512 MB disk per 1 GB RAM** (e.g. 2 GB RAM → 1 GB ephemeral). Exceeding it can crash the app.
- `php artisan storage:link` during deploy **will not persist**; docs say use Object Storage instead.
- Managed queue workers: same ephemeral model (512 MiB disk per 1 GiB worker memory).

Sources:

- https://cloud.laravel.com/docs/environments#filesystem
- https://cloud.laravel.com/docs/queues (Filesystem section under managed queues)

### Persistent option: Laravel Object Storage (S3 / R2)

- S3-compatible buckets via **Cloudflare R2**, attachable in the dashboard.
- Use Laravel `Storage` facade; require `league/flysystem-aws-s3-v3`.
- Cloud injects `FILESYSTEM_DISK` and AWS-compatible env vars when attached.
- **Bucket-level visibility only** (private *or* public entire bucket — no mixed ACL). Private buckets: `Storage::temporaryUrl()`. Do not set Flysystem `visibility: 'public'` per object — R2 rejects with `NotImplemented`.
- Docs explicitly recommend Object Storage for **private user documents** (fits payment screenshots / announcement attachments).

Sources:

- https://cloud.laravel.com/docs/resources/object-storage
- https://cloud.laravel.com/docs/pricing (Object Storage rates)

**Object Storage pricing (US rates as published):**

| Metric | Price |
| --- | --- |
| Storage | **$0.02 / GB-month** |
| Class A ops | **$0.005 / thousand** |
| Class B ops | **$0.0005 / thousand** |
| Data transfer from buckets | **Free** |

---

## 4. PDF generation / Dompdf

Official knowledge base: **Generating PDFs**

- Recommended path: **Cloudflare Browser Rendering API**.
- Preferred wrapper: **`spatie/laravel-pdf`** with `LARAVEL_PDF_DRIVER=cloudflare` (+ `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ACCOUNT_ID`). Docs: “No Node.js or Chrome binary is required on your instances.”
- Alternative: call Cloudflare’s `/browser-rendering/pdf` endpoint via `Http` facade.
- **Dompdf:** verbatim: “Cashier's default invoice PDF renderer uses Dompdf, which **does not work** in Laravel Cloud.” Cashier workaround: Spatie Laravel PDF + `CASHIER_INVOICE_RENDERER=Laravel\Cashier\Invoices\LaravelPdfInvoiceRenderer`.

**What docs do *not* say:** a detailed technical root cause for Dompdf failure (e.g. fonts, temp dirs, extensions). PHP extension list *does* include `dom`, `mbstring`, `gd`, `imagick` among many others — so the Dompdf statement is a platform constraint as published, not explained further.

**Implication for this app:** On Laravel Cloud, treat Dompdf as **unsupported per official KB**; plan on **spatie/laravel-pdf Cloudflare driver** (or direct Cloudflare API). Pure-PHP Dompdf may still be fine off-Cloud (see pdf-generation.md).

Source: https://cloud.laravel.com/docs/knowledge-base/generating-pdfs

---

## 5. Queues

**Supported** in three shapes:

| Approach | Notes |
| --- | --- |
| **Managed queues** (recommended) | Dedicated workers, scale on queue pressure **including to zero**, failed-job dashboard/retry. Sets `QUEUE_CONNECTION=cloud`. |
| **Worker clusters** | Self-managed `queue:work` / other drivers; **Growth+** (Starter has no worker clusters per pricing). |
| **App cluster background processes** | OK for low volume; competes with HTTP; jobs can be interrupted if Scale to Zero sleeps mid-job. |

Managed queue plan limits (docs):

| Plan | Queues / env | Max workers / queue | Memory tiers |
| --- | --- | --- | --- |
| Starter | 1 | 3 | 256 MiB–1 GiB |
| Growth | 10 | 25 | up to 8 GiB |
| Business | Unlimited | 50 (soft) | up to 8 GiB |

Default shutdown timeout customer max **90s** (raise via support for longer jobs). Visibility timeout configurable up to **12 hours**. Design for **at-least-once** delivery.

Scale to Zero: App-cluster queue workers can be interrupted when sleep timeout elapses — docs **recommend Managed Queues** so background work is not interrupted.

Sources:

- https://cloud.laravel.com/docs/queues
- https://cloud.laravel.com/docs/compute#scale-to-zero
- https://laravel.com/cloud/pricing

---

## 6. Pricing tiers (public)

From https://laravel.com/cloud/pricing and https://cloud.laravel.com/docs/pricing:

| Plan | Base | Notable for MVP |
| --- | --- | --- |
| **Starter** | **$5/mo + usage** (first month free) | Flex compute, Scale to Zero, **1 managed queue/env**, scheduler on App cluster, **no** Worker clusters / Pro compute / autoscaling beyond 1×. 10 custom domains, 1-day logs. |
| **Growth** | **$20/mo + usage** | Pro compute, autoscaling to 10×, Worker clusters, preview envs, **10 managed queues**, basic WAF, 50 domains, 7-day logs. |
| **Business** | **$200/mo + usage** | Unlimited/scheduled autoscaling, unlimited managed queues, advanced WAF, 250 domains, 30-day logs. |
| **Enterprise** | Custom | Private Cloud, dedicated compute, etc. |

- All paid plans: **$5** monthly usage credits.
- Not serverless: dedicated AWS EC2 (Graviton); Flex billed per second awake, monthly caps listed on pricing docs.
- Example Flex app compute (US East table excerpt): Flex 512 MiB ~$0.00000248/s (cap $6/mo); Flex 1 GiB cap $12; Flex 2 GiB cap $24.
- Extra bandwidth beyond allowance: **$0.10/GB**.
- Org spending limits available.

Exact total MVP bill = base + awake compute + DB + object storage + queue ops — use Cloud’s pricing calculator / docs scenarios; not inventable as a single number.

---

## 7. Other hard constraints relevant to HOA MVP

| Constraint | Detail | Source |
| --- | --- | --- |
| PHP versions | **8.2–8.5** (8.5 default for new envs). Laravel apps: Laravel **9+**. | Welcome / Environments |
| Build/deploy timeouts | Build and deploy commands **≤ 15 minutes**. | Environments |
| One-off Commands | Non-interactive, **≤ 30 minutes**. | Environments |
| No persistent `storage:link` | Use Object Storage. | Environments |
| Sessions/cache | Prefer **redis/database** (KV Store / DB), not local file. | Environments |
| Maintenance mode | Default `file` driver won’t persist across replicas/deploys; use `cache` driver. | Compute |
| Cloudflare PDF dependency | PDF path needs Cloudflare Account ID + Browser Rendering token (third-party account/cost outside Cloud base fee — **Cloud docs don’t publish Cloudflare Browser Rendering prices**). | Generating PDFs |
| Starter vs Growth | Worker clusters and Pro compute need **Growth+**; Starter is enough for Flex + 1 managed queue + scheduler on App. | Pricing |
| Preview environments | Growth feature (pricing page). | Pricing |
| HTTP basic auth | **Growth+**. | Environments |
| Domains | Starter 10 / Growth 50 / Business 250 custom domains per org. | Pricing |
| Regions | Multiple AWS regions listed on marketing/pricing (e.g. US East, EU, APAC, Canada, Middle East on compute pricing tables). | Pricing / Compute |

**Unknown / not stated in Cloud docs reviewed:**

- Exact root cause why Dompdf fails (only the flat statement + Cloudflare recommendation).
- Cloudflare Browser Rendering pricing / rate limits (owned by Cloudflare, not listed on Laravel Cloud pricing).
- Whether any Dompdf workaround exists on Cloud beyond “use Cloudflare / Spatie.”
- Hard max upload size for Object Storage HTTP path (not found in Object Storage page reviewed).

---

## 8. MVP architecture implications (factual mapping only)

| Need | Cloud-compatible approach per docs |
| --- | --- |
| Monthly Charge generation | Scheduler **on** + preferably dispatch to **managed queue** |
| Announcement / payment file uploads | **Private** Object Storage disk; temporary URLs for download |
| PDF bills | **spatie/laravel-pdf** + **cloudflare** driver (not Dompdf on Cloud) |
| Background work | Managed queue on Starter (1 queue) is enough to start |

---

## Sources index

1. https://cloud.laravel.com/docs — Welcome / prerequisites  
2. https://cloud.laravel.com/docs/scheduled-tasks  
3. https://cloud.laravel.com/docs/environments  
4. https://cloud.laravel.com/docs/resources/object-storage  
5. https://cloud.laravel.com/docs/knowledge-base/generating-pdfs  
6. https://cloud.laravel.com/docs/queues  
7. https://cloud.laravel.com/docs/compute  
8. https://cloud.laravel.com/docs/pricing  
9. https://laravel.com/cloud/pricing  
