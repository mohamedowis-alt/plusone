#!/usr/bin/env bash
# Checks the site before it is allowed to go live:
#   1. every PHP file is free of syntax errors
#   2. a fresh copy installs, shows the home page, takes a quote request and locks its installer
# Run it yourself with:  bash scripts/check.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${PORT:-8791}"
WORK="$(mktemp -d)"
SERVER=""
trap 'if [ -n "$SERVER" ]; then kill "$SERVER" 2>/dev/null || true; fi; rm -rf "$WORK"' EXIT

echo "1. PHP syntax"
find "$ROOT/site" -name '*.php' -print0 | while IFS= read -r -d '' f; do
  php -l "$f" > /dev/null || { echo "Syntax error in $f"; exit 1; }
done

echo "2. Fresh install"
cp -r "$ROOT/site" "$WORK/site"
rm -f "$WORK/site/config.php" "$WORK/site/storage/"*.sqlite "$WORK/site/storage/installed.lock"
php -S "127.0.0.1:$PORT" -t "$WORK/site" > "$WORK/server.log" 2>&1 &
SERVER=$!
sleep 1
H="http://127.0.0.1:$PORT"

fail() { echo "FAILED: $1"; cat "$WORK/server.log" | tail -20; exit 1; }

INSTALLED="$(curl -s -X POST "$H/install.php" \
  -d driver=sqlite -d name=Check -d email=check@example.com -d password=check-password-1 \
  -d notify=check@example.com -d from=check@example.com)"
grep -q 'The site is set up' <<< "$INSTALLED" || fail "installer"

[ "$(curl -s -o /dev/null -w '%{http_code}' "$H/install.php")" = "404" ] || fail "installer should lock itself"

HOME_PAGE="$(curl -s "$H/index.php")"
grep -q 'Bring us' <<< "$HOME_PAGE" || fail "home page"
grep -q 'id="wizard"' <<< "$HOME_PAGE" || fail "quote wizard missing"
if grep -qi 'EGP' <<< "$HOME_PAGE"; then fail "a price is showing on the home page"; fi

for path in assets/css/site.css assets/js/site.js assets/fonts/Eyeful-Regular.woff admin/login.php; do
  [ "$(curl -s -o /dev/null -w '%{http_code}' "$H/$path")" = "200" ] || fail "$path"
done

echo "3. Quote request"
STAMP="$(grep -o 'name="stamp" value="[^"]*"' <<< "$HOME_PAGE" | sed 's/.*value="//;s/"//')"
sleep 3.2
SENT="$(curl -s -X POST "$H/quote.php" -H 'Accept: application/json' --data-urlencode "stamp=$STAMP" \
  -d setting=home -d event_type=Birthday -d guests=20 -d name=Check -d phone=01000000000)"
grep -q '"ok":true' <<< "$SENT" || fail "quote request"

echo "All checks passed."
