# +1 by RDNA website

The public site, the quote request wizard and the team admin, in one package.
Everything that goes on the server is in the `site` folder.

This file covers the first install. For how updates reach the live site from GitHub, see `README.md`.

## What it does

**For guests**
- One page that sells the idea: hero, the promise, gatherings, the parties, this season's menus.
- Seasonal menus by party (Buffet, Pizza party, Barbecue, Coffee break, Birthday, Iftar). No prices anywhere.
- "Plan your gathering": eight quick questions (occasion, guests, party, service, date and place, vibe and live cooking, dietary needs and notes, contact). About a minute.
- After sending, a button to say hello on WhatsApp with the reference filled in.

**For the team** (`/admin`)
- **Requests**: every request, with status (New, In conversation, Quote sent, Booked, Not going ahead), team notes, one-tap WhatsApp reply, spreadsheet download.
- **Menus**: add, edit, hide or delete menus for each party. Dishes with a one-line description and a source tag. Optional picture and PDF.
- **Parties**: add or change the kinds of party. Each is a tile, a menu tab and a wizard choice.
- **Settings**: the email address (or addresses) that receive quote requests, how email is sent, WhatsApp and Instagram.
- **Team**: who can sign in.

Every request is saved in the database first and emailed second, so a mail problem never loses a request.

## What the server needs

- PHP 8.0 or newer, with PDO (MySQL or SQLite), mbstring and fileinfo. GD is recommended (it resizes uploaded pictures).
- MySQL 5.7+ or MariaDB 10.3+. Or nothing: the installer can use a built-in file database instead.
- Apache with `.htaccess` enabled (standard on cPanel-style hosting). For Nginx see the note at the end.
- HTTPS on the domain.

## Install (about ten minutes)

1. In the hosting panel, create an empty MySQL database and a user for it. Note the name, user and password.
2. Get the files onto the server. Either connect the hosting to GitHub (see `README.md`) and let the first deploy upload them, or upload the **contents** of the `site` folder to the web root (usually `public_html`) by hand.
3. Make the folders `storage` and `uploads/menus` writable by the web server (755 is usually enough, 775 on some hosts).
4. Open `https://your-domain/install.php` and fill in the form: database, your sign-in, the address that receives quote requests, and the address the site sends from.
5. Sign in at `https://your-domain/admin/`.
6. In **Settings**, set up email (next section) and press **Send a test email**.
7. In **Menus**, replace the six sample menus with the real ones.

Once the site is set up, `install.php` answers "not found" and does nothing, so it is safe that updates upload it again.

## Email: do this properly once

Quote requests are only useful if the email arrives. In **Admin > Settings** choose **Through a mailbox (SMTP)** and enter the details of a real mailbox on your domain (for example `events@your-domain`). Your email provider gives you the server, port, user name and password. Google Workspace and Microsoft 365 need an "app password" for this.

"Through the web server" needs no set-up, but on many hosts those emails go to spam or vanish.

The "send from" address should be on your own domain, and the domain's SPF and DKIM records should cover whichever service sends the mail. Your email provider's help pages explain this.

The mailbox password is stored in the site's database so the site can send mail. Use a mailbox made for the website, not a personal one.

## Changing who receives quote requests

**Admin > Settings > Send every request to.** One address, or several separated by commas. It takes effect on the next request.

## Seasonal menus

- **Admin > Menus > Add a menu.** Choose the party, name the menu, give it a season ("Winter 2026"), add the dishes.
- "Course" groups dishes under a small heading (To start, From the grill).
- Source tags (Locally grown, RDNA butchery, Home made, Cooked to order) are promises. Tag a dish only when it is true for that dish.
- The site shows no prices. If a menu text looks like it contains one, the admin warns you after saving. PDFs are not checked: look before you upload.
- When the season changes: add the new menu, then **Hide** or delete the old one.
- The six menus that come with the site are **samples** written to show the layout. They are marked "Sample" in the admin until you edit them. Check every dish and every claim before going live.

## Changing the wizard's choices

The choices (occasions, vibes, areas, dietary needs and so on) live in one file: `app/options.php`. Edit the lists there. Parties are edited in the admin, not in this file.

## Fonts

- Headlines use **Eyeful**, included in `assets/fonts`. Check that your Eyeful licence covers use on a website.
- Text uses **Hanken Grotesk**, loaded from Google Fonts.

## Looking after it

- **Back up** the database, the `uploads` folder and `config.php`. With the built-in file database, back up the `storage` folder too. None of these are in GitHub, on purpose: GitHub holds the code, the server holds the data.
- **Locked out?** Another team member can remove and re-add you under Team. If nobody can sign in, run on the server: `php app/reset-password.php you@your-domain "a new password"`.
- Six wrong passwords in 15 minutes locks that visitor out for a while.
- A visitor can send at most six requests an hour.

## Nginx

Nginx ignores `.htaccess`. Add rules that deny web access to `/app/`, `/storage/` and `/config.php`, and that never run PHP inside `/uploads/`.

## What was tested, and what was not

Tested on PHP 8.3 with the built-in file database (SQLite): install, the full wizard on desktop and phone widths, the no-JavaScript fallback, spam checks, saving requests, email over SMTP with STARTTLS and with SSL against a local test mail server, mail failures, sign-in and lock-out, menus with picture and PDF upload, parties, team, spreadsheet export.

Not tested here, so check on the real host:
- **MySQL.** No MySQL server was available in the build environment. The SQL is kept plain and the same code paths ran on SQLite, but run the installer and one full request on the real database before launch.
- **The GitHub deploy.** The check script was run here and it passes on good code and stops broken code. The upload step itself could not be run without a GitHub repository and a host, so watch the first deploy.
- **Apache `.htaccess` rules** (the test server does not read them). After install, confirm that `https://your-domain/app/bootstrap.php` and `https://your-domain/storage/` are refused.
- **Your real mailbox.** Use "Send a test email".
- Hanken Grotesk from Google Fonts (the build environment had no internet; a stand-in was used for screenshots).
