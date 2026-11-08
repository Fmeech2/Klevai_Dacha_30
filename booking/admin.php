<?php
/**
 * КЛЁВАЯ ДАЧА — страница хозяина: заявки, календарь занятости, брони.
 *
 * Открывается по непубличной ссылке:  .../booking/admin.php?k=СЕКРЕТНЫЙ_КЛЮЧ
 * Ключ задаётся в config.php. Без верного ключа страница отвечает «404».
 * Ключ попадает только на эту страницу и уходит дальше в заголовке X-Admin-Key;
 * публичный сайт его не содержит. На страницу нет ссылок с сайта, она закрыта от
 * индексации заголовками, а каждое действие повторно проверяется в admin-api.php.
 *
 * Весь JavaScript панели — в этом файле (в конце). Данные клиентов выводятся только
 * через textContent, поэтому экранировать HTML вручную не нужно.
 */

declare(strict_types=1);

define('KD_BOOKING', true);
require __DIR__ . '/lib.php';

try {
    kd_bootstrap();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Ошибка конфигурации: проверьте booking/config.php.';
    exit;
}

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

if (!admin_key_valid_format()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Админ-панель отключена: задайте admin_key (24–128 символов: латиница, цифры, - и _) в booking/config.php.';
    exit;
}

$key = (string) ($_GET['k'] ?? '');
if (!admin_key_matches($key)) {
    usleep(300000);
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$nonce = base64_encode(random_bytes(16));
header(
    "Content-Security-Policy: default-src 'none'; script-src 'nonce-$nonce'; style-src 'self'; "
    . "font-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'"
);
$keyJs = json_encode($key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="robots" content="noindex, nofollow, noarchive">
  <meta name="referrer" content="no-referrer">
  <meta name="theme-color" content="#2F80ED">
  <title>Бронирование — Клёвая Дача</title>
  <link rel="icon" href="../img/Logo_zaglyshka_icon.png">
  <link rel="stylesheet" href="admin.css">
</head>
<body>

<header class="ad-top">
  <div class="ad-top-in">
    <div class="ad-brand">
      <img class="ad-logo" src="../img/Logo_zaglyshka_footer2.png" alt="">
      <div>
        <div class="ad-brand-main">Клёвая Дача</div>
        <div class="ad-brand-sub">Управление бронированием</div>
      </div>
    </div>
    <button type="button" class="ad-btn ad-btn-sm" id="adRefresh">Обновить</button>
  </div>
</header>

<div class="ad-wrap">
  <div class="ad-banners" id="adBanners"></div>

  <nav class="ad-tabs" role="tablist" aria-label="Разделы">
    <button type="button" role="tab" data-tab="requests" aria-selected="true">Заявки <span class="ad-badge" id="adBadge" hidden>0</span></button>
    <button type="button" role="tab" data-tab="calendar" aria-selected="false">Календарь</button>
    <button type="button" role="tab" data-tab="bookings" aria-selected="false">Брони</button>
    <button type="button" role="tab" data-tab="reviews" aria-selected="false">Отзывы <span class="ad-badge" id="adRevBadge" hidden>0</span></button>
    <button type="button" role="tab" data-tab="media" aria-selected="false">Медиа</button>
    <button type="button" role="tab" data-tab="stats" aria-selected="false">Статистика</button>
    <button type="button" role="tab" data-tab="service" aria-selected="false">Служебное</button>
  </nav>

  <!-- ===== Заявки ===== -->
  <section id="tab-requests" role="tabpanel">
    <div class="ad-filters" id="adFilters"></div>
    <div class="ad-list" id="adRequestList"></div>
  </section>

  <!-- ===== Календарь занятости ===== -->
  <section id="tab-calendar" role="tabpanel" hidden>
    <div class="ad-mode" id="adMode"></div>

    <div class="ad-cal-card">
      <div class="ad-cal-head">
        <button type="button" class="ad-nav" id="adPrev" aria-label="Предыдущий месяц">‹</button>
        <div class="ad-cal-titles" id="adCalTitle" aria-live="polite"></div>
        <button type="button" class="ad-nav" id="adNext" aria-label="Следующий месяц">›</button>
      </div>
      <div class="ad-months" id="adMonths"></div>
      <ul class="ad-legend">
        <li><span class="ad-sw"></span>Свободно</li>
        <li><span class="ad-sw req"></span>Занято по заявке</li>
        <li><span class="ad-sw man"></span>Занято вручную</li>
        <li><span class="ad-sw sel"></span>Выбранный период</li>
        <li><span class="ad-sw edit"></span>Изменяемая бронь</li>
      </ul>
      <p class="ad-help ad-help-top">Занята ночь заезда и все ночи до даты выезда. День выезда свободен: в этот день может заехать следующий гость — такие дни закрашены наполовину.</p>
    </div>

    <div class="ad-sel-card">
      <h3>Выбранный период</h3>
      <div class="ad-dates">
        <label class="ad-field">Заезд <input type="date" id="adIn"></label>
        <label class="ad-field">Выезд <input type="date" id="adOut"></label>
      </div>
      <div class="ad-sel-info" id="adSelInfo" aria-live="polite"></div>
      <label class="ad-field">Внутренняя заметка (видна только вам)
        <textarea id="adNote" maxlength="500" rows="2" placeholder="Например: оплата на месте, приедут с лодкой"></textarea>
      </label>
      <div class="ad-actions">
        <button type="button" class="ad-btn ad-btn-primary" id="adSave">Заблокировать даты</button>
        <button type="button" class="ad-btn" id="adReset">Сбросить выбор</button>
      </div>
    </div>
  </section>

  <!-- ===== Брони и блокировки ===== -->
  <section id="tab-bookings" role="tabpanel" hidden>
    <div id="adBookingList"></div>
  </section>

  <!-- ===== Отзывы с сайта (модерация) ===== -->
  <section id="tab-reviews" role="tabpanel" hidden>
    <div id="adReviews"></div>
  </section>

  <!-- ===== Фото и видео главной страницы ===== -->
  <section id="tab-media" role="tabpanel" hidden>
    <div id="adMedia"></div>
  </section>

  <!-- ===== Статистика посещений ===== -->
  <section id="tab-stats" role="tabpanel" hidden>
    <div id="adStats"></div>
  </section>

  <!-- ===== Служебное ===== -->
  <section id="tab-service" role="tabpanel" hidden>
    <div class="ad-service" id="adService"></div>
  </section>
</div>

<dialog class="ad-dialog" id="adDetails"></dialog>
<dialog class="ad-dialog ad-dialog-sm" id="adConfirm"></dialog>
<div class="ad-toasts" id="adToasts" aria-live="polite"></div>

<script nonce="<?= $nonce ?>">
(() => {
  'use strict';

  const KEY = <?= $keyJs ?>;
  const API = 'admin-api.php';

  const STATUS = {
    new: 'Новая заявка', contacted: 'Хозяин связался', confirmed: 'Подтверждена',
    rejected: 'Отклонена', cancelled: 'Отменена',
  };
  const DEVICE = { phone: 'телефон', tablet: 'планшет', pc: 'компьютер' };
  const MONTHS = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
  const MONTHS_GEN = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
  const WEEKDAYS = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

  /* ================= Утилиты ================= */

  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

  /** Создание элементов без innerHTML: текст всегда вставляется как текст. */
  function h(tag, attrs, ...kids) {
    const el = document.createElement(tag);
    if (attrs) {
      for (const k of Object.keys(attrs)) {
        const v = attrs[k];
        if (v == null || v === false) continue;
        if (k === 'class') el.className = v;
        else if (k.startsWith('on') && typeof v === 'function') el.addEventListener(k.slice(2), v);
        else if (k === 'dataset') Object.assign(el.dataset, v);
        else el.setAttribute(k, v === true ? '' : String(v));
      }
    }
    for (const kid of kids.flat(Infinity)) {
      if (kid == null || kid === false) continue;
      el.append(kid);
    }
    return el;
  }

  /** replaceChildren, но null/false пропускаются (иначе в страницу попадёт слово «null»). */
  function setChildren(el, ...kids) {
    el.replaceChildren(...kids.flat(Infinity).filter(k => k != null && k !== false));
  }

  function nightsWord(n) {
    const a = Math.abs(n) % 100, d = a % 10;
    if (a > 10 && a < 20) return 'ночей';
    return d === 1 ? 'ночь' : (d >= 2 && d <= 4 ? 'ночи' : 'ночей');
  }

  /* ----- Даты: строки YYYY-MM-DD, арифметика только в UTC (никаких сдвигов поясов) ----- */
  const D = {
    parse(iso) {
      const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
      if (!m) return null;
      const y = +m[1], mo = +m[2], d = +m[3];
      const t = new Date(Date.UTC(y, mo - 1, d));
      if (t.getUTCFullYear() !== y || t.getUTCMonth() !== mo - 1 || t.getUTCDate() !== d) return null;
      return { y, m: mo, d };
    },
    iso(y, m, d) { return String(y).padStart(4, '0') + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0'); },
    toT(iso) { const p = D.parse(iso); return Date.UTC(p.y, p.m - 1, p.d); },
    fromT(t) { const d = new Date(t); return D.iso(d.getUTCFullYear(), d.getUTCMonth() + 1, d.getUTCDate()); },
    add(iso, n) { return D.fromT(D.toT(iso) + n * 86400000); },
    diff(a, b) { return Math.round((D.toT(b) - D.toT(a)) / 86400000); },
    dow(iso) { return (new Date(D.toT(iso)).getUTCDay() + 6) % 7; },          // понедельник = 0
    dim(y, m) { return new Date(Date.UTC(y, m, 0)).getUTCDate(); },
    ru(iso) { return iso.slice(8, 10) + '.' + iso.slice(5, 7) + '.' + iso.slice(0, 4); },
    long(iso) { const p = D.parse(iso); return p.d + ' ' + MONTHS_GEN[p.m - 1] + ' ' + p.y; },
  };

  function fmtDT(iso) {   // 2026-10-28T14:03:11+04:00 -> 28.10.2026 14:03 (без пересчёта поясов)
    if (!iso) return '';
    return iso.slice(8, 10) + '.' + iso.slice(5, 7) + '.' + iso.slice(0, 4) + ' ' + iso.slice(11, 16);
  }
  function fmtPhone(p) {
    const m = /^\+7(\d{3})(\d{3})(\d{2})(\d{2})$/.exec(p || '');
    return m ? '+7 ' + m[1] + ' ' + m[2] + '-' + m[3] + '-' + m[4] : (p || '');
  }
  const fullName = r => [r.lastName, r.firstName, r.patronymic].filter(Boolean).join(' ');

  /* ================= Обращение к серверу ================= */

  class ApiErr extends Error {
    constructor(code, message, extra) { super(message); this.code = code; this.extra = extra || {}; }
  }

  async function api(action, opts) {
    const o = opts || {};
    let url = API + '?action=' + encodeURIComponent(action);
    if (o.query) url += '&' + new URLSearchParams(o.query).toString();
    const headers = { 'X-Admin-Key': KEY };
    if (o.body) headers['Content-Type'] = 'application/json';
    let res;
    try {
      res = await fetch(url, { method: o.method || 'GET', headers, cache: 'no-store', body: o.body ? JSON.stringify(o.body) : undefined });
    } catch (e) {
      throw new ApiErr('network', 'Нет связи с сервером. Проверьте интернет и повторите.');
    }
    let json;
    try { json = await res.json(); } catch (e) {
      throw new ApiErr('server', 'Сервер вернул непонятный ответ (HTTP ' + res.status + ').');
    }
    if (!json || json.ok !== true) {
      const err = (json && json.error) || {};
      throw new ApiErr(err.code || 'error', err.message || 'Ошибка сервера.', err);
    }
    return json.data;
  }

  /* ================= Состояние ================= */

  const S = { today: '', requests: [], bookings: [], health: null, loaded: false, seenMax: 0, filter: 'active', exposed: false, netError: false };
  // Модули вкладок (admin-*.js) подключаются через KDAdmin.register(): у каждого есть init(), render() и onTab(имя)
  const modules = [];
  // Календарь. mode: block — ручная блокировка; request — подтверждение заявки; edit — изменение брони
  const C = { y: 0, m: 1, in: null, out: null, mode: 'block', requestId: null, bookingId: null, note: '', busy: false, error: null };

  /* ================= Уведомления и подтверждение ================= */

  function toast(msg, kind) {
    const el = h('div', { class: 'ad-toast ' + (kind || '') }, msg);
    $('#adToasts').append(el);
    setTimeout(() => el.remove(), kind === 'error' ? 8000 : 4500);
  }

  function confirmBox(opts) {
    return new Promise(resolve => {
      const dlg = $('#adConfirm');
      dlg.returnValue = '';
      dlg.replaceChildren(h('div', { class: 'ad-dlg' },
        h('h3', null, opts.title),
        h('p', null, opts.text),
        h('div', { class: 'ad-actions' },
          h('button', { type: 'button', class: 'ad-btn ' + (opts.danger ? 'ad-btn-danger' : 'ad-btn-primary'), onclick: () => dlg.close('ok') }, opts.ok || 'Да'),
          h('button', { type: 'button', class: 'ad-btn', onclick: () => dlg.close('cancel') }, 'Отмена'))));
      dlg.addEventListener('close', () => resolve(dlg.returnValue === 'ok'), { once: true });
      dlg.showModal();
    });
  }

  async function withBusy(btn, fn) {
    if (btn.disabled) return;
    btn.disabled = true;
    try { await fn(); } finally { btn.disabled = false; }
  }

  /* ================= Загрузка данных ================= */

  async function load(silent) {
    try {
      const d = await api('overview');
      S.netError = false;
      S.today = d.today; S.requests = d.requests; S.bookings = d.bookings; S.health = d.health;
      const maxNum = S.requests.reduce((m, r) => Math.max(m, r.number), 0);
      if (S.loaded && maxNum > S.seenMax) toast('Поступила новая заявка № ' + maxNum, 'ok');
      S.seenMax = maxNum;
      if (!S.loaded) {
        S.loaded = true;
        const t = D.parse(S.today);
        C.y = t.y; C.m = t.m;
      }
      renderAll();
    } catch (e) {
      if (e.code === 'forbidden') {
        document.body.replaceChildren(h('p', { class: 'ad-empty' }, 'Доступ запрещён: проверьте ссылку.'));
        return;
      }
      S.netError = true;
      renderBanners();
      if (!silent) toast(e.message, 'error');
    }
  }

  function renderAll() {
    renderBanners();
    renderBadge();
    renderRequests();
    renderBookings();
    renderCalendar();
    renderService();
    modules.forEach(m => { if (m.render) m.render(); });
  }

  /* ================= Вкладки ================= */

  function setTab(name) {
    $$('.ad-tabs [data-tab]').forEach(b => b.setAttribute('aria-selected', b.dataset.tab === name ? 'true' : 'false'));
    $$('[id^="tab-"]').forEach(sec => { sec.hidden = (sec.id !== 'tab-' + name); });
    window.scrollTo(0, 0);
    modules.forEach(m => { if (m.onTab) m.onTab(name); });
  }

  /* ================= Баннеры ================= */

  function renderBanners() {
    const box = $('#adBanners');
    const list = [];
    const hl = S.health;
    if (S.netError) list.push(h('div', { class: 'ad-banner bad' }, 'Нет связи с сервером. Данные на экране могут быть устаревшими.'));
    if (S.exposed) list.push(h('div', { class: 'ad-banner bad' }, 'Опасно: файл data/booking-data.json открывается из интернета. Перенесите папку данных за пределы публичной папки сайта (параметр data_dir в config.php) или закройте доступ настройками сервера.'));
    if (hl) {
      if (!hl.telegramConfigured) list.push(h('div', { class: 'ad-banner' }, 'Telegram не настроен: уведомления о заявках не приходят. Заполните telegram_bot_token и telegram_chat_id в booking/config.php. Заявки при этом сохраняются и видны здесь.'));
      if (hl.failedNotifications > 0 && hl.telegramConfigured) list.push(h('div', { class: 'ad-banner' }, 'Уведомление в Telegram не доставлено по заявкам: ' + hl.failedNotifications + '. Откройте заявку и нажмите «Повторить уведомление».'));
      if (!hl.curl && !hl.allowUrlFopen) list.push(h('div', { class: 'ad-banner bad' }, 'На хостинге недоступны ни cURL, ни allow_url_fopen — отправка в Telegram невозможна. Попросите хостинг включить расширение cURL.'));
      if (!hl.file.writable) list.push(h('div', { class: 'ad-banner bad' }, 'Папка данных недоступна для записи: новые заявки сохранить не удастся.'));
    }
    box.replaceChildren(...list);
  }

  function renderBadge() {
    const n = S.requests.filter(r => r.status === 'new').length;
    const b = $('#adBadge');
    b.hidden = n === 0;
    b.textContent = String(n);
  }

  /* ================= Заявки ================= */

  const FILTERS = [
    { id: 'active', label: 'В работе', test: r => r.status === 'new' || r.status === 'contacted' },
    { id: 'confirmed', label: 'Подтверждённые', test: r => r.status === 'confirmed' },
    { id: 'archive', label: 'Архив', test: r => r.status === 'rejected' || r.status === 'cancelled' },
    { id: 'all', label: 'Все', test: () => true },
  ];

  const statusPill = st => h('span', { class: 'ad-pill s-' + st }, STATUS[st] || st);

  function deviceNode(r) {
    const dv = r.device || {};
    return h('span', null, DEVICE[dv.type] || 'устройство', dv.label ? h('span', { class: 'ad-dim' }, ' (' + dv.label + ')') : null);
  }

  function renderRequests() {
    const f = FILTERS.find(x => x.id === S.filter) || FILTERS[0];
    $('#adFilters').replaceChildren(...FILTERS.map(x => h('button', {
      type: 'button', class: 'ad-chip', 'aria-pressed': x.id === S.filter ? 'true' : 'false',
      onclick: () => { S.filter = x.id; renderRequests(); },
    }, x.label, h('b', null, String(S.requests.filter(x.test).length)))));

    const items = S.requests.filter(f.test);
    const list = $('#adRequestList');
    if (!items.length) {
      list.replaceChildren(h('div', { class: 'ad-empty' }, f.id === 'active' ? 'Новых заявок нет. Когда гость оставит заявку, она появится здесь.' : 'Здесь пока пусто.'));
      return;
    }
    list.replaceChildren(...items.map(requestCard));
  }

  function requestCard(r) {
    const n = D.diff(r.checkIn, r.checkOut);
    const open = () => openDetails(r.id);
    return h('article', {
      class: 'ad-card ad-req' + (r.status === 'new' ? ' is-new' : ''), tabindex: '0', role: 'button',
      'aria-label': 'Открыть заявку № ' + r.number, onclick: open,
      onkeydown: e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } },
    },
      h('div', { class: 'ad-req-top' },
        h('span', { class: 'ad-req-num' }, '№ ' + r.number), statusPill(r.status),
        h('span', { class: 'ad-req-time' }, fmtDT(r.createdAt))),
      h('div', { class: 'ad-req-name' }, fullName(r)),
      h('div', { class: 'ad-req-phone' }, h('a', { href: 'tel:' + r.phone, onclick: e => e.stopPropagation() }, fmtPhone(r.phone))),
      h('div', { class: 'ad-req-dates' }, D.ru(r.checkIn) + ' — ' + D.ru(r.checkOut) + ', ' + n + ' ' + nightsWord(n)),
      r.comment ? h('div', { class: 'ad-req-meta' }, 'Комментарий: ' + r.comment) : null,
      r.adminNote ? h('div', { class: 'ad-req-meta' }, 'Ваша заметка: ' + r.adminNote) : null,
      h('div', { class: 'ad-req-meta' }, 'Заявка с: ', deviceNode(r)),
      (r.notificationStatus === 'failed' || r.notificationStatus === 'not_configured')
        ? h('div', { class: 'ad-req-warn' }, 'Уведомление в Telegram не доставлено') : null);
  }

  /* ----- Подробности заявки ----- */

  async function openDetails(id) {
    let r = S.requests.find(x => x.id === id);
    let booking = null;
    try {
      const d = await api('request', { query: { id } });
      r = d.request; booking = d.booking;
    } catch (e) {
      toast(e.message, 'error');
      if (!r) return;
    }
    const dlg = $('#adDetails');
    renderDetails(dlg, r, booking);
    if (!dlg.open) dlg.showModal();
  }

  function notificationText(r) {
    switch (r.notificationStatus) {
      case 'sent': return 'Отправлено' + (r.notifiedAt ? ' (' + fmtDT(r.notifiedAt) + ')' : '');
      case 'pending': return 'Отправляется или не было попытки';
      case 'not_configured': return 'Telegram не настроен';
      default: return 'Не доставлено' + (r.notificationError ? ': ' + r.notificationError : '') + (r.notificationAttempts ? ' (попыток: ' + r.notificationAttempts + ')' : '');
    }
  }

  function renderDetails(dlg, r, booking) {
    const n = D.diff(r.checkIn, r.checkOut);
    const hasActive = !!(booking && booking.status === 'active');
    const statusIds = Object.keys(STATUS).filter(s => s !== 'confirmed' || r.status === 'confirmed');
    const select = h('select', { id: 'adStatus', disabled: hasActive },
      statusIds.map(s => h('option', { value: s, selected: s === r.status }, STATUS[s])));
    const noteField = h('textarea', { id: 'adReqNote', maxlength: '2000', rows: '3', placeholder: 'Например: перезвонить после 18:00' });
    noteField.value = r.adminNote || '';

    const rows = [
      ['Имя', r.firstName], ['Фамилия', r.lastName || 'не указана'], ['Отчество', r.patronymic || 'не указано'],
      ['Телефон', h('a', { href: 'tel:' + r.phone }, fmtPhone(r.phone))],
      ['Заезд', D.ru(r.checkIn)], ['Выезд', D.ru(r.checkOut)], ['Ночей', String(n)],
      ['Комментарий', r.comment || 'нет'],
      ['Поступила', fmtDT(r.createdAt)],
      ['Устройство', deviceNode(r)],
      ['Telegram', notificationText(r)],
      ['Согласие на ПДн', r.consentAt ? fmtDT(r.consentAt) : 'нет данных'],
    ];
    if (booking) rows.push(['Бронь', D.ru(booking.checkIn) + ' — ' + D.ru(booking.checkOut) + (booking.status === 'active' ? ' (действует)' : ' (отменена)')]);

    const saveBtn = h('button', { type: 'button', class: 'ad-btn ad-btn-primary', onclick: e => withBusy(e.currentTarget, () => saveRequest(r, select, noteField)) }, 'Сохранить');
    const actions = [saveBtn];
    if (!hasActive) actions.push(h('button', { type: 'button', class: 'ad-btn', onclick: () => startConfirm(r) }, 'Отметить даты занятыми'));
    if (hasActive) actions.push(h('button', { type: 'button', class: 'ad-btn ad-btn-danger', onclick: () => cancelBookingFlow(booking, r) }, 'Отменить бронирование'));
    actions.push(h('button', { type: 'button', class: 'ad-btn', onclick: e => withBusy(e.currentTarget, () => resend(r)) },
      r.notificationStatus === 'sent' ? 'Отправить уведомление ещё раз' : 'Повторить уведомление в Telegram'));
    actions.push(h('a', { class: 'ad-btn', href: 'tel:' + r.phone }, 'Позвонить'));

    const hist = (r.statusHistory || []).map(x => h('li', null, fmtDT(x.at) + ' — ' + (STATUS[x.status] || x.status) + (x.by === 'visitor' ? ' (заявка с сайта)' : '')));

    dlg.replaceChildren(h('div', { class: 'ad-dlg' },
      h('div', { class: 'ad-dlg-head' }, h('h3', null, 'Заявка № ' + r.number), statusPill(r.status),
        h('button', { type: 'button', class: 'ad-x', 'aria-label': 'Закрыть', onclick: () => dlg.close() }, '×')),
      h('dl', { class: 'ad-dl' }, rows.map(([k, v]) => [h('dt', null, k), h('dd', null, v)])),
      hasActive ? h('div', { class: 'ad-note-box' }, 'Бронь действует, поэтому статус заблокирован. Чтобы изменить его, сначала отмените бронирование: даты снова станут свободными.') : null,
      h('label', { class: 'ad-field' }, 'Статус заявки', select),
      h('label', { class: 'ad-field' }, 'Внутренняя заметка (видна только вам)', noteField),
      h('div', { class: 'ad-actions' }, actions),
      hist.length ? h('div', null, h('div', { class: 'ad-dim' }, 'История'), h('ul', { class: 'ad-hist' }, hist)) : null));
  }

  async function saveRequest(r, select, noteField) {
    const body = { id: r.id, adminNote: noteField.value };
    if (!select.disabled && select.value !== r.status) body.status = select.value;
    try {
      await api('updateRequest', { method: 'POST', body });
      toast('Сохранено', 'ok');
      await load(true);
      openDetails(r.id);
    } catch (e) { toast(e.message, 'error'); }
  }

  async function resend(r) {
    try {
      const d = await api('resendNotification', { method: 'POST', body: { id: r.id } });
      toast(d.sent ? 'Уведомление отправлено в Telegram' : 'Не удалось отправить: ' + d.error, d.sent ? 'ok' : 'error');
      await load(true);
      openDetails(r.id);
    } catch (e) { toast(e.message, 'error'); }
  }

  /* ================= Календарь занятости ================= */

  const bookingOf = id => S.bookings.find(b => b.id === id);
  const requestOf = id => S.requests.find(r => r.id === id);

  function bookingLabel(b) {
    if (b.requestId) {
      const r = requestOf(b.requestId);
      return r ? 'заявка № ' + r.number + ', ' + r.firstName : 'по заявке';
    }
    return 'ручная блокировка';
  }

  /** Карта «ночь -> бронь» по действующим броням. Изменяемая бронь в карту не попадает. */
  function nightOwners() {
    const map = new Map();
    for (const b of S.bookings) {
      if (b.status !== 'active' || b.id === C.bookingId) continue;
      for (let d = b.checkIn; d < b.checkOut; d = D.add(d, 1)) map.set(d, b);
    }
    return map;
  }

  function conflictsOf(a, b) {
    return S.bookings.filter(x => x.status === 'active' && x.id !== C.bookingId && a < x.checkOut && b > x.checkIn);
  }

  function resetCalendar() {
    C.in = null; C.out = null; C.mode = 'block'; C.requestId = null; C.bookingId = null; C.note = ''; C.error = null;
  }

  function goToMonth(iso) { const p = D.parse(iso); if (p) { C.y = p.y; C.m = p.m; } }

  function startConfirm(r) {
    resetCalendar();
    C.mode = 'request'; C.requestId = r.id; C.in = r.checkIn; C.out = r.checkOut;
    goToMonth(r.checkIn);
    $('#adDetails').close();
    renderCalendar();
    setTab('calendar');
  }

  function startEdit(b) {
    resetCalendar();
    C.mode = 'edit'; C.bookingId = b.id; C.in = b.checkIn; C.out = b.checkOut; C.note = b.note || '';
    goToMonth(b.checkIn);
    renderCalendar();
    setTab('calendar');
  }

  function renderMode() {
    const el = $('#adMode');
    el.className = 'ad-mode' + (C.mode !== 'block' ? ' is-' + C.mode : '');
    let text;
    if (C.mode === 'request') {
      const r = requestOf(C.requestId);
      text = r ? 'Заявка № ' + r.number + ': ' + fullName(r) + ', ' + fmtPhone(r.phone) + '. Гость просил ' + D.ru(r.checkIn) + ' — ' + D.ru(r.checkOut) + '. Проверьте даты после разговора, при необходимости поправьте и подтвердите.' : 'Подтверждение заявки.';
    } else if (C.mode === 'edit') {
      const b = bookingOf(C.bookingId);
      text = b ? 'Изменение: ' + bookingLabel(b) + ', сейчас ' + D.ru(b.checkIn) + ' — ' + D.ru(b.checkOut) + '. Выберите новые даты.' : 'Изменение брони.';
    } else {
      text = 'Ручная блокировка. Выберите даты, которые нужно показать занятыми на сайте, и подтвердите.';
    }
    setChildren(el, h('p', null, text),
      C.mode !== 'block' ? h('button', { type: 'button', class: 'ad-btn ad-btn-sm', onclick: () => { resetCalendar(); renderCalendar(); } }, 'Выйти из этого режима') : null);
  }

  function buildMonth(y, m, owners) {
    const first = D.iso(y, m, 1), lead = D.dow(first), dim = D.dim(y, m);
    const editing = new Set();
    const eb = C.bookingId ? bookingOf(C.bookingId) : null;
    if (eb) for (let d = eb.checkIn; d < eb.checkOut; d = D.add(d, 1)) editing.add(d);

    const grid = h('div', { class: 'ad-grid', role: 'group', 'aria-label': MONTHS[m - 1] + ' ' + y });
    for (let i = 0; i < lead; i++) grid.append(h('span', { class: 'ad-pad' }));
    for (let d = 1; d <= dim; d++) {
      const iso = D.iso(y, m, d);
      const own = owners.get(iso), prev = owners.get(D.add(iso, -1));
      const cls = ['ad-day'];
      if (iso < S.today) cls.push('is-past');
      if (iso === S.today) cls.push('is-today');
      const src = own || prev;
      if (src) cls.push(src.source === 'request' ? 'src-req' : 'src-man');
      if (own) cls.push('busy');
      if (own && !prev) cls.push('edge-start');
      if (!own && prev) cls.push('edge-end');
      if (editing.has(iso)) cls.push('is-editing');
      const inSel = C.in && C.out && iso >= C.in && iso < C.out;
      if (inSel) cls.push('sel');
      if (iso === C.in) cls.push('sel-start');
      if (iso === C.out) cls.push('sel-end');
      if (inSel && own) cls.push('sel-conflict');
      const state = own ? 'занято' : (prev ? 'свободно, день выезда гостей' : 'свободно');
      grid.append(h('button', {
        type: 'button', class: cls.join(' '), dataset: { date: iso },
        'aria-label': D.long(iso) + ', ' + state, 'aria-pressed': (iso === C.in || iso === C.out) ? 'true' : 'false',
      }, String(d)));
    }
    return h('div', { class: 'ad-month' }, h('h3', null, MONTHS[m - 1] + ' ' + y),
      h('div', { class: 'ad-wd', 'aria-hidden': 'true' }, WEEKDAYS.map(w => h('span', null, w))), grid);
  }

  function monthShift(y, m, k) { const i = y * 12 + (m - 1) + k; return { y: Math.floor(i / 12), m: (i % 12) + 1 }; }

  function renderCalendar() {
    if (!S.loaded) return;
    renderMode();
    const owners = nightOwners();
    const second = monthShift(C.y, C.m, 1);
    $('#adMonths').replaceChildren(buildMonth(C.y, C.m, owners), buildMonth(second.y, second.m, owners));
    const two = window.matchMedia('(min-width: 900px)').matches;   // на телефоне виден один месяц
    $('#adCalTitle').textContent = two
      ? MONTHS[C.m - 1] + ' ' + C.y + ' — ' + MONTHS[second.m - 1] + ' ' + second.y
      : MONTHS[C.m - 1] + ' ' + C.y;

    const t = D.parse(S.today), idx = C.y * 12 + C.m - 1, tIdx = t.y * 12 + t.m - 1;
    $('#adPrev').disabled = idx <= tIdx - 12;
    $('#adNext').disabled = idx >= tIdx + 36;

    $('#adIn').value = C.in || '';
    $('#adOut').value = C.out || '';
    const note = $('#adNote');
    if (note.value !== C.note) note.value = C.note;
    renderSelection();
  }

  function renderSelection() {
    const box = $('#adSelInfo');
    const kids = [];
    let canSave = false;
    if (!C.in) {
      kids.push(h('p', { class: 'ad-dim' }, 'Нажмите на дату заезда в календаре или выберите её в поле «Заезд».'));
    } else if (!C.out) {
      kids.push(h('p', null, 'Заезд: ' + D.long(C.in) + '. Теперь выберите дату выезда.'));
    } else {
      const n = D.diff(C.in, C.out);
      kids.push(h('p', null, h('b', null, D.long(C.in) + ' — ' + D.long(C.out)), ': ' + n + ' ' + nightsWord(n) + '.'));
      kids.push(h('p', { class: 'ad-dim' }, 'Будут заняты ночи с ' + D.long(C.in) + ' по ' + D.long(D.add(C.out, -1)) + ' включительно. День выезда, ' + D.long(C.out) + ', останется свободным: в этот день может заехать другой гость.'));
      const conflicts = conflictsOf(C.in, C.out);
      if (conflicts.length) {
        kids.push(h('div', { class: 'ad-sel-err' }, 'Эти даты уже заняты. Сохранить нельзя — измените период или сначала снимите существующую бронь:',
          h('ul', null, conflicts.map(c => h('li', null, D.ru(c.checkIn) + ' — ' + D.ru(c.checkOut) + ' (' + bookingLabel(c) + ')')))));
      } else {
        canSave = true;
      }
      if (C.in < S.today) kids.push(h('div', { class: 'ad-sel-warn' }, 'Период начинается в прошлом. Убедитесь, что это нужно.'));
    }
    if (C.error) kids.push(C.error);
    box.replaceChildren(...kids);

    const label = C.mode === 'request' ? 'Подтвердить бронирование' : (C.mode === 'edit' ? 'Сохранить изменения' : 'Заблокировать даты');
    const btn = $('#adSave');
    btn.textContent = C.busy ? 'Сохраняем…' : label;
    btn.disabled = !canSave || C.busy;
  }

  function pickDate(iso) {
    C.error = null;
    if (!C.in || (C.in && C.out)) { C.in = iso; C.out = null; }
    else if (iso > C.in) { C.out = iso; }
    else if (iso === C.in) { toast('Дата выезда должна быть позже даты заезда: нужна хотя бы одна ночь.', ''); return; }
    else { C.in = iso; C.out = null; }
    renderCalendar();
  }

  function onDateInput() {
    C.error = null;
    const a = $('#adIn').value, b = $('#adOut').value;
    C.in = D.parse(a) ? a : null;
    C.out = (D.parse(b) && C.in && b > C.in) ? b : null;
    if (C.in) goToMonth(C.in);
    renderCalendar();
  }

  async function saveSelection() {
    if (C.busy || !C.in || !C.out) return;
    C.busy = true; C.error = null;
    renderSelection();
    const body = { checkIn: C.in, checkOut: C.out, note: C.note };
    try {
      if (C.mode === 'request') {
        await api('confirmRequest', { method: 'POST', body: Object.assign({ id: C.requestId }, body) });
        toast('Даты заняты, заявка подтверждена. Календарь на сайте обновлён.', 'ok');
      } else if (C.mode === 'edit') {
        await api('updateBooking', { method: 'POST', body: Object.assign({ id: C.bookingId }, body) });
        toast('Изменения сохранены.', 'ok');
      } else {
        await api('createBlock', { method: 'POST', body });
        toast('Даты заблокированы. Календарь на сайте обновлён.', 'ok');
      }
      resetCalendar();
      C.busy = false;
      await load(true);
    } catch (e) {
      C.busy = false;
      if (e.code === 'dates_conflict') {
        await load(true);   // показываем актуальную занятость
        const list = (e.extra.conflicts || []).map(c => h('li', null, D.ru(c.checkIn) + ' — ' + D.ru(c.checkOut) + (c.requestNumber ? ' (заявка № ' + c.requestNumber + ')' : '')));
        C.error = h('div', { class: 'ad-sel-err' }, 'Пока вы выбирали даты, они стали недоступны. Бронь не создана.', h('ul', null, list));
      } else {
        C.error = h('div', { class: 'ad-sel-err' }, e.message);
      }
      renderCalendar();
    }
  }

  /* ================= Брони и блокировки ================= */

  async function cancelBookingFlow(b, request) {
    const r = request || (b.requestId ? requestOf(b.requestId) : null);
    const n = D.diff(b.checkIn, b.checkOut);
    const ok = await confirmBox({
      title: r ? 'Отменить бронирование?' : 'Снять блокировку?',
      text: D.ru(b.checkIn) + ' — ' + D.ru(b.checkOut) + ' (' + n + ' ' + nightsWord(n) + ') снова станут свободными в календаре для всех посетителей сайта.'
        + (r ? ' Заявка № ' + r.number + ' получит статус «Отменена».' : ''),
      ok: r ? 'Отменить бронирование' : 'Снять блокировку', danger: true,
    });
    if (!ok) return;
    try {
      await api('cancelBooking', { method: 'POST', body: { id: b.id } });
      toast('Даты освобождены.', 'ok');
      if ($('#adDetails').open) $('#adDetails').close();
      if (C.bookingId === b.id) resetCalendar();
      await load(true);
    } catch (e) { toast(e.message, 'error'); }
  }

  function bookingRow(b, withActions) {
    const n = D.diff(b.checkIn, b.checkOut);
    return h('article', { class: 'ad-card ad-bk' },
      h('div', { class: 'ad-bk-top' },
        h('span', { class: 'ad-bk-dates' }, D.ru(b.checkIn) + ' — ' + D.ru(b.checkOut)),
        h('span', { class: 'ad-dim' }, n + ' ' + nightsWord(n)),
        h('span', { class: 'ad-pill ' + (b.requestId ? 'src-request' : 'src-manual') }, bookingLabel(b))),
      b.note ? h('div', { class: 'ad-bk-note' }, 'Заметка: ' + b.note) : null,
      b.status === 'cancelled' && b.cancelledAt ? h('div', { class: 'ad-bk-note' }, 'Отменена ' + fmtDT(b.cancelledAt)) : null,
      withActions ? h('div', { class: 'ad-actions' },
        h('button', { type: 'button', class: 'ad-btn ad-btn-sm', onclick: () => startEdit(b) }, 'Изменить даты или заметку'),
        h('button', { type: 'button', class: 'ad-btn ad-btn-sm ad-btn-danger', onclick: () => cancelBookingFlow(b) }, b.requestId ? 'Отменить бронирование' : 'Снять блокировку')) : null);
  }

  function renderBookings() {
    const box = $('#adBookingList');
    const active = S.bookings.filter(b => b.status === 'active');
    const upcoming = active.filter(b => b.checkOut > S.today).sort((a, b) => a.checkIn.localeCompare(b.checkIn));
    const past = active.filter(b => b.checkOut <= S.today).sort((a, b) => b.checkIn.localeCompare(a.checkIn));
    const cancelled = S.bookings.filter(b => b.status === 'cancelled').sort((a, b) => String(b.cancelledAt).localeCompare(String(a.cancelledAt)));

    setChildren(box,
      h('h2', { class: 'ad-section-title' }, 'Действующие и будущие'),
      upcoming.length ? h('div', { class: 'ad-list' }, upcoming.map(b => bookingRow(b, true)))
        : h('div', { class: 'ad-empty' }, 'Занятых дат нет. Добавьте блокировку на вкладке «Календарь».'),
      past.length ? h('details', { class: 'ad-details' }, h('summary', null, 'Прошедшие (' + past.length + ')'), h('div', { class: 'ad-list' }, past.map(b => bookingRow(b, false)))) : null,
      cancelled.length ? h('details', { class: 'ad-details' }, h('summary', null, 'Отменённые и снятые (' + cancelled.length + ')'), h('div', { class: 'ad-list' }, cancelled.map(b => bookingRow(b, false)))) : null);
  }

  /* ================= Служебное ================= */

  function check(level, text) { return h('li', null, h('span', { class: 'ad-dot ' + level }), h('span', null, text)); }

  function renderService() {
    const hl = S.health;
    if (!hl) return;
    const kb = Math.max(1, Math.round(hl.file.size / 1024));
    const checks = [
      check('ok', 'PHP ' + hl.php),
      check(hl.curl ? 'ok' : (hl.allowUrlFopen ? 'warn' : 'bad'), hl.curl ? 'cURL доступен: уведомления в Telegram отправляются через него.' : (hl.allowUrlFopen ? 'cURL нет, используется запасной способ (allow_url_fopen).' : 'Нет ни cURL, ни allow_url_fopen: отправка в Telegram невозможна.')),
      check(hl.telegramConfigured ? 'ok' : 'bad', hl.telegramConfigured ? 'Telegram настроен (токен и Chat ID заданы).' : 'Telegram не настроен: заполните telegram_bot_token и telegram_chat_id в config.php.'),
      check(hl.failedNotifications ? 'warn' : 'ok', hl.failedNotifications ? 'Не доставлено уведомлений: ' + hl.failedNotifications + '. Повторите отправку в карточке заявки.' : 'Недоставленных уведомлений нет.'),
      check(hl.file.writable ? 'ok' : 'bad', hl.file.writable ? 'Папка данных доступна для записи.' : 'Папка данных недоступна для записи.'),
      check(hl.file.exists ? 'ok' : 'warn', hl.file.exists ? 'Файл данных: ' + kb + ' КБ.' : 'Файл данных появится после первой заявки или блокировки.'),
      check(hl.backups.count ? 'ok' : 'warn', hl.backups.count ? 'Резервных копий: ' + hl.backups.count + ', последняя ' + fmtDT(hl.backups.latest) + '.' : 'Резервных копий пока нет.'),
      check(S.exposed ? 'bad' : 'ok', S.exposed ? 'Файл данных доступен из интернета — срочно закройте доступ.' : 'Файл данных из интернета не открывается.'),
    ];
    $('#adService').replaceChildren(
      h('div', { class: 'ad-card' }, h('h3', null, 'Состояние сервиса'), h('ul', { class: 'ad-checks' }, checks)),
      h('div', { class: 'ad-card ad-service' },
        h('h3', null, 'Действия'),
        h('div', { class: 'ad-actions' },
          h('button', { type: 'button', class: 'ad-btn', onclick: e => withBusy(e.currentTarget, async () => {
            try {
              const d = await api('testTelegram', { method: 'POST', body: {} });
              toast(d.sent ? 'Тестовое сообщение отправлено в Telegram.' : 'Не удалось отправить: ' + d.error, d.sent ? 'ok' : 'error');
            } catch (err) { toast(err.message, 'error'); }
          }) }, 'Проверить Telegram'),
          h('button', { type: 'button', class: 'ad-btn', onclick: e => withBusy(e.currentTarget, async () => {
            try { await api('createBackup', { method: 'POST', body: {} }); toast('Резервная копия создана.', 'ok'); await load(true); }
            catch (err) { toast(err.message, 'error'); }
          }) }, 'Создать резервную копию')),
        h('p', { class: 'ad-help' }, 'Копии делаются автоматически перед каждым вашим изменением и не чаще раза в час при новых заявках. Старые копии удаляются, остаются самые свежие.'),
        h('p', { class: 'ad-help' }, 'Если файл данных повредится, сайт покажет ошибку и ничего не сотрёт. Восстановление вручную: скачайте нужную копию из папки data/backups/ по FTP и загрузите её в data/ под именем booking-data.json.')));
  }

  async function checkExposure() {
    try {
      const res = await fetch('data/booking-data.json', { cache: 'no-store' });
      if (!res.ok) return;
      const j = JSON.parse(await res.text());
      if (j && Array.isArray(j.requests)) { S.exposed = true; renderBanners(); renderService(); }
    } catch (e) { /* закрыт или не JSON — это хорошо */ }
  }

  /* ================= Запуск ================= */

  function init() {
    $$('.ad-tabs [data-tab]').forEach(b => b.addEventListener('click', () => setTab(b.dataset.tab)));
    $('#adRefresh').addEventListener('click', e => withBusy(e.currentTarget, () => load(false)));
    $('#adPrev').addEventListener('click', () => { const s = monthShift(C.y, C.m, -1); C.y = s.y; C.m = s.m; renderCalendar(); });
    $('#adNext').addEventListener('click', () => { const s = monthShift(C.y, C.m, 1); C.y = s.y; C.m = s.m; renderCalendar(); });
    $('#adMonths').addEventListener('click', e => { const b = e.target.closest('.ad-day'); if (b) pickDate(b.dataset.date); });
    $('#adIn').addEventListener('change', onDateInput);
    $('#adOut').addEventListener('change', onDateInput);
    $('#adNote').addEventListener('input', e => { C.note = e.target.value; });
    $('#adSave').addEventListener('click', saveSelection);
    $('#adReset').addEventListener('click', () => { resetCalendar(); renderCalendar(); });
    // Клик по затемнению закрывает диалог
    $$('dialog').forEach(d => d.addEventListener('click', e => { if (e.target === d) d.close(); }));

    load(false).then(checkExposure);
    setInterval(() => { if (!document.hidden && !$('#adDetails').open && !C.busy) load(true); }, 60000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) load(true); });
  }

  // Точка расширения для модулей вкладок (admin-alerts.js, admin-reviews.js, admin-media.js, admin-stats.js)
  window.KDAdmin = {
    $, $$, h, api, toast, confirmBox, withBusy, setChildren, D, fmtDT, nightsWord, S, KEY, ApiErr,
    setTab, load,
    register(mod) {
      modules.push(mod);
      if (mod.init) mod.init();
      if (S.loaded && mod.render) mod.render();
    },
  };

  init();
})();
</script>
<script nonce="<?= $nonce ?>" src="admin-alerts.js" defer></script>
<script nonce="<?= $nonce ?>" src="admin-reviews.js" defer></script>
<script nonce="<?= $nonce ?>" src="admin-media.js" defer></script>
<script nonce="<?= $nonce ?>" src="admin-stats.js" defer></script>
</body>
</html>
