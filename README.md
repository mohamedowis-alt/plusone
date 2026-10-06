# +1 by RDNA website

The public site, the quote request wizard and the team admin for +1 by RDNA.

- `site/` is everything that runs on the server.
- `INSTALL.md` covers the first install, email set-up and day-to-day care.
- `scripts/check.sh` checks the site. GitHub runs it on every change.

## How updates work

The same way as amsforum.com: the live site installs its own updates from this repository.

1. A change is pushed to the `main` branch here.
2. GitHub checks it (valid code, and a fresh copy must install, show the home page and accept a quote request).
3. In the site's admin, open **Updates**. It shows the version that is live and whether a newer one is ready.
4. Press **Update website**.

Before installing, the site checks every file itself and keeps a copy of the files it is about to replace. A version that failed its GitHub check, or that contains a broken file, is refused and nothing changes.

**Roll back last update** on the same page puts the previous files back. The last five updates are kept.

## What an update never touches

Updates carry code and design only. These stay on the server, are not in GitHub, and are left alone:

- `config.php` (database details)
- the database: menus, parties, quote requests, settings, team sign-ins
- `uploads/menus/` (menu pictures and PDFs the team uploaded)
- `storage/`
- the root `.htaccess` (hosting panels such as cPanel keep the PHP version setting in it)

So menus and requests are managed in the admin, never through GitHub.

## Connect the live site to this repository (once)

Keep this repository **private**: it holds the Eyeful font file.

1. On GitHub, create an access token that can only read this repository: **Settings > Developer settings > Personal access tokens > Fine-grained tokens > Generate new token**. Repository access: only this repository. Permissions: **Contents: Read-only** and **Actions: Read-only**.
2. In the site's admin, open **Updates > Where updates come from** and enter the repository (`owner/name`), the branch (`main`) and the token. Save. The page says whether it can reach the repository.

A token expires on the date you choose. When it does, the Updates page says GitHub refused it: create a new one and save it.

## Make a change

- **Small text or style change:** open the file on GitHub, press the pencil, edit, commit to `main`. Then **Updates > Update website** in the admin.
- **Anything bigger:** make it on a branch and open a pull request. The check runs on the pull request. Merge it, then update from the admin.

Where things are:

| To change | Edit |
| --- | --- |
| Words and sections on the home page | `site/index.php` |
| Colours, type, layout, motion | `site/assets/css/site.css` |
| The quote wizard's behaviour | `site/assets/js/site.js` |
| The wizard's choices (occasions, vibes, areas, dietary needs) | `site/app/options.php` |
| The emails | `site/app/mail.php` |
| The admin pages | `site/admin/` |

Only files inside `site/` reach the server.

## Updates that change the database

Updates never touch the live database directly. When one needs a new column or table, describe it in `site/app/migrations.php` (the file explains how). The site applies it by itself the first time a page loads after the update.

## Run the check yourself

With PHP 8 installed:

```
bash scripts/check.sh
```
