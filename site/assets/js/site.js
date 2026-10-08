/* +1 by RDNA: public site behaviour. No libraries. */
(function () {
  'use strict';

  var cfg = window.PLUSONE || {};
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var T = cfg.t || {};
  var rtl = document.documentElement.dir === 'rtl';
  var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var PLUS = '<svg class="plus" viewBox="0 0 100 100" aria-hidden="true"><path d="' + (cfg.plus || '') + '"/></svg>';

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // ---------------------------------------------------------- the hero plus
  var big = $('#bigplus');
  if (big) {
    var marks = $$('.mark', big);
    var label = $('#mood-label');
    var at = 0;
    big.addEventListener('click', function () {
      marks[at].classList.remove('is-on');
      at = (at + 1) % marks.length;
      marks[at].classList.add('is-on');
      if (label && cfg.moods) { label.textContent = cfg.moods[at]; }
    });
    var hero = big.closest('.hero');
    if (hero && !calm && window.matchMedia('(hover: hover)').matches) {
      var frame = 0;
      hero.addEventListener('pointermove', function (ev) {
        if (frame) { return; }
        frame = requestAnimationFrame(function () {
          frame = 0;
          var r = hero.getBoundingClientRect();
          var x = (ev.clientX - r.left) / r.width - 0.5;
          var y = (ev.clientY - r.top) / r.height - 0.5;
          big.style.setProperty('--tilt', (x * 10).toFixed(2) + 'deg');
          big.style.setProperty('--dx', (x * 14).toFixed(1) + 'px');
          big.style.setProperty('--dy', (y * 10).toFixed(1) + 'px');
        });
      });
      hero.addEventListener('pointerleave', function () {
        big.style.setProperty('--tilt', '0deg');
        big.style.setProperty('--dx', '0px');
        big.style.setProperty('--dy', '0px');
      });
    }
  }

  // ---------------------------------------------------------- menu tabs
  var tabs = $$('.tabs .tab');
  function showTab(slug, focus) {
    tabs.forEach(function (t) {
      var on = t.id === 'tab-' + slug;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      if (on) { t.removeAttribute('tabindex'); if (focus) { t.focus(); } } else { t.setAttribute('tabindex', '-1'); }
      var panel = document.getElementById(t.getAttribute('aria-controls'));
      if (panel) { panel.hidden = !on; }
    });
  }
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () { showTab(t.id.replace('tab-', '')); });
    t.addEventListener('keydown', function (ev) {
      var step = ev.key === 'ArrowRight' ? 1 : ev.key === 'ArrowLeft' ? -1 : 0;
      if (!step) { return; }
      if (rtl) { step = -step; }
      ev.preventDefault();
      var next = tabs[(i + step + tabs.length) % tabs.length];
      showTab(next.id.replace('tab-', ''), true);
    });
  });
  $$('[data-tab]').forEach(function (a) {
    a.addEventListener('click', function () { showTab(a.getAttribute('data-tab')); });
  });

  // ---------------------------------------------------------- the quote wizard
  var form = $('#wizard');
  if (!form) { return; }

  var steps = $$('.step', form);
  var dots = $$('.wiz-progress li', form);
  var back = $('#wiz-back');
  var next = $('#wiz-next');
  var send = $('#wiz-send');
  var errorBox = $('#wiz-error');
  var count = $('#wiz-count');
  var sumLine = $('#wiz-sum');
  var guests = $('#guests');
  var now = 0;

  function checked(name) { var el = form.querySelector('[name="' + name + '"]:checked'); return el ? el.value : ''; }
  function checkedAll(name) { return $$('[name="' + name + '"]:checked', form).map(function (el) { return el.value; }); }
  // The name a guest sees for each ticked choice. In Arabic it differs from the value that is sent.
  function checkedLabels(name) { return $$('[name="' + name + '"]:checked', form).map(function (el) { return el.getAttribute('data-label') || el.value; }); }

  function guestsText(n) {
    if (cfg.lang === 'ar') {
      // Egyptian Arabic counts in its own way: 1 and 2 have their own words, 3 to 10 take the plural, the rest the singular.
      if (n === 1) { return 'ضيف واحد'; }
      if (n === 2) { return 'ضيفين'; }
      var r = n % 100;
      return n + (r >= 3 && r <= 10 ? ' ضيوف' : ' ضيف');
    }
    return n + (n === 1 ? ' guest' : ' guests');
  }
  function field(name) { return form.elements[name]; }

  function syncSetting() {
    var s = checked('setting') || 'home';
    $$('[data-setting]', form).forEach(function (el) {
      var on = el.getAttribute('data-setting') === s;
      el.hidden = !on;
      if (!on) { $$('input', el).forEach(function (i) { if (i.type === 'radio') { i.checked = false; } }); }
    });
  }

  function drawSum() {
    if (!sumLine) { return; }
    var parts = [T.you || 'You'];
    var n = parseInt(guests.value, 10);
    if (now >= 1 && n > 0) { parts.push(guestsText(n)); }
    var parties = checkedLabels('parties[]');
    if (now >= 2 && parties.length) { parts.push(parties.length > 2 ? parties[0] + (T.more || ' and more') : parties.join(T.and || ' and ')); }
    sumLine.innerHTML = parts.map(esc).join(PLUS);
  }

  function show(i, focus) {
    now = Math.max(0, Math.min(steps.length - 1, i));
    steps.forEach(function (s, k) { s.classList.toggle('is-on', k === now); });
    dots.forEach(function (d, k) { d.className = k < now ? 'done' : k === now ? 'now' : ''; });
    back.hidden = now === 0;
    next.hidden = now === steps.length - 1;
    send.hidden = now !== steps.length - 1;
    count.textContent = (now + 1) + (T.of || ' of ') + steps.length;
    fail('');
    drawSum();
    if (focus) {
      var target = steps[now].querySelector('input:not([type=hidden]), textarea, select');
      var card = form.getBoundingClientRect();
      if (card.top < 70 || card.top > window.innerHeight * 0.5) {
        window.scrollTo({ top: window.pageYOffset + card.top - 84, behavior: calm ? 'auto' : 'smooth' });
      }
      if (target && window.matchMedia('(hover: hover)').matches) { target.focus({ preventScroll: true }); }
    }
  }

  function fail(message) {
    errorBox.textContent = message;
    errorBox.hidden = !message;
  }

  function problem(i) {
    var key = steps[i].getAttribute('data-step');
    if (key === 'occasion' && !checked('event_type')) { return T.pick || 'Pick the one that is closest.'; }
    if (key === 'guests') {
      var n = parseInt(guests.value, 10);
      if (!(n >= 1 && n <= 5000)) { return T.guests || 'Tell us roughly how many guests.'; }
    }
    if (key === 'when') {
      var d = field('event_date').value;
      if (d && d < field('event_date').min) { return T.date || 'Choose a date from today onwards, or leave it open.'; }
    }
    if (key === 'you') {
      if (!field('name').value.trim()) { return T.name || 'Tell us your name.'; }
      if (field('phone').value.replace(/\D+/g, '').length < 8) { return T.phone || 'Give us a mobile number we can reach you on.'; }
      var em = field('email').value.trim();
      if (em && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(em)) { return T.email || 'That email address does not look right.'; }
    }
    return '';
  }

  function go(dir) {
    if (dir > 0) {
      var p = problem(now);
      if (p) { fail(p); return; }
    }
    show(now + dir, true);
  }

  next.addEventListener('click', function () { go(1); });
  back.addEventListener('click', function () { go(-1); });
  form.addEventListener('keydown', function (ev) {
    if (ev.key === 'Enter' && ev.target.tagName !== 'TEXTAREA' && ev.target.type !== 'submit' && ev.target.type !== 'button') {
      if (now < steps.length - 1) { ev.preventDefault(); go(1); }
    }
  });
  form.addEventListener('change', function (ev) {
    if (ev.target.name === 'setting') { syncSetting(); }
    // A single tap answers the first question, so move on.
    if (ev.target.name === 'event_type' && now === 0) { setTimeout(function () { go(1); }, 220); }
    drawSum();
  });
  form.addEventListener('input', function () { fail(''); drawSum(); });

  $$('[data-add]', form).forEach(function (b) {
    b.addEventListener('click', function () {
      var n = (parseInt(guests.value, 10) || 0) + parseInt(b.getAttribute('data-add'), 10);
      guests.value = Math.max(1, Math.min(5000, n));
      drawSum();
    });
  });
  $$('[data-guests]', form).forEach(function (b) {
    b.addEventListener('click', function () { guests.value = b.getAttribute('data-guests'); drawSum(); });
  });

  // "Ask for this party" on a menu ticks that party and opens the wizard.
  $$('[data-party]').forEach(function (a) {
    a.addEventListener('click', function () {
      var name = a.getAttribute('data-party');
      $$('[name="parties[]"]', form).forEach(function (box) { if (box.value === name) { box.checked = true; } });
      drawSum();
    });
  });

  function finish(data) {
    var card = $('#wiz-done-card');
    var first = field('name').value.trim().split(/\s+/)[0];
    $('#done-name').textContent = first ? (T.comma || ', ') + first : '';
    $('#done-ref').textContent = data.ref ? (T.ref || 'Your reference: ') + data.ref : '';
    var wa = $('#done-wa');
    if (data.whatsapp) { wa.href = data.whatsapp; wa.hidden = false; } else { wa.hidden = true; }
    if (cfg.preview) { $('#done-preview').hidden = false; }
    form.hidden = true;
    card.hidden = false;
    $('#wiz-done').focus({ preventScroll: true });
    var section = card.closest('section');
    if (section) { section.scrollLeft = 0; }
    var top = card.getBoundingClientRect().top;
    if (top < 70) { window.scrollTo({ top: window.pageYOffset + top - 84, behavior: calm ? 'auto' : 'smooth' }); }
  }

  function headline() {
    var parties = checkedAll('parties[]');
    var what = parties.length ? parties.join(', ') : checked('event_type');
    var s = what + ' for ' + guests.value + ' guests';
    var d = field('event_date').value;
    if (d) {
      var t = new Date(d + 'T12:00:00');
      if (!isNaN(t)) { s += ', ' + t.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }); }
    }
    return s;
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    for (var i = 0; i < steps.length; i++) {
      var p = problem(i);
      if (p) { show(i, true); fail(p); return; }
    }
    if (cfg.preview) {
      var text = 'Hello +1, I would like a quote: ' + headline() + '.';
      finish({ ref: '', whatsapp: cfg.whatsapp ? 'https://wa.me/' + cfg.whatsapp + '?text=' + encodeURIComponent(text) : '' });
      return;
    }
    send.disabled = true;
    send.textContent = T.sending || 'Sending';
    fetch(cfg.endpoint || form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (res) {
      return res.json().then(function (data) { return { res: res, data: data }; });
    }).then(function (out) {
      if (out.data && out.data.ok) { finish(out.data); return; }
      throw new Error((out.data && out.data.error) || '');
    }).catch(function (err) {
      send.disabled = false;
      send.textContent = T.send || 'Send my request';
      fail(err && err.message ? err.message : (T.failed || 'That did not go through. Try again, or message us on WhatsApp.'));
    });
  });

  syncSetting();
  show(0, false);
})();
