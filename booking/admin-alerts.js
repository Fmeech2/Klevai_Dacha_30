/* =====================================================================
   КЛЁВАЯ ДАЧА — оповещения админ-панели о новых заявках и отзывах.
   Подключается из admin.php после основного скрипта (через window.KDAdmin).

   Что делает:
   - раз в 12 секунд спрашивает у сервера (admin-api.php?action=poll), не появились ли
     новые заявки или отзывы на проверку;
   - если появились: иконка вкладки браузера начинает мигать красным кружком, в заголовке
     вкладки мигает «Новая заявка», звучит сигнал (и повторяется каждые 30 секунд, пока вы не
     вернётесь на вкладку);
   - мигание и звук прекращаются, когда вы возвращаетесь на вкладку или нажимаете что-либо на странице;
   - показывает на вкладках «Заявки» и «Отзывы» красные счётчики.

   Звук: браузеры не разрешают проигрывать звук, пока на странице не было ни одного нажатия.
   Поэтому после открытия панели достаточно один раз нажать в любом месте страницы.
   ===================================================================== */
(() => {
  'use strict';
  const A = window.KDAdmin;
  if (!A) return;
  const { h, $, api } = A;

  const POLL_MS = 12000;        // как часто спрашиваем сервер
  const REPEAT_MS = 30000;      // как часто повторять звук, пока тревога не снята
  const AUTO_ACK_MS = 6000;     // если вы смотрите на вкладку в момент события, тревога снимается сама
  const LS_REQ = 'kd_admin_seen_req', LS_REV = 'kd_admin_seen_rev', LS_SOUND = 'kd_admin_sound';

  const ls = {
    get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* хранилище недоступно: работаем без запоминания */ } },
  };
  const toNum = v => (v === null || v === undefined || v === '' || isNaN(+v)) ? null : +v;

  const baseTitle = document.title;
  const iconLink = document.querySelector('link[rel~="icon"]');
  const originalHref = iconLink ? iconLink.getAttribute('href') : '';

  let seen = { req: toNum(ls.get(LS_REQ)), rev: toNum(ls.get(LS_REV)) };   // что уже видели на этом устройстве
  let latest = { req: 0, rev: 0 };                                          // что есть на сервере
  let pending = { requests: 0, reviews: 0 };
  let soundOn = ls.get(LS_SOUND) !== '0';
  let alerting = false, blinkTimer = 0, repeatTimer = 0, autoAckTimer = 0, blinkPhase = false;
  let plainIcon = '', redIcon = '', polling = false;
  let audioCtx = null;

  /* ---------- звук ---------- */

  function audio() {
    if (!audioCtx) {
      const C = window.AudioContext || window.webkitAudioContext;
      if (!C) return null;
      try { audioCtx = new C(); } catch (e) { return null; }
    }
    if (audioCtx.state === 'suspended') audioCtx.resume().catch(() => { });
    return audioCtx;
  }

  function tone(ctx, freq, start, dur, vol) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(vol, start + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + dur);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start(start);
    osc.stop(start + dur + 0.05);
  }

  /** Короткая мелодия «дин-дон». Возвращает false, если браузер ещё не разрешил звук. */
  function playSound() {
    if (!soundOn) return true;
    const ctx = audio();
    if (!ctx || ctx.state !== 'running') return false;
    const t = ctx.currentTime;
    tone(ctx, 880, t, 0.28, 0.35);
    tone(ctx, 1175, t + 0.3, 0.28, 0.35);
    tone(ctx, 880, t + 0.7, 0.28, 0.35);
    tone(ctx, 1175, t + 1.0, 0.5, 0.35);
    return true;
  }

  let hint = null;
  function showSoundHint(on) {
    if (on && !hint) {
      hint = h('div', { class: 'ad-banner', id: 'adSoundHint' }, 'Звуковой сигнал пока не может сработать: браузер разрешает звук только после первого нажатия на странице. Нажмите в любом месте.');
      const box = $('#adBanners');
      if (box && box.parentNode) box.parentNode.insertBefore(hint, box);
    } else if (!on && hint) {
      hint.remove();
      hint = null;
    }
  }

  /* ---------- иконка и заголовок вкладки ---------- */

  function prepareIcons() {
    const draw = (img) => {
      const c = document.createElement('canvas');
      c.width = c.height = 64;
      const x = c.getContext('2d');
      if (img) {
        x.drawImage(img, 0, 0, 64, 64);
      } else {
        x.fillStyle = '#2F80ED';
        x.fillRect(0, 0, 64, 64);
      }
      plainIcon = c.toDataURL('image/png');
      x.fillStyle = '#E53935';
      x.beginPath();
      x.arc(46, 18, 16, 0, Math.PI * 2);
      x.fill();
      x.lineWidth = 4;
      x.strokeStyle = '#fff';
      x.stroke();
      redIcon = c.toDataURL('image/png');
    };
    if (!iconLink || !originalHref) { draw(null); return; }
    const img = new Image();
    img.onload = () => { try { draw(img); } catch (e) { draw(null); } };
    img.onerror = () => draw(null);
    img.src = new URL(originalHref, location.href).href;
  }

  function setIcon(href) {
    if (!iconLink) return;
    iconLink.setAttribute('type', 'image/png');
    iconLink.setAttribute('href', href);
  }

  function updateTitle(blinkText) {
    const n = pending.requests + pending.reviews;
    document.title = (blinkText ? blinkText + ' ' : '') + (n ? '(' + n + ') ' : '') + baseTitle;
  }

  function startBlink() {
    if (blinkTimer) return;
    blinkTimer = setInterval(() => {
      blinkPhase = !blinkPhase;
      if (redIcon) setIcon(blinkPhase ? redIcon : plainIcon);
      updateTitle(blinkPhase ? '●' : '');
    }, 650);
  }

  function stopBlink() {
    clearInterval(blinkTimer);
    blinkTimer = 0;
    blinkPhase = false;
    if (iconLink && originalHref) { iconLink.setAttribute('href', originalHref); iconLink.removeAttribute('type'); }
    updateTitle('');
  }

  /* ---------- тревога ---------- */

  function startAlert() {
    if (alerting) return;
    alerting = true;
    startBlink();
    if (!playSound()) showSoundHint(true);
    repeatTimer = setInterval(() => { if (!playSound()) showSoundHint(true); }, REPEAT_MS);
    // Если вы и так смотрите на вкладку, тревога не должна мигать бесконечно
    if (document.visibilityState === 'visible' && document.hasFocus()) {
      autoAckTimer = setTimeout(acknowledge, AUTO_ACK_MS);
    }
  }

  /** Вы увидели новое: запоминаем, что видели, и гасим мигание и звук. */
  function acknowledge() {
    seen = { req: latest.req, rev: latest.rev };
    ls.set(LS_REQ, String(seen.req));
    ls.set(LS_REV, String(seen.rev));
    if (!alerting) return;
    alerting = false;
    clearInterval(repeatTimer);
    clearTimeout(autoAckTimer);
    repeatTimer = autoAckTimer = 0;
    stopBlink();
    showSoundHint(false);
  }

  function onUserActivity() {
    if (document.visibilityState !== 'visible') return;
    // Первое нажатие «открывает» звук для браузера
    const ctx = audio();
    if (ctx && ctx.state === 'running') showSoundHint(false);
    if (alerting) acknowledge();
  }

  /* ---------- счётчики на вкладках ---------- */

  function setBadge(id, n) {
    const el = document.getElementById(id);
    if (!el) return;
    el.hidden = n === 0;
    el.textContent = String(n);
  }

  // Модуль отзывов сообщает, сколько отзывов ждут решения после модерации
  window.addEventListener('kd:reviews', e => {
    pending.reviews = (e.detail && e.detail.pending) || 0;
    setBadge('adRevBadge', pending.reviews);
    updateTitle(blinkPhase ? '●' : '');
  });

  /* ---------- опрос сервера ---------- */

  async function poll() {
    if (polling) return;
    polling = true;
    try {
      const d = await api('poll');
      pending = { requests: d.newRequests, reviews: d.pendingReviews };
      latest = { req: d.maxRequestNumber, rev: d.maxReviewNumber };
      setBadge('adRevBadge', pending.reviews);
      updateTitle(blinkPhase ? '●' : '');

      if (seen.req === null || seen.rev === null) {
        // Первый запуск на этом устройстве: ничего не «пропущено»
        seen = { req: latest.req, rev: latest.rev };
        ls.set(LS_REQ, String(seen.req));
        ls.set(LS_REV, String(seen.rev));
        return;
      }
      if (latest.req > seen.req || latest.rev > seen.rev) {
        const first = !alerting;
        startAlert();
        if (first) A.load(true);    // сразу обновляем списки на странице
      }
    } catch (e) {
      /* нет связи: следующий опрос попробует снова, основная панель сама покажет баннер об ошибке */
    } finally {
      polling = false;
    }
  }

  /* ---------- кнопка звука в шапке ---------- */

  function addSoundButton() {
    const bar = document.querySelector('.ad-top-in');
    if (!bar) return;
    const btn = h('button', { type: 'button', class: 'ad-btn ad-btn-sm', id: 'adSoundBtn', 'aria-pressed': soundOn ? 'true' : 'false' });
    const label = () => { btn.textContent = soundOn ? 'Звук: вкл' : 'Звук: выкл'; btn.setAttribute('aria-pressed', soundOn ? 'true' : 'false'); };
    label();
    btn.addEventListener('click', () => {
      soundOn = !soundOn;
      ls.set(LS_SOUND, soundOn ? '1' : '0');
      label();
      if (soundOn) playSound();     // нажатие пользователя разрешает звук; сразу даём послушать
    });
    const refresh = bar.querySelector('#adRefresh');
    if (refresh) {
      const wrap = h('div', { class: 'ad-top-actions' });
      bar.insertBefore(wrap, refresh);
      wrap.append(btn, refresh);
    } else {
      bar.append(btn);
    }
  }

  A.register({
    init() {
      prepareIcons();
      addSoundButton();
      ['pointerdown', 'keydown', 'touchend'].forEach(ev => window.addEventListener(ev, onUserActivity, { capture: true, passive: true }));
      document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') { onUserActivity(); poll(); } });
      window.addEventListener('focus', onUserActivity);
      poll();
      setInterval(poll, POLL_MS);
    },
  });
})();
