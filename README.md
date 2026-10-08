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

In the site's admin, open **Updates > Where updates come from** and enter the repository (`mohamedowis-alt/plusone`) and the branch (`main`). Save. The page says whether it can reach the repository.

While the repository is public, that is all. If it is made private later, the site also needs an access token:

1. On GitHub: **Settings > Developer settings > Personal access tokens > Fine-grained tokens > Generate new token**. Repository access: only this repository. Permissions: **Contents: Read-only** and **Actions: Read-only**.
2. Paste it into **Access token** on the Updates page and save.

A token expires on the date you choose. When it does, the Updates page says GitHub refused it: create a new one and save it.

## The Eyeful font is not in this repository

Eyeful is a licensed font, so its files are kept out of GitHub (see `.gitignore`). They are in the site package and go to the server with the first upload, in `assets/fonts/`. Updates never remove them. If they are missing, headlines fall back to a plain condensed font.

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
| The Arabic for the site's own words | `site/app/lang.ar.php` |
| Arabic type and right-to-left layout | the "Arabic" part at the end of `site/assets/css/site.css` |

Only files inside `site/` reach the server.

## English and Arabic

The site is one page in two languages. English is at the site's address. Arabic is the same page at `?lang=ar` and reads right to left. Each page links to the other in the header and the footer.

- The site's own words (headlines, buttons, the quote questions) are translated in `site/app/lang.ar.php`: English on the left, Arabic on the right. A text with no Arabic shows in English.
- The Arabic names of parties, menus and dishes are typed in the admin, beside the English. Leave one empty and the Arabic page shows the English.
- A new text added to `site/index.php` needs to be wrapped like the ones around it, `<?= te('Text') ?>`, and given its Arabic in `lang.ar.php`.
- Layout rules in the stylesheet are written with start and end, never left and right, so the Arabic page turns round by itself. Keep it that way.
- A quote request remembers its language. The team's email and the admin stay in English and say when a guest wrote in Arabic. The guest's confirmation email is in their language.

## Updates that change the database

Updates never touch the live database directly. When one needs a new column or table, describe it in `site/app/migrations.php` (the file explains how). The site applies it by itself the first time a page loads after the update.

## Run the check yourself

With PHP 8 installed:

```
bash scripts/check.sh
```
