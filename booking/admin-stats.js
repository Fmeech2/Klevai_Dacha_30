/* =====================================================================
   КЛЁВАЯ ДАЧА — вкладка «Статистика»: посещения, время заходов, источники, посетители.
   Подключается из admin.php после основного скрипта (через window.KDAdmin).

   Все данные приходят одним запросом admin-api.php?action=stats за выбранный период,
   поэтому фильтр по датам применяется ко всем блокам сразу. Графики нарисованы на чистом SVG,
   без библиотек. Время и дни показываются в поясе, который сервер указал в ответе
   (константа KD_STATS_TZ в booking/stats.php).
   ===================================================================== */
(() => {
  'use strict';
  const A = window.KDAdmin;
  if (!A) return;
  const { h, $, api, toast, setChildren, D } = A;

  const SVG_NS = 'http://www.w3.org/2000/svg';
  const MONTHS = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
  const WEEKDAYS = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
  const WD_SHORT = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
  const TYPES = [
    ['ad', 'Реклама (по UTM)', '#E8913A'],
    ['search', 'Поисковик', '#2F80ED'],
    ['social', 'Соцсеть', '#56CCF2'],
    ['other', 'Другой сайт', '#8b93a1'],
    ['direct', 'Прямой / неизвестный', '#B9C4D6'],
  ];
  const TYPE_LABEL = Object.fromEntries(TYPES.map(t => [t[0], t[1]]));
  const BUTTONS = {
    phone: 'Телефон', booking: 'Бронирование', booking_hero: 'Бронирование (первый экран)', booking_bottom: 'Бронирование (низ страницы)',
    whatsapp: 'WhatsApp', telegram: 'Telegram', max: 'MAX', vk: 'ВКонтакте', review_submit: 'Отправка отзыва',
  };
  const btnLabel = k => BUTTONS[k] || k;
  const PAGE = 100;

  const T = { data: null, from: '', to: '', rows: [], loading: false, error: '' };

  /* ---------- вспомогательное ---------- */

  const fmt = n => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '\u00A0');
  const pct = (n, total) => total > 0 ? (n * 100 / total).toFixed(n * 100 / total >= 10 ? 0 : 1).replace('.', ',') + '%' : '0%';
  const monthName = key => { const [y, m] = key.split('-'); return MONTHS[+m - 1] + ' ' + y; };
  const typeLabel = t => TYPE_LABEL[t] || t;

  function svg(tag, attrs, ...kids) {
    const el = document.createElementNS(SVG_NS, tag);
    for (const k of Object.keys(attrs || {})) el.setAttribute(k, String(attrs[k]));
    kids.flat().forEach(c => { if (c != null) el.append(c); });
    return el;
  }

  /**
   * Столбчатая диаграмма. items: [{label, value}]. Пиковый столбец выделяется цветом и подписью.
   * labelEvery — подписывать каждый n-й столбец (для 24 часов).
   */
  function barChart(items, { title, labelEvery = 1, height = 190 }) {
    const W = 640, padL = 8, padR = 8, padT = 22, padB = 28;
    const max = Math.max(1, ...items.map(i => i.value));
    const peak = items.some(i => i.value > 0) ? items.reduce((p, i, idx) => (i.value > items[p].value ? idx : p), 0) : -1;
    const slot = (W - padL - padR) / items.length;
    const bw = Math.min(40, slot * 0.7);
    const base = height - padB;
    const g = svg('g');
    g.append(svg('line', { x1: padL, y1: base, x2: W - padR, y2: base, class: 'st-axis' }));
    items.forEach((it, i) => {
      const bh = it.value > 0 ? Math.max(2, (base - padT) * it.value / max) : 0;
      const x = padL + slot * i + (slot - bw) / 2;
      g.append(svg('rect', { x, y: base - bh, width: bw, height: bh, rx: 3, class: 'st-bar' + (i === peak ? ' is-peak' : '') },
        svg('title', {}, it.label + ': ' + it.value)));
      if (i % labelEvery === 0) g.append(svg('text', { x: x + bw / 2, y: height - 9, 'text-anchor': 'middle', class: 'st-lab' }, it.short || it.label));
      if (i === peak) g.append(svg('text', { x: x + bw / 2, y: base - bh - 6, 'text-anchor': 'middle', class: 'st-peak' }, String(it.value)));
    });
    return svg('svg', { viewBox: '0 0 ' + W + ' ' + height, class: 'st-chart', role: 'img', 'aria-label': title }, g);
  }

  /** Таблица «значение, визитов, процент»; пиковая строка выделена. */
  function valueTable(items, head) {
    const total = items.reduce((s, i) => s + i.value, 0);
    const peak = items.some(i => i.value > 0) ? items.reduce((p, i, idx) => (i.value > items[p].value ? idx : p), 0) : -1;
    return h('div', { class: 'st-table-wrap' }, h('table', { class: 'st-table' },
      h('thead', null, h('tr', null, h('th', null, head), h('th', { class: 'num' }, 'Визитов'), h('th', { class: 'num' }, 'Доля'))),
      h('tbody', null, items.map((i, idx) => h('tr', { class: idx === peak ? 'is-peak' : '' },
        h('td', null, i.label, idx === peak ? h('span', { class: 'st-badge' }, 'пик') : null),
        h('td', { class: 'num' }, fmt(i.value)), h('td', { class: 'num' }, pct(i.value, total)))))));
  }

  function block(title, note, ...kids) {
    return h('section', { class: 'ad-card st-block' }, h('h3', null, title), note ? h('p', { class: 'ad-help' }, note) : null, kids);
  }

  /* ---------- блоки ---------- */

  function summaryBlock(d) {
    const s = d.summary;
    const card = (label, value, sub) => h('div', { class: 'st-card' }, h('div', { class: 'st-card-v' }, value), h('div', { class: 'st-card-l' }, label), sub ? h('div', { class: 'st-card-s ad-dim' }, sub) : null);
    return h('div', { class: 'st-cards' },
      card('Уникальных устройств', fmt(s.unique), 'за выбранный период'),
      card('Визитов', fmt(s.visits), s.unique ? 'в среднем ' + (s.visits / s.unique).toFixed(1).replace('.', ',') + ' на устройство' : ''),
      card('Повторные посетители', String(s.repeatShare).replace('.', ',') + '%', fmt(s.repeat) + ' из ' + fmt(s.unique) + ' устройств'),
      card('Кликов по кнопкам', fmt(s.clicks), 'телефон, бронь, мессенджеры'));
  }

  function timeBlocks(d) {
    const hours = d.time.hours.map((v, i) => ({ label: String(i).padStart(2, '0') + ':00–' + String(i).padStart(2, '0') + ':59', short: String(i).padStart(2, '0'), value: v }));
    const wd = d.time.weekdays.map((v, i) => ({ label: WEEKDAYS[i], short: WD_SHORT[i], value: v }));
    const longSpan = d.time.months.length > 12;       // больше года: к названию месяца добавляем год
    const months = d.time.months.map(m => ({ label: monthName(m.key), short: MONTHS[+m.key.slice(5) - 1].slice(0, 3) + (longSpan ? ' ' + m.key.slice(2, 4) : ''), value: m.n }));
    const tz = d.range.tz;
    const grid = (title, note, items, head, opts) => block(title, note, barChart(items, Object.assign({ title }, opts)), valueTable(items, head));
    return [
      grid('Время заходов: по часам суток', 'Час начала визита, ' + tz + '. Новый визит считается, если с последней активности прошло больше 30 минут.', hours, 'Час', { labelEvery: 2 }),
      grid('Время заходов: по дням недели', 'День недели, ' + tz + '.', wd, 'День недели'),
      grid('Время заходов: по месяцам', 'Месяц, ' + tz + '.', months, 'Месяц', { labelEvery: Math.ceil(months.length / 14) }),
    ];
  }

  /** Реклама против остальных: полоса из двух частей и разбивка по типам источников. */
  function sourcesChart(types) {
    const total = Object.values(types).reduce((s, n) => s + n, 0);
    const ad = types.ad || 0, rest = total - ad;
    const W = 640, H = 34;
    const wAd = total ? W * ad / total : 0;
    const versus = svg('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'st-versus', role: 'img', 'aria-label': 'Реклама ' + pct(ad, total) + ', остальные источники ' + pct(rest, total) },
      svg('rect', { x: 0, y: 0, width: W, height: H, rx: 8, fill: '#E3E6EB' }),
      wAd > 0 ? svg('rect', { x: 0, y: 0, width: Math.max(wAd, 6), height: H, rx: 8, fill: '#E8913A' }) : null);
    const bars = h('div', { class: 'st-types' }, TYPES.map(([key, label, color]) => {
      const n = types[key] || 0;
      const row = svg('svg', { viewBox: '0 0 300 14', class: 'st-tbar', 'aria-hidden': 'true' },
        svg('rect', { x: 0, y: 0, width: 300, height: 14, rx: 7, fill: '#EEF0F3' }),
        n > 0 ? svg('rect', { x: 0, y: 0, width: Math.max(300 * n / (total || 1), 7), height: 14, rx: 7, fill: color }) : null);
      return h('div', { class: 'st-type-row' }, h('span', { class: 'st-type-name' }, h('i', { class: 'st-dot', 'data-c': key }), label), row, h('span', { class: 'st-type-n' }, fmt(n) + ' · ' + pct(n, total)));
    }));
    return h('div', null,
      h('div', { class: 'st-versus-head' },
        h('span', null, h('i', { class: 'st-dot', 'data-c': 'ad' }), 'Реклама: ', h('b', null, fmt(ad)), ' (' + pct(ad, total) + ')'),
        h('span', null, h('i', { class: 'st-dot', 'data-c': 'rest' }), 'Все остальные источники: ', h('b', null, fmt(rest)), ' (' + pct(rest, total) + ')')),
      versus, bars);
  }

  function dataTable(head, rows, empty) {
    if (!rows.length) return h('div', { class: 'ad-empty' }, empty);
    return h('div', { class: 'st-table-wrap' }, h('table', { class: 'st-table' },
      h('thead', null, h('tr', null, head.map(([t, num]) => h('th', { class: num ? 'num' : '' }, t)))),
      h('tbody', null, rows.map(r => h('tr', null, r.map((c, i) => h('td', { class: head[i][1] ? 'num' : (head[i][2] || '') , title: typeof c === 'string' && c.length > 40 ? c : null }, c)))))));
  }

  function linksBlocks(d) {
    const L = d.links;
    const totalTypes = Object.values(L.types).reduce((s, n) => s + n, 0);
    return [
      block('Источники трафика: реклама против остальных', 'Реклама определяется по UTM-меткам в ссылке (utm_source, utm_medium, utm_campaign). Пустой referer и переходы с самого сайта считаются «прямой / неизвестный».', sourcesChart(L.types)),
      block('Самые частые переходы', 'Откуда приходят посетители: домен и полный адрес страницы-источника. Браузеры часто передают только домен, поэтому полный адрес бывает не всегда.',
        h('h4', { class: 'st-sub' }, 'По доменам'),
        dataTable([['Домен'], ['Тип источника'], ['Визитов', true], ['Доля', true]], L.domains.map(r => [r.domain, typeLabel(r.type), fmt(r.n), pct(r.n, totalTypes)]), 'Переходов с других сайтов за период не было.'),
        h('h4', { class: 'st-sub' }, 'По полному адресу'),
        dataTable([['Адрес страницы-источника', false, 'st-url'], ['Тип источника'], ['Визитов', true]], L.urls.map(r => [r.url, typeLabel(r.type), fmt(r.n)]), 'Нет данных о полных адресах.')),
      block('UTM-кампании', 'Метки добавляются в адрес сайта, например: ?utm_source=yandex&utm_medium=cpc&utm_campaign=leto.',
        dataTable([['utm_source'], ['utm_medium'], ['utm_campaign'], ['Визитов', true], ['Доля', true]], L.utm.map(r => [r.source || '—', r.medium || '—', r.campaign || '—', fmt(r.n), pct(r.n, L.types.ad || 0)]), 'Визитов с UTM-метками за период не было.')),
    ];
  }

  function clickChips(clicks) {
    const keys = Object.keys(clicks || {});
    if (!keys.length) return h('span', { class: 'ad-dim' }, '—');
    return h('span', { class: 'st-chips' }, keys.map(k => h('span', { class: 'st-chip' }, btnLabel(k) + ' ×' + clicks[k])));
  }

  function visitorsBlock(d) {
    const rows = T.rows;
    const src = s => typeLabel(s.type) + (s.domain ? ': ' + s.domain : '') + (s.campaign ? ' (' + s.campaign + ')' : '');
    const clicksAll = Object.entries(d.clicks || {});
    const more = rows.length < d.visitors.total;
    return block('Посетители', 'Одно устройство (браузер) — один посетитель. Показаны устройства, заходившие в выбранный период. Счётчики кликов ведутся за всё время и в период не разбиваются.',
      clicksAll.length ? h('div', { class: 'st-clicks' }, h('b', null, 'Клики по кнопкам за период: '), h('span', { class: 'st-chips' }, clicksAll.map(([k, n]) => h('span', { class: 'st-chip' }, btnLabel(k) + ' ×' + fmt(n))))) : null,
      rows.length ? h('div', { class: 'st-table-wrap' }, h('table', { class: 'st-table st-visitors' },
        h('thead', null, h('tr', null, ['Устройство', 'Заходов', 'Первый заход', 'Последний заход', 'Первый источник', 'Клики'].map((t, i) => h('th', { class: i === 1 ? 'num' : '' }, t)))),
        h('tbody', null, rows.map(r => h('tr', null,
          h('td', null, h('code', { title: r.id }, r.id.slice(0, 8)), h('div', { class: 'ad-dim st-dev' }, (r.device.type === 'mobile' ? 'телефон' : 'компьютер') + (r.device.screen ? ', ' + r.device.screen + ' px' : ''))),
          h('td', { class: 'num' }, fmt(r.visits), r.visitsInRange !== r.visits ? h('div', { class: 'ad-dim st-dev' }, 'за период ' + r.visitsInRange) : null),
          h('td', null, r.firstSeen), h('td', null, r.lastSeen),
          h('td', null, src(r.firstSource)),
          h('td', null, clickChips(r.clicks))))))) : h('div', { class: 'ad-empty' }, 'За выбранный период посетителей не было.'),
      d.visitors.total ? h('div', { class: 'st-more' }, h('span', { class: 'ad-dim' }, 'Показано ' + rows.length + ' из ' + fmt(d.visitors.total)),
        more ? h('button', { type: 'button', class: 'ad-btn ad-btn-sm', onclick: e => A.withBusy(e.currentTarget, loadMore) }, 'Показать ещё') : null) : null);
  }

  /* ---------- фильтр ---------- */

  function filterBar(today) {
    const from = h('input', { type: 'date', class: 'ad-input', 'aria-label': 'С даты', value: T.from, max: today || '' });
    const to = h('input', { type: 'date', class: 'ad-input', 'aria-label': 'По дату', value: T.to, max: today || '' });
    const apply = () => {
      if (!D.parse(from.value) || !D.parse(to.value)) { toast('Выберите обе даты.', 'error'); return; }
      T.from = from.value; T.to = to.value;
      reload();
    };
    const preset = (days) => () => {
      const t = today || D.iso(...Object.values(D.parse(T.to)));
      T.to = t;
      T.from = days ? D.add(t, -(days - 1)) : '2020-01-01';
      reload();
    };
    return h('div', { class: 'ad-card st-filter' },
      h('div', { class: 'st-range' },
        h('label', { class: 'ad-field' }, 'С', from), h('label', { class: 'ad-field' }, 'По', to),
        h('button', { type: 'button', class: 'ad-btn ad-btn-primary', onclick: apply }, 'Показать')),
      h('div', { class: 'ad-filters st-presets' },
        [['7 дней', 7], ['30 дней', 30], ['90 дней', 90], ['Год', 365], ['Всё время', 0]].map(([l, d]) => h('button', { type: 'button', class: 'ad-chip', onclick: preset(d) }, l))),
      h('p', { class: 'ad-help' }, 'Период применяется ко всем блокам ниже. Даты и время показаны по поясу ' + ((T.data && T.data.range.tz) || 'Europe/Moscow') + '.'));
  }

  /* ---------- загрузка и отрисовка ---------- */

  async function fetchStats(offset) {
    const q = { limit: String(PAGE), offset: String(offset) };
    if (T.from) q.from = T.from;
    if (T.to) q.to = T.to;
    return api('stats', { query: q });
  }

  async function reload() {
    T.loading = true;
    draw();
    try {
      const d = await fetchStats(0);
      T.data = d;
      T.from = d.range.from;
      T.to = d.range.to;
      T.rows = d.visitors.rows;
      T.error = '';
    } catch (e) { T.error = e.message; toast(e.message, 'error'); }
    T.loading = false;
    draw();
  }

  async function loadMore() {
    try {
      const d = await fetchStats(T.rows.length);
      T.rows = T.rows.concat(d.visitors.rows);
      draw();
    } catch (e) { toast(e.message, 'error'); }
  }

  function draw() {
    const root = $('#adStats');
    if (!root) return;
    const d = T.data;
    if (!d) {
      setChildren(root, filterBar(''), T.error ? h('div', { class: 'ad-banner bad' }, T.error) : h('div', { class: 'ad-empty' }, 'Загружаем статистику…'));
      return;
    }
    // Пока считается новый период, прежние данные остаются на месте (чуть притушены): страница не «прыгает»
    setChildren(root,
      filterBar(d.today),
      !T.loading && d.summary.visits === 0 ? h('div', { class: 'ad-banner' }, 'За выбранный период визитов нет. Если счётчик только что подключён, данные появятся после первых заходов на сайт.') : null,
      h('div', { class: 'st-body' + (T.loading ? ' is-loading' : ''), 'aria-busy': T.loading ? 'true' : 'false' }, summaryBlock(d), timeBlocks(d), linksBlocks(d), visitorsBlock(d)));
  }

  A.register({
    init() { draw(); },
    onTab(name) { if (name === 'stats' && !T.data && !T.loading) reload(); },
  });
})();
