# SEO and public pages

The public website uses stable, crawlable German and English URLs. The protected application, APIs, ingest, TTN webhook, OTA and tools remain excluded from indexing.

## Public routes

| Content | English | German |
| --- | --- | --- |
| Homepage | `/en` | `/de` |
| Features | `/en/features` | `/de/funktionen` |
| The Things Network | `/en/ttn-integration` | `/de/ttn-integration` |
| API overview | `/en/api` | `/de/api` |
| OTA updates | `/en/ota-updates` | `/de/ota-updates` |
| Privacy | `/en/privacy` | `/de/datenschutz` |
| Imprint | `/en/imprint` | `/de/impressum` |

`/`, `/index.php`, `/privacy.php` and `/imprint.php` permanently redirect to their canonical English public URL. Every public document links its language counterpart with `hreflang`; English is the `x-default` version.

## Sitemap and robots

- Sitemap: `https://mds-git.derguntmar.de/sitemap.xml`
- Robots file: `https://mds-git.derguntmar.de/robots.txt`
- `public/sitemap.php` generates the XML from the central route map in `app/Support/seo.func.php`.
- `lastmod` is intentionally omitted: deployment file modification times do not identify real per-page content changes.
- Authenticated pages and machine endpoints emit or receive `noindex`/robots exclusions and do not belong in the sitemap.

## Google Search Console

1. Add the domain or URL-prefix property in Google Search Console.
2. For meta-tag verification, copy only the value of the `content` attribute.
3. Enter it as an administrator under `Settings -> Server Setting -> Google Search Console`.
4. Submit `/sitemap.xml` in Search Console.
5. Request indexing for `/en` and `/de` after a deployment.

MDS does not add analytics or tracking by default. This avoids additional consent requirements. Add measurement only after choosing a privacy-compatible setup and documenting it in the privacy notice.

## Social preview

`public/assets/img/mds-social-preview.png` is used as the Open Graph and Twitter preview image. It is 1200 x 630 pixels. Public pages provide individual titles and descriptions while sharing this branded visual.

The homepage uses 640px and 1200px WebP versions through `srcset`. The original
PNG remains unchanged for social previews. A static dashboard illustration uses
explicitly fictional demo values and never loads private sensor data. Public
topic pages include bilingual setup steps, request examples and troubleshooting.
The shared header provides a keyboard-visible skip-to-content link.

## Operational checks

After changing public routes or metadata:

```bash
composer test
curl -I https://mds-git.derguntmar.de/en
curl -s https://mds-git.derguntmar.de/sitemap.xml
```

Anonymous public responses should be cacheable and must not set `PHPSESSID`. Authenticated requests remain private.
