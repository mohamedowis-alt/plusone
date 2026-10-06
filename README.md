# +1 by RDNA website

The public site, the quote request wizard and the team admin for +1 by RDNA.

- `site/` is everything that runs on the server.
- `INSTALL.md` covers the first install, email set-up and day-to-day care.
- `.github/workflows/deploy.yml` checks every change and sends it to the live site.
- `scripts/check.sh` is the check itself.

## How updates work

1. A change is pushed to the `main` branch on GitHub.
2. GitHub checks it: every PHP file must be valid, and a fresh copy of the site must install, show the home page and accept a quote request.
3. If the check passes, GitHub uploads the files that changed to the hosting. If it fails, nothing is uploaded and the live site stays as it was.
4. The admin sidebar shows the version that is live.

It takes a minute or two. Progress is under the **Actions** tab of the repository.

## What an update never touches

Updates carry code and design only. These live on the server, are not in GitHub, and are left alone by every deploy:

- `config.php` (database details)
- the database: menus, parties, quote requests, settings, team sign-ins
- `uploads/menus/` (menu pictures and PDFs the team uploaded)
- `storage/`

So menus and requests are managed in the admin, never through GitHub.

## Connect the hosting (once)

The deploy uses FTP, which every shared host offers. In the hosting panel, create an FTP account for the site. Then in the GitHub repository open **Settings > Secrets and variables > Actions** and add four repository secrets:

| Secret | Example | What it is |
| --- | --- | --- |
| `FTP_SERVER` | `ftp.your-domain.com` | The FTP server name from the host |
| `FTP_USERNAME` | `deploy@your-domain.com` | The FTP account |
| `FTP_PASSWORD` | | Its password |
| `FTP_SERVER_DIR` | `/public_html/` | The folder the site lives in. It must end with `/` |

The deploy uses secure FTP (FTPS) on port 21. If the host only offers plain FTP, add a repository **variable** (same page, Variables tab) named `FTP_PROTOCOL` with the value `ftp`. A different port goes in a variable named `FTP_PORT`.

Until `FTP_SERVER` is set, pushes are still checked but nothing is uploaded.

Then run the first deploy: **Actions > Check and deploy > Run workflow**. When it finishes, open `https://your-domain/install.php` and follow `INSTALL.md` from step 3.

## Make a change

- **Small text or style change:** open the file on GitHub, press the pencil, edit, commit to `main`.
- **Anything bigger:** make it on a branch and open a pull request. The check runs on the pull request. Merging it deploys it.

Where things are:

| To change | Edit |
| --- | --- |
| Words and sections on the home page | `site/index.php` |
| Colours, type, layout, motion | `site/assets/css/site.css` |
| The quote wizard's behaviour | `site/assets/js/site.js` |
| The wizard's choices (occasions, vibes, areas, dietary needs) | `site/app/options.php` |
| The emails | `site/app/mail.php` |
| The admin pages | `site/admin/` |

## Undo a change

On GitHub, open the commit and press **Revert**, then merge the revert. That deploys the previous version.

## Updates that change the database

Deploys never touch the live database. When an update needs a new column or table, describe it in `site/app/migrations.php` (the file explains how). The site applies it by itself the first time a page loads after the update.

## Run the check yourself

With PHP 8 installed:

```
bash scripts/check.sh
```

## Optional: approve each deploy

The deploy job runs in a GitHub environment named `live`. Under **Settings > Environments > live** you can require a named person to approve each deploy before it goes out (availability depends on the GitHub plan).
