/* =====================================================================
   КЛЁВАЯ ДАЧА — вкладка «Медиа»: фото и видео главной страницы.
   Подключается из admin.php после основного скрипта (через window.KDAdmin).

   Фото:   загрузка (по несколько штук сразу), категории («Фото», «Фото от посетителей» и свои),
           порядок показа, размер на странице (четверть или половина ширины), удаление.
   Видео:  загрузка кусками (обходит лимиты хостинга на размер файла), порядок показа, удаление.
   Порядок кнопками «влево/вправо»: так удобно и на телефоне.
   ===================================================================== */
(() => {
  'use strict';
  const A = window.KDAdmin;
  if (!A) return;
  const { h, $, api, toast, confirmBox, withBusy, setChildren, KEY } = A;

  const API = 'admin-api.php';
  const M = { loaded: false, categories: [], photos: [], videos: [], limits: {}, cat: '', size: 'quarter', uploads: [], busy: false };

  const mb = bytes => (bytes / 1048576).toFixed(bytes >= 10485760 ? 0 : 1) + ' МБ';
  const fileUrl = rel => '../' + rel;                 // админ-панель лежит в booking/, файлы — от корня сайта

  /* ---------- сеть: загрузка с индикатором прогресса ---------- */

  /** POST с прогрессом (fetch этого не умеет). body — FormData или Blob. Возвращает data из ответа сервера. */
  function xhrPost(url, body, onProgress) {
    return new Promise((resolve, reject) => {
      const x = new XMLHttpRequest();
      x.open('POST', url);
      x.setRequestHeader('X-Admin-Key', KEY);
      x.responseType = 'json';
      x.upload.onprogress = e => { if (e.lengthComputable && onProgress) onProgress(e.loaded / e.total); };
      x.onerror = () => reject(new A.ApiErr('network', 'Нет связи с сервером.'));
      x.ontimeout = () => reject(new A.ApiErr('network', 'Сервер не отвечает.'));
      x.onload = () => {
        const j = x.response;
        if (j && j.ok) resolve(j.data);
        else {
          const err = (j && j.error) || {};
          reject(new A.ApiErr(err.code || 'server', err.message || 'Ошибка сервера (HTTP ' + x.status + ').', err));
        }
      };
      x.timeout = 120000;
      x.send(body);
    });
  }

  async function load() {
    try {
      const d = await api('media');
      Object.assign(M, { categories: d.categories, photos: d.photos, videos: d.videos, limits: d.limits, loaded: true });
      if (!M.categories.some(c => c.id === M.cat)) M.cat = M.categories[0] ? M.categories[0].id : '';
      draw();
    } catch (e) { toast(e.message, 'error'); }
  }

  /* ---------- действия ---------- */

  async function call(action, body, okText) {
    try {
      await api(action, { method: 'POST', body });
      if (okText) toast(okText, 'ok');
      await load();
    } catch (e) { toast(e.message, 'error'); await load(); }
  }

  async function uploadPhotos(files) {
    if (!files.length || !M.cat) return;
    M.uploads = [];
    const cat = M.cat, size = M.size;
    for (const f of files) {
      const u = { name: f.name, p: 0, err: '' };
      M.uploads.push(u);
      drawUploads();
      try {
        if (!/^image\/(jpeg|png|webp)$/.test(f.type)) throw new Error('Подходят JPG, PNG и WebP.');
        if (f.size > M.limits.maxPhotoBytes) throw new Error('Больше ' + mb(M.limits.maxPhotoBytes) + '.');
        const fd = new FormData();
        fd.append('file', f);
        fd.append('category', cat);
        fd.append('size', size);
        await xhrPost(API + '?action=photoUpload', fd, p => { u.p = p; drawUploads(); });
        u.p = 1;
      } catch (e) { u.err = e.message; }
      drawUploads();
    }
    const failed = M.uploads.filter(u => u.err).length;
    toast(failed ? 'Часть фото не загружена, подробности под кнопкой загрузки.' : 'Фото загружены.', failed ? 'error' : 'ok');
    if (!failed) M.uploads = [];
    await load();
  }

  /** Кадр из начала видео для обложки (необязательно: если браузер не справился, обложки не будет). */
  function capturePoster(file) {
    return new Promise(resolve => {
      const v = document.createElement('video');
      const url = URL.createObjectURL(file);
      let finished = false;
      const done = blob => { if (finished) return; finished = true; URL.revokeObjectURL(url); resolve(blob); };
      v.muted = true;
      v.preload = 'metadata';
      v.playsInline = true;
      v.onloadeddata = () => { try { v.currentTime = Math.min(1, (v.duration || 2) / 2); } catch (e) { done(null); } };
      v.onseeked = () => {
        try {
          const s = Math.min(1, 960 / (v.videoWidth || 960));
          const c = document.createElement('canvas');
          c.width = Math.max(1, Math.round((v.videoWidth || 960) * s));
          c.height = Math.max(1, Math.round((v.videoHeight || 540) * s));
          c.getContext('2d').drawImage(v, 0, 0, c.width, c.height);
          c.toBlob(b => done(b), 'image/jpeg', 0.8);
        } catch (e) { done(null); }
      };
      v.onerror = () => done(null);
      setTimeout(() => done(null), 10000);
      v.src = url;
    });
  }

  const rand16 = () => Array.from(crypto.getRandomValues(new Uint8Array(8)), b => b.toString(16).padStart(2, '0')).join('');

  /** Видео кусками: сервер собирает файл из частей и проверяет, что это MP4 или WebM. */
  async function uploadVideo(file) {
    const u = { name: file.name, p: 0, err: '' };
    M.uploads.push(u);
    drawUploads();
    try {
      if (file.size > M.limits.maxVideoBytes) throw new Error('Видео больше ' + mb(M.limits.maxVideoBytes) + '.');
      if (!/\.(mp4|webm)$/i.test(file.name) && !/^video\/(mp4|webm)$/.test(file.type)) throw new Error('Подходят MP4 (H.264) и WebM.');
      const id = rand16(), chunk = M.limits.chunkBytes || 2097152;
      let offset = 0, video = null;
      while (offset < file.size) {
        const end = Math.min(file.size, offset + chunk);
        const blob = file.slice(offset, end);
        const q = new URLSearchParams({ action: 'videoChunk', uploadId: id, offset: String(offset), total: String(file.size), name: file.name });
        let attempt = 0;
        for (;;) {                                   // до трёх попыток на кусок при сбоях сети
          try {
            const res = await xhrPost(API + '?' + q.toString(), blob, p => { u.p = (offset + p * blob.size) / file.size; drawUploads(); });
            if (res.done) video = res.video;
            break;
          } catch (e) {
            if (e.code !== 'network' || ++attempt >= 3) throw e;
            await new Promise(r => setTimeout(r, 1200 * attempt));
          }
        }
        offset = end;
      }
      u.p = 1;
      if (video) {
        const poster = await capturePoster(file);
        if (poster) {
          const fd = new FormData();
          fd.append('id', video.id);
          fd.append('poster', poster, 'poster.jpg');
          try { await xhrPost(API + '?action=videoPoster', fd); } catch (e) { /* обложка необязательна */ }
        }
      }
    } catch (e) { u.err = e.message; }
    drawUploads();
  }

  async function uploadVideos(files) {
    M.uploads = [];
    for (const f of files) await uploadVideo(f);
    const failed = M.uploads.filter(u => u.err).length;
    toast(failed ? 'Видео не загружено, подробности под кнопкой загрузки.' : 'Видео загружено.', failed ? 'error' : 'ok');
    if (!failed) M.uploads = [];
    await load();
  }

  function moveItem(list, id, delta, action, extra) {
    const ids = list.map(x => x.id);
    const i = ids.indexOf(id), j = i + delta;
    if (i < 0 || j < 0 || j >= ids.length) return;
    [ids[i], ids[j]] = [ids[j], ids[i]];
    return call(action, Object.assign({ ids }, extra || {}));
  }

  /* ---------- отрисовка ---------- */

  function limitsBanner() {
    const l = M.limits, out = [];
    if (!l.gd) out.push(h('div', { class: 'ad-banner' }, 'На сервере нет расширения GD: фото сохраняются как есть, без сжатия и поворота. Очень крупные фото замедлят страницу. Лучше уменьшать их перед загрузкой.'));
    return out;
  }

  function categoriesBlock() {
    const input = h('input', { type: 'text', maxlength: '40', class: 'ad-input', placeholder: 'Название новой категории', 'aria-label': 'Название новой категории' });
    const add = async e => {
      const name = input.value.trim();
      if (!name) return;
      await withBusy(e.currentTarget, async () => { await call('categoryCreate', { name }, 'Категория добавлена.'); });
    };
    input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); add({ currentTarget: { disabled: false } }); } });

    return h('div', { class: 'ad-card md-block' },
      h('h3', null, 'Категории фото'),
      h('p', { class: 'ad-help' }, 'Категории превращаются во вкладки над галереей. Категория без фото на сайте не показывается. При удалении категории её фото переходят в первую из оставшихся.'),
      h('ul', { class: 'md-cats' }, M.categories.map(c => {
        const n = M.photos.filter(p => p.category === c.id).length;
        return h('li', { class: 'md-cat' },
          h('span', { class: 'md-cat-name' }, c.name, h('span', { class: 'ad-dim' }, ' (' + n + ')')),
          h('button', { type: 'button', class: 'ad-btn ad-btn-sm', onclick: async () => {
            const name = prompt('Новое название категории', c.name);
            if (name && name.trim() && name.trim() !== c.name) await call('categoryRename', { id: c.id, name: name.trim() }, 'Категория переименована.');
          } }, 'Переименовать'),
          h('button', { type: 'button', class: 'ad-btn ad-btn-sm ad-btn-danger', onclick: async () => {
            const ok = await confirmBox({ title: 'Удалить категорию «' + c.name + '»?', text: n ? 'Фото из неё (' + n + ') не удалятся, а перейдут в первую из оставшихся категорий.' : 'В категории нет фото.', ok: 'Удалить', danger: true });
            if (ok) await call('categoryDelete', { id: c.id }, 'Категория удалена.');
          } }, 'Удалить'));
      })),
      h('div', { class: 'md-add' }, input, h('button', { type: 'button', class: 'ad-btn ad-btn-primary', onclick: add }, 'Добавить')));
  }

  function photoCard(p, list, idx) {
    const catSel = h('select', { class: 'ad-input md-sel', 'aria-label': 'Категория фото' },
      M.categories.map(c => h('option', { value: c.id, selected: c.id === p.category }, c.name)));
    catSel.addEventListener('change', () => call('photoUpdate', { id: p.id, category: catSel.value }, 'Фото перенесено в другую категорию.'));
    const alt = h('input', { type: 'text', maxlength: '120', class: 'ad-input md-alt', placeholder: 'Подпись для поисковиков (необязательно)', 'aria-label': 'Описание фото' });
    alt.value = p.alt || '';
    alt.addEventListener('change', () => call('photoUpdate', { id: p.id, alt: alt.value }));
    const sizeBtn = (value, label) => h('button', {
      type: 'button', class: 'md-size' + (p.size === value ? ' is-on' : ''), 'aria-pressed': p.size === value ? 'true' : 'false',
      onclick: () => { if (p.size !== value) call('photoUpdate', { id: p.id, size: value }); },
    }, label);

    return h('article', { class: 'md-photo' + (p.size === 'half' ? ' is-half' : '') },
      h('div', { class: 'md-thumb' }, h('img', { src: fileUrl(p.file), alt: p.alt || 'Фото', loading: 'lazy' })),
      h('div', { class: 'md-size-row', role: 'group', 'aria-label': 'Размер на странице' },
        h('span', { class: 'ad-dim' }, 'Ширина:'), sizeBtn('quarter', '1/4'), sizeBtn('half', '1/2')),
      catSel, alt,
      h('div', { class: 'md-tools' },
        h('button', { type: 'button', class: 'ad-btn ad-btn-sm', disabled: idx === 0, 'aria-label': 'Сдвинуть раньше', onclick: () => moveItem(list, p.id, -1, 'photosReorder', { category: p.category }) }, '←'),
        h('button', { type: 'button', class: 'ad-btn ad-btn-sm', disabled: idx === list.length - 1, 'aria-label': 'Сдвинуть позже', onclick: () => moveItem(list, p.id, 1, 'photosReorder', { category: p.category }) }, '→'),
        h('span', { class: 'ad-dim md-pos' }, (idx + 1) + ' из ' + list.length),
        h('button', { type: 'button', class: 'ad-btn ad-btn-sm ad-btn-danger', onclick: async () => {
          const ok = await confirmBox({ title: 'Удалить фото?', text: 'Фото исчезнет с сайта' + (p.file.startsWith('uploads/') ? ' и будет удалено с сервера.' : ' (сам файл останется на сервере, так как он был добавлен вручную).'), ok: 'Удалить', danger: true });
          if (ok) await call('photoDelete', { id: p.id }, 'Фото удалено.');
        } }, 'Удалить')));
  }

  function photosBlock() {
    const list = M.photos.filter(p => p.category === M.cat);
    const picker = h('div', { class: 'ad-filters' }, M.categories.map(c => h('button', {
      type: 'button', class: 'ad-chip', 'aria-pressed': c.id === M.cat ? 'true' : 'false',
      onclick: () => { M.cat = c.id; draw(); },
    }, c.name, h('b', null, String(M.photos.filter(p => p.category === c.id).length)))));

    const fileInput = h('input', { type: 'file', accept: 'image/jpeg,image/png,image/webp', multiple: true, class: 'md-file', 'aria-label': 'Выбрать фото' });
    fileInput.addEventListener('change', () => { const files = Array.from(fileInput.files); fileInput.value = ''; uploadPhotos(files); });
    const sizeSel = h('select', { class: 'ad-input md-sel', 'aria-label': 'Ширина новых фото' },
      h('option', { value: 'quarter', selected: M.size === 'quarter' }, 'Четверть ширины (1/4)'),
      h('option', { value: 'half', selected: M.size === 'half' }, 'Половина ширины (1/2)'));
    sizeSel.addEventListener('change', () => { M.size = sizeSel.value; });

    const drop = h('label', { class: 'md-drop' }, h('span', null, 'Перетащите фото сюда или нажмите, чтобы выбрать'), h('small', { class: 'ad-dim' }, 'JPG, PNG, WebP, до ' + mb(M.limits.maxPhotoBytes || 15728640) + ' каждое. Загружаются в категорию «' + ((M.categories.find(c => c.id === M.cat) || {}).name || '') + '».'), fileInput);
    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('is-over'); }));
    drop.addEventListener('drop', e => uploadPhotos(Array.from(e.dataTransfer.files)));

    return h('div', { class: 'ad-card md-block' },
      h('h3', null, 'Фото на главной странице'),
      picker,
      h('div', { class: 'md-upload-row' }, drop, h('label', { class: 'ad-field' }, 'Ширина новых фото', sizeSel)),
      h('div', { id: 'adMediaUploads', class: 'md-uploads' }),
      list.length
        ? h('div', { class: 'md-grid' }, list.map((p, i) => photoCard(p, list, i)))
        : h('div', { class: 'ad-empty' }, 'В этой категории пока нет фото.'));
  }

  function videoCard(v, idx) {
    const preview = v.poster
      ? h('img', { src: fileUrl(v.poster), alt: 'Обложка видео', loading: 'lazy' })
      : h('video', { src: fileUrl(v.file) + '#t=0.1', preload: 'metadata', muted: true, playsinline: true });
    return h('article', { class: 'md-video' },
      h('div', { class: 'md-thumb' }, preview),
      h('div', { class: 'md-vinfo' }, h('b', null, v.name || v.file.split('/').pop()), h('span', { class: 'ad-dim' }, v.size ? mb(v.size) : 'добавлено вручную')),
      h('div', { class: 'md-tools' },
        h('button', { type: 'button', class: 'ad-btn ad-btn-sm', disabled: idx === 0, 'aria-label': 'Сдвинуть раньше', onclick: () => moveItem(M.videos, v.id, -1, 'videosReorder') }, '←'),
        h('button', { type: 'button', class: 'ad-btn ad-btn-sm', disabled: idx === M.videos.length - 1, 'aria-label': 'Сдвинуть позже', onclick: () => moveItem(M.videos, v.id, 1, 'videosReorder') }, '→'),
        h('span', { class: 'ad-dim md-pos' }, (idx + 1) + ' из ' + M.videos.length),
        h('button', { type: 'button', class: 'ad-btn ad-btn-sm ad-btn-danger', onclick: async () => {
          const ok = await confirmBox({ title: 'Удалить видео?', text: 'Видео исчезнет с сайта' + (v.file.startsWith('uploads/') ? ' и будет удалено с сервера.' : ' (сам файл останется на сервере, так как он был добавлен вручную).'), ok: 'Удалить', danger: true });
          if (ok) await call('videoDelete', { id: v.id }, 'Видео удалено.');
        } }, 'Удалить')));
  }

  function videosBlock() {
    const fileInput = h('input', { type: 'file', accept: 'video/mp4,video/webm,.mp4,.webm', multiple: true, class: 'md-file', 'aria-label': 'Выбрать видео' });
    fileInput.addEventListener('change', () => { const files = Array.from(fileInput.files); fileInput.value = ''; uploadVideos(files); });
    const drop = h('label', { class: 'md-drop' }, h('span', null, 'Нажмите, чтобы выбрать видео'),
      h('small', { class: 'ad-dim' }, 'MP4 (H.264) или WebM, до ' + mb(M.limits.maxVideoBytes || 524288000) + '. Файл загружается частями, закрывать страницу до конца загрузки нельзя.'), fileInput);
    return h('div', { class: 'ad-card md-block' },
      h('h3', null, 'Видео на главной странице'),
      h('div', { class: 'md-upload-row' }, drop),
      h('div', { id: 'adMediaUploadsV', class: 'md-uploads' }),
      M.videos.length
        ? h('div', { class: 'md-grid md-grid-v' }, M.videos.map(videoCard))
        : h('div', { class: 'ad-empty' }, 'Видео пока нет. Блок «Видео» на сайте не показывается.'));
  }

  function draw() {
    const root = $('#adMedia');
    if (!root) return;
    if (!M.loaded) { setChildren(root, h('div', { class: 'ad-empty' }, 'Загружаем…')); return; }
    setChildren(root, limitsBanner(), categoriesBlock(), photosBlock(), videosBlock());
    drawUploads();
  }

  // Общий список загрузок показывается и под фото, и под видео
  function drawUploads() {
    ['adMediaUploads', 'adMediaUploadsV'].forEach(id => {
      const box = document.getElementById(id);
      if (!box) return;
      setChildren(box, M.uploads.map(u => h('div', { class: 'md-upl' + (u.err ? ' is-err' : '') },
        h('span', { class: 'md-upl-name' }, u.name),
        u.err ? h('span', { class: 'md-upl-msg' }, u.err)
          : h('progress', { class: 'md-bar', max: '100', value: String(Math.round(u.p * 100)), 'aria-label': 'Загрузка ' + u.name }))));
    });
  }

  A.register({
    init() { /* данные загружаются при открытии вкладки */ },
    onTab(name) { if (name === 'media') load(); },
  });
})();
