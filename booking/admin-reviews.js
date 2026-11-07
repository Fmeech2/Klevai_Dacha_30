/* =====================================================================
   КЛЁВАЯ ДАЧА — вкладка «Отзывы»: модерация отзывов, оставленных на сайте.
   Подключается из admin.php после основного скрипта (через window.KDAdmin).

   Отзыв с сайта на странице не появляется, пока хозяин его не опубликует. Здесь отзыв можно:
   опубликовать, отредактировать перед публикацией (исходный текст посетителя сохраняется),
   отклонить, вернуть на проверку и удалить. На сайте у опубликованного отзыва показывается
   дата, когда его написал посетитель, а не дата публикации.
   ===================================================================== */
(() => {
  'use strict';
  const A = window.KDAdmin;
  if (!A) return;
  const { h, $, api, toast, confirmBox, withBusy, setChildren, fmtDT, D } = A;

  const STATUS = { pending: 'Ждёт публикации', published: 'Опубликован', rejected: 'Отклонён' };
  const FILTERS = [
    { id: 'pending', label: 'Ждут решения', test: r => r.status === 'pending' },
    { id: 'published', label: 'Опубликованные', test: r => r.status === 'published' },
    { id: 'rejected', label: 'Отклонённые', test: r => r.status === 'rejected' },
    { id: 'all', label: 'Все', test: () => true },
  ];

  const R = { list: [], loaded: false, filter: 'pending', editing: null, timer: 0 };

  const pill = st => h('span', { class: 'ad-pill rv-' + st }, STATUS[st] || st);

  function pendingCount() { return R.list.filter(r => r.status === 'pending').length; }

  function announce() {
    window.dispatchEvent(new CustomEvent('kd:reviews', { detail: { pending: pendingCount() } }));
  }

  async function refresh(silent) {
    try {
      const d = await api('reviews');
      R.list = d.reviews;
      R.loaded = true;
      announce();
      if (R.editing === null) draw();     // пока хозяин редактирует отзыв, список не перерисовываем
    } catch (e) {
      if (!silent) toast(e.message, 'error');
    }
  }

  function replace(review) {
    const i = R.list.findIndex(r => r.id === review.id);
    if (i >= 0) R.list[i] = review;
    announce();
  }

  async function act(action, body, okText) {
    try {
      const d = await api(action, { method: 'POST', body });
      if (d.review) replace(d.review);
      else if (action === 'reviewDelete') R.list = R.list.filter(r => r.id !== body.id);
      if (okText) toast(okText, 'ok');
      R.editing = null;
      announce();
      draw();
    } catch (e) { toast(e.message, 'error'); }
  }

  function card(r) {
    const editing = R.editing === r.id;
    const created = fmtDT(r.createdAt);
    const head = h('div', { class: 'ad-req-top' },
      h('span', { class: 'ad-req-num' }, '№ ' + r.number), pill(r.status),
      h('span', { class: 'ad-req-time' }, 'Написан ' + created));

    const who = h('div', { class: 'ad-req-meta' },
      'Автор: ' + (r.firstName + (r.lastName ? ' ' + r.lastName : '')),
      ', телефон: ', r.phone ? h('a', { href: 'tel:' + r.phone }, r.phone) : 'не указан');

    const nodes = [head, who];

    if (editing) {
      const name = h('input', { type: 'text', maxlength: '60', class: 'ad-input', 'aria-label': 'Имя на сайте' });
      name.value = r.displayName || '';
      const text = h('textarea', { rows: '6', maxlength: '2000', class: 'ad-input rv-text', 'aria-label': 'Текст отзыва' });
      text.value = r.text;
      const save = (publish) => async (e) => {
        await withBusy(e.currentTarget, async () => {
          try {
            await api('reviewUpdate', { method: 'POST', body: { id: r.id, text: text.value, displayName: name.value } });
            if (publish) await api('reviewSetStatus', { method: 'POST', body: { id: r.id, status: 'published' } });
            R.editing = null;
            toast(publish ? 'Отзыв изменён и опубликован.' : 'Изменения сохранены.', 'ok');
            await refresh(true);
            draw();
          } catch (err) { toast(err.message, 'error'); }
        });
      };
      nodes.push(
        h('label', { class: 'ad-field' }, 'Имя, которое увидят на сайте', name),
        h('label', { class: 'ad-field' }, 'Текст отзыва', text),
        h('div', { class: 'ad-actions' },
          h('button', { type: 'button', class: 'ad-btn ad-btn-primary ad-btn-sm', onclick: save(r.status !== 'published') }, r.status === 'published' ? 'Сохранить' : 'Сохранить и опубликовать'),
          r.status !== 'published' ? h('button', { type: 'button', class: 'ad-btn ad-btn-sm', onclick: save(false) }, 'Сохранить без публикации') : null,
          h('button', { type: 'button', class: 'ad-btn ad-btn-sm', onclick: () => { R.editing = null; draw(); } }, 'Отмена')));
    } else {
      nodes.push(
        h('div', { class: 'rv-name' }, r.displayName),
        h('p', { class: 'rv-body' }, r.text));
      if (r.editedAt) {
        nodes.push(h('details', { class: 'ad-details' }, h('summary', null, 'Изменён вами ' + fmtDT(r.editedAt) + '. Исходный текст посетителя'),
          h('p', { class: 'rv-body ad-dim' }, r.originalText || '')));
      }
      if (r.status === 'published') {
        nodes.push(h('div', { class: 'ad-req-meta' }, 'На сайте: имя «' + r.displayName + '», дата ' + created + ' (когда написал посетитель). Опубликован ' + fmtDT(r.publishedAt) + '.'));
      }
      if (r.notificationStatus === 'failed' || r.notificationStatus === 'not_configured') {
        nodes.push(h('div', { class: 'ad-req-warn' }, 'Уведомление в Telegram не было доставлено'));
      }

      const btn = (label, cls, fn) => h('button', { type: 'button', class: 'ad-btn ad-btn-sm ' + (cls || ''), onclick: e => withBusy(e.currentTarget, fn) }, label);
      const actions = [];
      if (r.status !== 'published') actions.push(btn('Опубликовать', 'ad-btn-primary', () => act('reviewSetStatus', { id: r.id, status: 'published' }, 'Отзыв опубликован на сайте.')));
      actions.push(btn('Редактировать', '', async () => { R.editing = r.id; draw(); }));
      if (r.status === 'pending') actions.push(btn('Отклонить', '', () => act('reviewSetStatus', { id: r.id, status: 'rejected' }, 'Отзыв отклонён.')));
      if (r.status === 'published') actions.push(btn('Снять с публикации', '', () => act('reviewSetStatus', { id: r.id, status: 'pending' }, 'Отзыв снят с сайта и ждёт решения.')));
      if (r.status === 'rejected') actions.push(btn('Вернуть на проверку', '', () => act('reviewSetStatus', { id: r.id, status: 'pending' })));
      actions.push(btn('Удалить', 'ad-btn-danger', async () => {
        const ok = await confirmBox({ title: 'Удалить отзыв?', text: 'Отзыв № ' + r.number + ' будет удалён безвозвратно (останется только в резервных копиях).', ok: 'Удалить', danger: true });
        if (ok) await act('reviewDelete', { id: r.id }, 'Отзыв удалён.');
      }));
      nodes.push(h('div', { class: 'ad-actions' }, actions));
    }
    return h('article', { class: 'ad-card rv-card' + (r.status === 'pending' ? ' is-new' : '') }, nodes);
  }

  function draw() {
    const box = $('#adReviews');
    if (!box) return;
    const f = FILTERS.find(x => x.id === R.filter) || FILTERS[0];
    const chips = h('div', { class: 'ad-filters' }, FILTERS.map(x => h('button', {
      type: 'button', class: 'ad-chip', 'aria-pressed': x.id === R.filter ? 'true' : 'false',
      onclick: () => { R.filter = x.id; R.editing = null; draw(); },
    }, x.label, h('b', null, String(R.list.filter(x.test).length)))));

    const items = R.list.filter(f.test);
    setChildren(box,
      h('p', { class: 'ad-help rv-intro' }, 'Отзывы с сайта появляются здесь сразу после отправки и попадают на страницу только после вашей публикации. Дату написания посетителя сайт показывает как есть. Отзывы с Авито подгружаются автоматически и здесь не отображаются.'),
      chips,
      items.length
        ? h('div', { class: 'ad-list rv-list' }, items.map(card))
        : h('div', { class: 'ad-empty' }, R.loaded ? (f.id === 'pending' ? 'Новых отзывов нет.' : 'Здесь пока пусто.') : 'Загружаем…'));
  }

  A.register({
    init() {
      refresh(true);
      R.timer = setInterval(() => {
        // Обновляем список в фоне, только когда вкладка открыта и вы ничего не редактируете
        const tab = $('#tab-reviews');
        if (tab && !tab.hidden && !document.hidden) refresh(true);
      }, 30000);
    },
    render() { if (R.editing === null) draw(); },
    onTab(name) { if (name === 'reviews') refresh(true); },
  });
})();
