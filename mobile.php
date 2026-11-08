<?php require __DIR__ . '/avito/render_reviews.php'; ?>
<?php
// Фото и видео главной страницы берутся из админ-панели (booking/data/content-data.json)
$kdGallery = function_exists('kd_gallery_html') ? kd_gallery_html('mobile') : ['tabs' => '', 'panels' => ''];
$kdVideos  = function_exists('kd_videos_html') ? kd_videos_html('mobile') : '';
?>
<!doctype html>
<html lang="ru">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#2F80ED" />
  <title>Клёвая Дача 30</title>
  <link rel="icon" href="img/Logo_zaglyshka_icon.png">
  <link rel="stylesheet" href="mobile.css">
  <link rel="stylesheet" href="reviews_photos.css">
</head>

<body id="start">

  <!-- Верёвка слева: крюк ползёт вверх при прокрутке (подробности — в mobile.css, раздел 3) -->
  <div class="scroll-rope" id="scrollRope" aria-hidden="true">
    <img src="img/Крюк ползёт.png" alt="" class="scroll-rope_cap">
    <div class="scroll-rope_body"></div>
  </div>

  <!-- ===================== ШАПКА + БОКОВОЕ МЕНЮ (как в версии Grok) ===================== -->
  <header class="m-header" id="mHeader">
    <div class="m-header-inner">
      <a href="#start">
        <img src="img/Logo_zaglyshka_footer2.png" alt="Клёвая Дача" class="m-logo">
      </a>
      <div class="m-header-title">
        <div class="m-header-title-main">КЛЁВАЯ ДАЧА</div>
        <div class="m-header-title-sub">Аренда дома на реке</div>
      </div>
      <button class="m-burger" id="mBurger" aria-label="Меню" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <div class="m-nav-overlay" id="mNavOverlay"></div>
  <nav class="m-nav" id="mNav" aria-hidden="true">
    <button class="m-nav-close" id="mNavClose" aria-label="Закрыть">×</button>
    <div class="m-nav-links">
      <a href="#start">Главная</a>
      <a href="#gallery">Фото</a>
      <a href="#reviews">Отзывы</a>
      <a href="#contacts">Контакты</a>
    </div>
    <div class="m-nav-contacts">
      <a href="tel:+79171725558" class="m-nav-phone">+7 917 172-55-58</a>
      <div class="m-nav-socials">
        <a href="https://vk.ru/club11109186" target="_blank" rel="noopener">
          <img src="img/VK PNG.png" alt="VK">
        </a>
        <a href="https://max.ru/u/f9LHodD0cOLFu70KB7BkQSMHHAYgfCx8tbYvh3qt9_pkbZJNzbhYcXr5BbI" target="_blank" rel="noopener">
          <img src="img/Max PNG.png" alt="Max">
        </a>
        <a href="https://api.whatsapp.com/send?phone=79171725558" target="_blank" rel="noopener">
          <img src="img/WhatsApp PNG.png" alt="WhatsApp">
        </a>
        <a href="https://t.me/@ALEXYI91" target="_blank" rel="noopener">
          <img src="img/Telegram PNG.png" alt="Telegram">
        </a>
      </div>
      <!-- ?desktop=1 — просим index.php НЕ переадресовывать обратно на мобильную версию -->
      <a href="index.php?desktop=1" class="m-nav-desktop">Полная версия сайта</a>
    </div>
  </nav>


  <main>

    <!-- ===================== ГЛАВНЫЙ ЭКРАН ===================== -->
    <section class="hero wrap" id="hero">
      <!-- Рыба — иллюстрация сверху (на десктопе она была фоном карточки) -->
      <div class="hero_art" aria-hidden="true">
        <img src="img/Рыба на превью.png" alt="" class="hero_fish">
      </div>

      <div class="hero_text">
        <h1 class="hero_title">Хотите идеальный отдых у воды?</h1>
        <p class="hero_desc">В гостевом доме „Клёвая дача“ вас ждут комфорт, все удобства, просторная беседка и
          мангал, коптильня — и, конечно, отличная рыбалка. Собирайте компанию, берите снасти — и приезжайте за
          впечатлениями!»</p>

        <a class="hero_phone" href="tel:+79171725558">+7 (917) 172-55-58</a><br>
        <a class="hero_wa" href="https://api.whatsapp.com/send?phone=79171725558" target="_blank" rel="noopener">Написать в Вацап</a>
        <button type="button" class="btn" data-booking-open data-track="booking_hero">Забронировать</button>
      </div>

      <!-- Карточка дома: цена и вместимость + фото -->
      <div class="home_card">
        <h2 class="home_card_title">Гостевой дом<br> "КЛЁВАЯ ДАЧА"</h2>
        <div class="home_card_inner">
          <div class="home_card_imgs">
            <img src="img/Шашлык превью.png" alt="Мангальная зона" data-group="hero">
            <img src="img/Прихожая превью.png" alt="Прихожая" data-group="hero">
          </div>
          <div class="home_card_info">
            <div class="home_card_h">Мангальная зона</div>
            <div class="home_card_p">Красивый вид на реку с вкусными шашлыками</div>
            <div class="home_card_tags">
              <span class="home_card_tag">Дом на 4 человек</span>
              <span class="home_card_tag">от 6000 руб./сутки</span>
            </div>
          </div>
        </div>
      </div>
    </section>


    <!-- ===================== КАРТА, ЛОДКА И АДРЕС ===================== -->
    <section class="where" id="contacts">
      <div class="wrap">
        <div class="where_map" id="mapBox">
          <!-- Карта 2ГИС: iframe подгружается сам (loading="lazy"), когда блок приближается к экрану -->
          <div class="where_map_slot" id="mapSlot">
            <iframe id="map_104546197" frameborder="0" width="100%" height="100%" loading="lazy" title="Карта проезда, 2ГИС" src="https://makemap.2gis.ru/widget?data=eJyllU1rHEcQhv9L57qIrs_u3msgxhCIIQdDjA4bbVseMtpZRmOILPTfU9NfIigGQ3RaPVNT_dbbVTXPblnPec3nd3l5yNs65Ud3_PTstqdrdkf3Sz5tX9fsDu66Lte8buX5szvnx7t1um7TcrEoe_y4rctf-edlXlYDP-UkJDj4x-m8fXFH7f__dj3dTduTO8LBfXt_Oee_3RFfDu6-injaj2gKfp0u-XfTdbm3bHeLqZ0up62o_MThJkUmxAPLTUJBH24PlZJShZxIBvTcqIQ4QoFRK40xpEZDgsSFEtqLgyJIpaQigyrWvMSK4zQAjY0SwNAAsWVg8a_KYuoZmAdVaqEaRXcYbzwAhQLtTB8b9NEKLTSChB7qWWI6sNovFB0JKEql4DVwjyVpFJMn6BQiY6EsAbjXGzBJoZESx0bZfupOAb2Oi5DAuwtGhcK4iQDCXKjGANqopog11s7qoapeoEJB6RKUlGto9Bw7lRi5xkZzt2cQAAiFJjFHulwrrVD0CICNEgNipeppUPKpUfO5JxChIgHRR-z3K94qqhS9790kyYyoNIRBlYMWF9A6REbn-bR36U4DjtgEkMr9oETwXViKQWqsJLOp0xC03ASq2CGdMlEtQs2oTiMr1QwBAg4JAlVYEB6Dogmqu1aDNd-gtUMw2CTGcb-Bar12IWmkpZAajfo6ESlVBeRF6kWUzqsJCIJNRIeYQCqNLKNJVevtkE0E6FsqbdDKoFgXVWobYIyE-VzMNYc4pU6DDV6lnLBTu1esymxisGtAc6x4Q2yedWgv1srYhoM6tVFsoQFlUOXaeMQRzcdb24bT2Raktf_L4X_vYwx_Evgf2sefp3ke76mclZKrdET5G3nd2_Tfe_vDMj_dm5o3S7vv53017UtXgccqFqC2y1V5tKiyb7H2XEfjJuwrXvB1vf477_DRpjX-gI93vXCPET7vH7Bt2uZcPX1jcrcAfP_7nhfTZXvjRK0iaq-NrPmb2sDycntwD6frh-Vxqgc-u_m07YVYrH2qkrWngPhwcPP-uH1GbB-KjVxC-6a5b8vyYOqsgZwpX-b545ec5z8K3dav-eUfZ2j_GQ" sandbox="allow-modals allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation-by-user-activation"></iframe>
          </div>
          <button class="map_shield" id="mapShield" type="button" aria-label="Включить управление картой">
            <span>Нажмите, чтобы двигать карту</span>
          </button>
        </div>
      </div>

      <!--<div class="where_boat" aria-hidden="true">
        <img src="img/Лодка мобильная.png" alt="" loading="lazy">
      </div>-->

      <div class="wrap">
        <div class="where_card">
          <div class="where_addr">
            Астраханская область, Камызякский район, поселок Нижненикольский <br>
            На реке Гандурино.
          </div>
          <a class="where_tel" href="tel:+79171725558">Телефон: +7 (917) 172-55-58</a>
        </div>
      </div>
    </section>


    <!-- ===================== ФОТОГАЛЕРЕЯ И ВИДЕО ===================== -->
    <section class="gal wrap" id="gallery">
      <h2 class="sec_title">Фотогалерея</h2>

      <div class="tabs" role="tablist" aria-label="Галерея">
        <?= $kdGallery['tabs'] ?>
      </div>

<?= $kdGallery['panels'] ?>
<?php if ($kdVideos !== ''): ?>
      <h3 class="sub_title">Видео</h3>
      <div class="vid_grid">
<?= $kdVideos ?>
      </div>
<?php endif; ?>
    </section>


    <!-- ===================== ОТЗЫВЫ ===================== -->
    <!-- Кнопка «Забронировать» перед отзывами -->
    <section class="cta_end cta_mid wrap" id="cta_mid">
      <h2 class="sec_title">Понравилось у нас?</h2>
      <p class="cta_end_text">Выберите даты и оставьте заявку: хозяин перезвонит и подтвердит бронь.</p>
      <button type="button" class="btn" data-booking-open data-track="booking_middle">Забронировать</button>
    </section>

    <section class="rev wrap" id="reviews">
      <h2 class="sec_title">Отзывы</h2>

      <?= render_reviews() ?>

      <!-- Форма -->
      <div class="review_form_wrapper">
        <h3 class="review_form_title">Оставить отзыв</h3>

        <form class="review_form" id="reviewForm" action="#" method="post" novalidate>
          <div class="review_form_row">
            <input type="text" name="firstName" class="review_input" placeholder="Имя*" maxlength="50" autocomplete="given-name" enterkeyhint="next" required>
            <input type="text" name="lastName" class="review_input" placeholder="Фамилия" maxlength="50" autocomplete="family-name" enterkeyhint="next">
          </div>

          <input type="tel" name="phone" class="review_input review_input_phone" placeholder="Номер телефона (по желанию)" maxlength="30" autocomplete="tel" inputmode="tel" enterkeyhint="next">

          <textarea name="text" class="review_textarea" placeholder="Ваш отзыв*" rows="4" maxlength="2000" required></textarea>

          <div class="review_attach">
            <input type="file" id="reviewFiles" class="review_file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,.mp4,.webm" multiple tabindex="-1" aria-hidden="true">
            <button type="button" class="review_attach_btn" id="reviewAttach">
              <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M21 11.5l-8.6 8.6a5 5 0 01-7.1-7.1l8.6-8.6a3.3 3.3 0 014.7 4.7l-8.6 8.6a1.7 1.7 0 01-2.4-2.4l7.9-7.9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Прикрепить фото или видео
            </button>
            <span class="review_attach_hint" id="reviewAttachHint">до 6 фото и 1 видео</span>
            <ul class="review_files" id="reviewFileList"></ul>
          </div>

          <!-- Ловушка для спам-ботов: человек это поле не видит и не заполняет -->
          <div class="review_hp" aria-hidden="true"><label>Сайт <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

          <label class="review_check">
            <input type="checkbox" name="consent">
            <span>Я даю <a href="consent.html" target="_blank" rel="noopener">согласие на обработку персональных данных</a> и ознакомлен(а) с <a href="privacy.html" target="_blank" rel="noopener">Политикой обработки персональных данных</a>.</span>
          </label>
          <p class="review_note">Отзыв появится на сайте после проверки хозяином. Телефон нужен только хозяину и на сайте не показывается.</p>
          <div class="review_msg" id="reviewMsg" role="alert" hidden></div>

          <button type="submit" class="review_submit" data-track="review_submit">Отправить</button>
        </form>
      </div>
    </section>

    <!-- Ещё одна кнопка «Забронировать» в самом конце страницы -->
    <section class="cta_end wrap" id="cta_end">
      <h2 class="sec_title">Готовы приехать?</h2>
      <p class="cta_end_text">Выберите даты в календаре и оставьте заявку. Хозяин перезвонит и подтвердит бронь.</p>
      <button type="button" class="btn" data-booking-open data-track="booking_bottom">Забронировать</button>
    </section>

  </main>


  <!-- ===================== ФУТЕР (как в версии Grok) ===================== -->
  <footer class="m-footer">
    <!-- Крепление верёвки (только узел) -->
    <img src="img/Крюк ползёт низ.png" alt="" class="rope-anchor" id="ropeAnchor" aria-hidden="true">

    <div class="m-footer-inner">
      <img src="img/Logo_zaglyshka_footer2.png" alt="Клёвая Дача" class="m-footer-logo">
      <div class="m-footer-copy">© Клёвая Дача 2026. Все права защищены.</div>
      <a class="m-footer-legal" href="privacy.html">Политика обработки персональных данных</a>
      <div class="m-footer-phones">
        <div class="m-footer-phone-row">
          <span>Телефон:</span>
          <strong><a href="tel:+79171725558">+7 (917) 172-55-58</a></strong>
          <span>Алексей</span>
        </div>
        <div class="m-footer-phone-row">
          <span>Телефон:</span>
          <strong><a href="tel:+79673379957">+7 (967) 337-99-57</a></strong>
          <span>Анна</span>
        </div>
      </div>
      <a class="m-footer-email" href="mailto:Astrakhan@klevaydacha30.ru">Astrakhan@klevaydacha30.ru</a>
      <div class="m-footer-socials">
        <a href="https://vk.ru/club11109186" target="_blank" rel="noopener">
          <img src="img/VK PNG.png" alt="VK">
        </a>
        <a href="https://max.ru/u/f9LHodD0cOLFu70KB7BkQSMHHAYgfCx8tbYvh3qt9_pkbZJNzbhYcXr5BbI" target="_blank" rel="noopener">
          <img src="img/Max PNG.png" alt="Max">
        </a>
        <a href="https://api.whatsapp.com/send?phone=79171725558" target="_blank" rel="noopener">
          <img src="img/WhatsApp PNG.png" alt="WhatsApp">
        </a>
        <a href="https://t.me/@ALEXYI91" target="_blank" rel="noopener">
          <img src="img/Telegram PNG.png" alt="Telegram">
        </a>
      </div>
      <a href="index.php?desktop=1" class="m-footer-desktop">Полная версия сайта</a>
    </div>
  </footer>


  <!-- ===================== ЛАЙТБОКС ===================== -->
  <div class="lb" id="lightbox" role="dialog" aria-modal="true" aria-label="Просмотр фото и видео">
    <img src="" alt="" class="lb_media" id="lbImg">
    <video class="lb_media" id="lbVideo" controls playsinline></video>

    <button class="lb_btn lb_prev" id="lbPrev" type="button" aria-label="Назад">‹</button>
    <button class="lb_btn lb_next" id="lbNext" type="button" aria-label="Вперёд">›</button>
    <button class="lb_close" id="lbClose" type="button" aria-label="Закрыть">×</button>
    <div class="lb_count" id="lbCount"></div>
  </div>


  <!-- ===================== БРОНИРОВАНИЕ: модальное окно =====================
       Разметка одинаковая в index.php и mobile.php; оформление — в style.css / mobile.css (классы bk-*).
       Скрипт — в конце страницы, блок «БРОНИРОВАНИЕ». -->
  <div class="bk-overlay" id="bkModal" hidden>
    <div class="bk-dialog" id="bkDialog" role="dialog" aria-modal="true" aria-labelledby="bkTitle" tabindex="-1">
      <button type="button" class="bk-close" id="bkClose" aria-label="Закрыть окно бронирования">×</button>

      <div class="bk-scroll">
        <!-- Основной вид: календарь и форма -->
        <div class="bk-main" id="bkMain">
          <h2 class="bk-title" id="bkTitle">Бронирование</h2>
          <p class="bk-lead">Выберите даты заезда и выезда и оставьте контакты. Хозяин перезвонит, уточнит детали и подтвердит бронь.</p>

          <div class="bk-layout">
            <section class="bk-cal" aria-label="Календарь доступности">
              <div class="bk-summary" id="bkSummary" aria-live="polite"></div>
              <div class="bk-msg" id="bkMsg" role="alert" hidden></div>
              <button type="button" class="bk-link" id="bkClearDates" hidden>Сбросить даты</button>

              <div class="bk-cal-head">
                <button type="button" class="bk-nav" id="bkPrev" aria-label="Предыдущий месяц">‹</button>
                <div class="bk-month" id="bkMonth" aria-live="polite"></div>
                <button type="button" class="bk-nav" id="bkNext" aria-label="Следующий месяц">›</button>
              </div>

              <div class="bk-week" aria-hidden="true">
                <span>Пн</span><span>Вт</span><span>Ср</span><span>Чт</span><span>Пт</span><span>Сб</span><span>Вс</span>
              </div>
              <div class="bk-grid" id="bkGrid" role="group" aria-label="Дни месяца"></div>

              <div class="bk-calstate" id="bkCalState" role="status"></div>

              <ul class="bk-legend">
                <li><span class="bk-sw bk-sw-free"></span>Свободно</li>
                <li><span class="bk-sw bk-sw-busy"></span>Занято</li>
                <li><span class="bk-sw bk-sw-sel"></span>Выбрано</li>
                <li><span class="bk-sw bk-sw-past"></span>Прошло</li>
              </ul>
              <p class="bk-hint">Наполовину закрашенный день — заезд или выезд других гостей. В день их выезда можно заезжать.</p>
            </section>

            <form class="bk-form" id="bkForm" novalidate>
              <h3 class="bk-form-title">Ваши данные</h3>

              <div class="bk-row">
                <label class="bk-field">
                  <span class="bk-label">Имя<span class="bk-req" aria-hidden="true">*</span></span>
                  <input class="bk-input" type="text" name="firstName" maxlength="50" autocomplete="given-name" required aria-describedby="bkErr-firstName">
                  <span class="bk-err" id="bkErr-firstName"></span>
                </label>
                <label class="bk-field">
                  <span class="bk-label">Фамилия</span>
                  <input class="bk-input" type="text" name="lastName" maxlength="50" autocomplete="family-name" aria-describedby="bkErr-lastName">
                  <span class="bk-err" id="bkErr-lastName"></span>
                </label>
              </div>

              <label class="bk-field">
                <span class="bk-label">Отчество</span>
                <input class="bk-input" type="text" name="patronymic" maxlength="50" autocomplete="additional-name" aria-describedby="bkErr-patronymic">
                <span class="bk-err" id="bkErr-patronymic"></span>
              </label>

              <label class="bk-field">
                <span class="bk-label">Номер телефона<span class="bk-req" aria-hidden="true">*</span></span>
                <input class="bk-input" type="tel" name="phone" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" required aria-describedby="bkErr-phone">
                <span class="bk-err" id="bkErr-phone"></span>
              </label>

              <label class="bk-field">
                <span class="bk-label">Комментарий</span>
                <textarea class="bk-input bk-textarea" name="comment" rows="3" maxlength="1000" placeholder="Например: удобное время для звонка, сколько вас человек"></textarea>
              </label>

              <!-- Ловушка для спам-ботов: человек это поле не видит и не заполняет -->
              <div class="bk-hp" aria-hidden="true">
                <label>Сайт <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
              </div>

              <label class="bk-check">
                <input type="checkbox" name="consent" aria-describedby="bkErr-consent">
                <span>Я даю <a href="consent.html" id="bkConsent" target="_blank" rel="noopener">согласие на обработку персональных данных</a> и ознакомлен(а) с <a href="privacy.html" id="bkPrivacy" target="_blank" rel="noopener">Политикой обработки персональных данных</a>.</span>
              </label>
              <span class="bk-err" id="bkErr-consent"></span>

              <span class="bk-err" id="bkErr-dates"></span>
              <div class="bk-msg" id="bkFormMsg" role="alert" hidden></div>

              <button type="submit" class="bk-submit" id="bkSubmit">
                <span class="bk-spin" aria-hidden="true"></span>
                <span class="bk-submit-label">Отправить заявку</span>
              </button>
              <p class="bk-note">Даты станут занятыми только после подтверждения хозяином. Не получается? Позвоните: <a href="tel:+79171725558">+7 917 172-55-58</a></p>
            </form>
          </div>
        </div>

        <!-- Вид после успешной отправки -->
        <div class="bk-done" id="bkDone" hidden>
          <div class="bk-done-icon" aria-hidden="true"></div>
          <h2 class="bk-title" id="bkDoneTitle" tabindex="-1">Заявка отправлена</h2>
          <p class="bk-done-text" id="bkDoneText"></p>
          <p class="bk-done-num" id="bkDoneNum"></p>
          <button type="button" class="bk-submit bk-done-btn" id="bkDoneClose">Закрыть</button>
        </div>
      </div>
    </div>
  </div>


  <script>
    (function () {
      'use strict';

      const $ = (s, r) => (r || document).querySelector(s);
      const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
      const root = document.documentElement;

      // ================= Блокировка прокрутки страницы (меню / лайтбокс) =================
      let menuOpen = false;
      let lbOpen = false;
      function updateScrollLock() {
        root.classList.toggle('no-scroll', menuOpen || lbOpen);
      }

      // ================= Шапка: большая -> маленькая =================
      const header = $('#mHeader');
      const SHRINK_AT = 40;
      let isShrunk = false;

      function updateHeader() {
        const next = window.scrollY > SHRINK_AT;
        if (next === isShrunk) return;
        isShrunk = next;
        header.classList.toggle('shrink', next);
      }

      // ================= Боковое меню (бургер) =================
      const burger = $('#mBurger');
      const nav = $('#mNav');
      const navOverlay = $('#mNavOverlay');
      const navClose = $('#mNavClose');

      function setNav(open) {
        menuOpen = open;
        nav.classList.toggle('is-open', open);
        navOverlay.classList.toggle('is-open', open);
        burger.classList.toggle('is-open', open);
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        nav.setAttribute('aria-hidden', open ? 'false' : 'true');
        updateScrollLock();
      }
      burger.addEventListener('click', () => setNav(!menuOpen));
      navOverlay.addEventListener('click', () => setNav(false));
      navClose.addEventListener('click', () => setNav(false));
      $$('#mNav a[href^="#"]').forEach(a => a.addEventListener('click', () => setNav(false)));
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && menuOpen) setNav(false); });

      // ----- Закрытие меню без крестика -----
      // 1) Тап по пустому месту слева от меню. Затемнение уже закрывало меню, но верхняя полоса
      //    экрана принадлежит шапке (она лежит выше затемнения) — тап по ней ничего не делал.
      //    Тап по самому бургеру не трогаем: он открывает и закрывает меню сам.
      header.addEventListener('click', e => {
        if (menuOpen && !e.target.closest('#mBurger')) setNav(false);
      });

      // 2) Свайп вправо: меню едет за пальцем. Отпустили после 30% ширины или быстрым
      //    движением — закрывается, иначе плавно возвращается на место.
      let swipeX = 0, swipeY = 0, swipeT = 0, swipeW = 0;
      let swipeTracking = false, swipeDecided = false, swipeDragging = false;

      function swipeReset() {
        nav.style.transition = '';
        nav.style.transform = '';
        navOverlay.style.transition = '';
        navOverlay.style.opacity = '';
      }
      function swipeStart(e) {
        if (!menuOpen || e.touches.length !== 1) return;
        swipeX = e.touches[0].clientX;
        swipeY = e.touches[0].clientY;
        swipeT = Date.now();
        swipeW = nav.offsetWidth || 300;
        swipeTracking = true;
        swipeDecided = false;
        swipeDragging = false;
      }
      function swipeMove(e) {
        if (!swipeTracking) return;
        const dx = e.touches[0].clientX - swipeX;
        const dy = e.touches[0].clientY - swipeY;
        if (!swipeDecided) {
          if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;     // ещё не понятно, куда ведёт палец
          swipeDecided = true;
          swipeDragging = dx > 0 && Math.abs(dx) > Math.abs(dy) * 1.2;   // тянут вправо, а не прокручивают вниз
          if (swipeDragging) {
            nav.style.transition = 'none';
            navOverlay.style.transition = 'none';
          }
        }
        if (!swipeDragging) return;
        const x = Math.max(0, dx);
        nav.style.transform = 'translateX(' + x + 'px)';
        navOverlay.style.opacity = String(Math.max(0, 1 - x / swipeW));
      }
      function swipeEnd(e) {
        if (!swipeTracking) return;
        swipeTracking = false;
        if (!swipeDragging) return;
        swipeDragging = false;
        const dx = Math.max(0, e.changedTouches[0].clientX - swipeX);
        const speed = dx / Math.max(1, Date.now() - swipeT);            // px за мс
        const shouldClose = dx > swipeW * 0.3 || (speed > 0.5 && dx > 30);
        swipeReset();                       // дальше CSS-переход доезжает сам: до закрытия или назад
        if (shouldClose) setNav(false);
      }
      function swipeCancel() {
        swipeTracking = false;
        if (swipeDragging) { swipeDragging = false; swipeReset(); }
      }
      [nav, navOverlay, header].forEach(el => {
        el.addEventListener('touchstart', swipeStart, { passive: true });
        el.addEventListener('touchmove', swipeMove, { passive: true });
        el.addEventListener('touchend', swipeEnd, { passive: true });
        el.addEventListener('touchcancel', swipeCancel, { passive: true });
      });

      // ================= Верёвка слева =================
      // Высота верёвки считается так, чтобы она всегда шла от крюка до низа окна,
      // а в конце страницы — ровно до узла в футере (см. раздел 3 в mobile.css).
      const rope = $('#scrollRope');
      const anchor = $('#ropeAnchor');
      let ropeW = 26, headerBig = 60, headerSmall = 52, anchorBottom = 60, knotEnd = 6.2;

      function measureRope() {
        const cs = getComputedStyle(root);
        ropeW = parseFloat(cs.getPropertyValue('--rope-w')) || 26;
        headerBig = parseFloat(cs.getPropertyValue('--header-h')) || 60;
        headerSmall = parseFloat(cs.getPropertyValue('--header-h-small')) || 52;
        anchorBottom = parseFloat(getComputedStyle(anchor).bottom) || 60;
        knotEnd = 25 * ropeW / 105;   // от низа картинки до места, где верёвка уходит в узел (25 строк из 105)
      }

      function updateRope() {
        const vh = root.clientHeight;
        const y = window.scrollY;

        if (y < headerBig) {
          rope.style.opacity = '0';
          rope.style.height = '0px';
          rope.style.transform = 'translateY(0)';
          return;
        }

        const maxScroll = root.scrollHeight - vh;
        const progress = (y - headerBig) / Math.max(1, maxScroll - headerBig);   // 0..1
        const clamped = Math.min(Math.max(progress, 0), 1);
        const move = clamped * (vh - headerSmall);           // на сколько поднялся крюк
        const ropeTop = vh - move;                           // y крюка в окне

        // Футер — последний блок страницы: считаем положение узла от низа страницы
        const remaining = Math.max(0, maxScroll - y);
        const knotY = vh + remaining - (anchorBottom + knotEnd);

        const length = Math.max(0, Math.min(vh, knotY) - ropeTop);

        rope.style.opacity = '1';
        rope.style.height = length + 'px';
        rope.style.transform = 'translateY(-' + move + 'px)';
      }

      // ================= Один обработчик прокрутки =================
      let ticking = false;
      function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
          ticking = false;
          updateHeader();
          updateRope();
        });
      }
      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', () => { measureRope(); onScroll(); });
      window.addEventListener('orientationchange', () => setTimeout(() => { measureRope(); onScroll(); }, 250));
      window.addEventListener('load', () => { measureRope(); onScroll(); });   // после загрузки картинок высота страницы меняется
      measureRope();
      updateHeader();
      updateRope();

      // ================= Вкладки галереи =================
      const tabs = $$('.tab[data-gallery]');
      const galleries = {};
      $$('.gal_grid[id^="gallery_"]').forEach(g => { galleries[g.id.slice(8)] = g; });

      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          const target = tab.dataset.gallery;
          tabs.forEach(t => t.setAttribute('aria-selected', t === tab ? 'true' : 'false'));
          Object.keys(galleries).forEach(k => { galleries[k].hidden = (k !== target); });
        });
      });

      // ================= Лайтбокс (фото и видео, свайпы, кнопки, клавиатура) =================
      const lb = $('#lightbox');
      const lbImg = $('#lbImg');
      const lbVideo = $('#lbVideo');
      const lbPrev = $('#lbPrev');
      const lbNext = $('#lbNext');
      const lbClose = $('#lbClose');
      const lbCount = $('#lbCount');

      let items = [];
      let index = 0;
      let lbHistory = false;

      function showMedia(i) {
        if (!items.length) return;
        index = (i + items.length) % items.length;
        const el = items[index];

        // Сброс
        lbImg.classList.remove('active');
        lbVideo.classList.remove('active');
        lbVideo.pause();
        lbVideo.removeAttribute('src');
        lbVideo.load();

        const video = el.tagName === 'VIDEO' ? el : el.querySelector('video');
        if (video) {
          const source = video.querySelector('source');
          const src = (source ? source.getAttribute('src') : video.getAttribute('src') || '').split('#')[0];
          lbVideo.src = src;
          lbVideo.classList.add('active');
          const p = lbVideo.play();
          if (p && p.catch) p.catch(() => { });
        } else {
          lbImg.src = el.currentSrc || el.src;
          lbImg.alt = el.alt || '';
          lbImg.classList.add('active');
        }

        lbCount.textContent = (index + 1) + ' / ' + items.length;
        const single = items.length < 2;
        lbPrev.style.display = lbNext.style.display = single ? 'none' : '';
      }

      function openLightbox(el) {
        const group = el.dataset.group;
        items = $$('[data-group="' + group + '"]');
        const i = items.indexOf(el);
        if (i === -1) return;

        lb.classList.add('active');
        lbOpen = true;
        updateScrollLock();
        showMedia(i);

        // Кнопка «Назад» на телефоне закрывает лайтбокс, а не уходит со страницы
        try { history.pushState({ lb: 1 }, ''); lbHistory = true; } catch (e) { lbHistory = false; }
      }

      function closeLightbox(fromPop) {
        if (!lb.classList.contains('active')) return;
        lb.classList.remove('active');
        lbOpen = false;
        lbVideo.pause();
        lbVideo.removeAttribute('src');
        lbVideo.load();
        updateScrollLock();
        if (lbHistory && !fromPop) { lbHistory = false; history.back(); }
        lbHistory = false;
      }
      window.addEventListener('popstate', () => closeLightbox(true));

      // Клики по фото и видео (делегирование)
      document.addEventListener('click', e => {
        const el = e.target.closest('[data-group]');
        if (el) openLightbox(el);
      });
      $$('.vwrap').forEach(w => w.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openLightbox(w); }
      }));

      lbPrev.addEventListener('click', e => { e.stopPropagation(); showMedia(index - 1); });
      lbNext.addEventListener('click', e => { e.stopPropagation(); showMedia(index + 1); });
      lbClose.addEventListener('click', e => { e.stopPropagation(); closeLightbox(); });
      lb.addEventListener('click', e => { if (e.target === lb) closeLightbox(); });

      // Свайп: влево — следующее, вправо — предыдущее
      let tx = 0, ty = 0, tracking = false;
      lb.addEventListener('touchstart', e => {
        if (e.touches.length !== 1 || e.target === lbVideo) { tracking = false; return; }
        tracking = true;
        tx = e.touches[0].clientX;
        ty = e.touches[0].clientY;
      }, { passive: true });
      lb.addEventListener('touchend', e => {
        if (!tracking) return;
        tracking = false;
        const t = e.changedTouches[0];
        const dx = t.clientX - tx;
        const dy = t.clientY - ty;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.3) {
          showMedia(index + (dx < 0 ? 1 : -1));
        }
      }, { passive: true });

      // Клавиатура (если подключена, например на планшете)
      document.addEventListener('keydown', e => {
        if (!lb.classList.contains('active')) return;
        if (e.key === 'ArrowLeft') lbPrev.click();
        else if (e.key === 'ArrowRight') lbNext.click();
        else if (e.key === 'Escape') closeLightbox();
      });

      // ================= Карта 2ГИС =================
      // Карта — готовый iframe 2ГИС; атрибут loading="lazy" сам подгружает его, когда блок приближается к экрану.
      const mapBox = $('#mapBox');
      const mapShield = $('#mapShield');

      // «Щит» над картой: тап включает управление картой, тап вне карты — возвращает прокрутку страницы
      mapShield.addEventListener('click', () => mapShield.classList.add('off'));
      document.addEventListener('pointerdown', e => {
        if (!mapBox.contains(e.target)) mapShield.classList.remove('off');
      }, { passive: true });
    })();
  </script>
  <script>
    // ====================================================================
    // БРОНИРОВАНИЕ: модальное окно, календарь доступности, отправка заявки
    // ====================================================================
    // Блок не зависит от галереи, меню и шапки: свой масштаб видимости, свои обработчики,
    // наружу выставлен только window.KDBooking.open(). Код одинаков в index.php и mobile.php,
    // различается только CONFIG. Серверная часть общая: booking/api.php.
    //
    // Для переноса в отдельный файл: вынесите всё, что находится внутри этого блока, в booking.js
    // и подключите файл через тег script с атрибутом defer в обеих страницах (значение
    // CONFIG.page задайте через data-атрибут или глобальную переменную).
    //
    // Структура:
    //   1. Настройки и тексты        5. Календарь: выбор дат и подсказки
    //   2. Работа с датами           6. Проверка формы (как на сервере)
    //   3. Связь с сервером          7. Отправка заявки
    //   4. Календарь: отрисовка      8. Открытие/закрытие окна
    (function () {
      'use strict';

      // ---------- 1. Настройки и тексты ----------
      const CONFIG = {
        page: 'mobile',            // 'desktop' в index.php, 'mobile' в mobile.php
        api: 'booking/api.php',      // общий серверный обработчик
        privacyUrl: 'privacy.html',  // страница политики обработки персональных данных
        useHistory: true,     // true: кнопка «Назад» на телефоне закрывает окно
        timeoutMs: 20000,
      };

      const MONTHS = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
      const MONTHS_GEN = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
      const SUCCESS_TEXT = 'Ваша заявка на бронирование отправлена! Хозяин гостевого комплекса свяжется с вами по телефону для уточнения деталей. Бронирование будет подтверждено после согласования с хозяином.';
      const OWNER_PHONE = '+7 917 172-55-58';

      const byId = id => document.getElementById(id);
      const modal = byId('bkModal');
      if (!modal) return;

      const dialog = byId('bkDialog');
      const grid = byId('bkGrid');
      const monthEl = byId('bkMonth');
      const prevBtn = byId('bkPrev');
      const nextBtn = byId('bkNext');
      const calState = byId('bkCalState');
      const summaryEl = byId('bkSummary');
      const msgEl = byId('bkMsg');
      const clearBtn = byId('bkClearDates');
      const form = byId('bkForm');
      const submitBtn = byId('bkSubmit');
      const submitLabel = submitBtn.querySelector('.bk-submit-label');
      const formMsg = byId('bkFormMsg');
      const mainView = byId('bkMain');
      const doneView = byId('bkDone');

      const state = {
        open: false,
        loaded: false,          // календарь хотя бы раз получен с сервера
        loading: false,
        today: '', maxDate: '', maxNights: 60,
        busy: new Set(),        // занятые ночи (YYYY-MM-DD)
        periods: [],            // занятые периоды {checkIn, checkOut} — для подсказок
        token: '',              // подписанный токен формы от сервера
        month: null,            // {y, m}
        checkIn: null, checkOut: null, hover: null, focusIso: null,
        sending: false, histPushed: false,
      };
      const cells = new Map();  // дата -> кнопка в сетке текущего месяца
      let lastFocus = null, closeTimer = 0, downOnOverlay = false;

      // ---------- 2. Работа с датами ----------
      // Даты — только строки YYYY-MM-DD. Арифметика идёт в UTC, поэтому часовой пояс
      // браузера не может сдвинуть день. new Date('YYYY-MM-DD') нигде не используется.
      const D = {
        parse(iso) {
          const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
          if (!m) return null;
          const y = +m[1], mo = +m[2], d = +m[3];
          const t = new Date(Date.UTC(y, mo - 1, d));
          if (t.getUTCFullYear() !== y || t.getUTCMonth() !== mo - 1 || t.getUTCDate() !== d) return null;
          return { y: y, m: mo, d: d };
        },
        iso(y, m, d) { return String(y).padStart(4, '0') + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0'); },
        toT(iso) { const p = D.parse(iso); return Date.UTC(p.y, p.m - 1, p.d); },
        fromT(t) { const d = new Date(t); return D.iso(d.getUTCFullYear(), d.getUTCMonth() + 1, d.getUTCDate()); },
        add(iso, n) { return D.fromT(D.toT(iso) + n * 86400000); },
        diff(a, b) { return Math.round((D.toT(b) - D.toT(a)) / 86400000); },
        dow(iso) { return (new Date(D.toT(iso)).getUTCDay() + 6) % 7; },      // понедельник = 0
        dim(y, m) { return new Date(Date.UTC(y, m, 0)).getUTCDate(); },
        dm(iso) { const p = D.parse(iso); return p.d + ' ' + MONTHS_GEN[p.m - 1]; },
        long(iso) { const p = D.parse(iso); return p.d + ' ' + MONTHS_GEN[p.m - 1] + ' ' + p.y; },
        monthIndex(iso) { const p = D.parse(iso); return p.y * 12 + p.m - 1; },
      };

      function nightsWord(n) {
        const a = Math.abs(n) % 100, d = a % 10;
        if (a > 10 && a < 20) return 'ночей';
        return d === 1 ? 'ночь' : (d >= 2 && d <= 4 ? 'ночи' : 'ночей');
      }

      /** «с 17 по 20 июня» / «с 28 июня по 3 июля» */
      function periodText(a, b) {
        const pa = D.parse(a), pb = D.parse(b);
        if (pa.y === pb.y && pa.m === pb.m) return 'с ' + pa.d + ' по ' + pb.d + ' ' + MONTHS_GEN[pb.m - 1];
        return 'с ' + D.dm(a) + ' по ' + D.dm(b);
      }

      /** Первая занятая ночь в периоде [a, b) или null. */
      function firstBusyNight(a, b) {
        for (let d = a; d < b; d = D.add(d, 1)) if (state.busy.has(d)) return d;
        return null;
      }

      function periodWithNight(night) {
        return state.periods.find(p => p.checkIn <= night && night < p.checkOut) || null;
      }

      // ---------- 3. Связь с сервером ----------
      class NetError extends Error { }

      async function requestJSON(url, options, timeoutMs) {
        const ctrl = typeof AbortController === 'function' ? new AbortController() : null;
        const timer = ctrl ? setTimeout(() => ctrl.abort(), timeoutMs || CONFIG.timeoutMs) : 0;
        let res;
        try {
          res = await fetch(url, Object.assign({ cache: 'no-store', credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined }, options));
        } catch (e) {
          throw new NetError('network');
        } finally {
          clearTimeout(timer);
        }
        let json;
        try { json = await res.json(); } catch (e) { throw new NetError('bad-response'); }
        if (!json || typeof json.ok !== 'boolean') throw new NetError('bad-response');
        return json;
      }

      async function loadCalendar() {
        if (state.loading) return;
        state.loading = true;
        setCalState(state.loaded ? 'refreshing' : 'loading');
        try {
          const json = await requestJSON(CONFIG.api + '?action=calendar&_=' + Date.now(), { method: 'GET' });
          if (!json.ok) throw new NetError('server');
          applyCalendar(json.data);
          setCalState('ok');
        } catch (e) {
          setCalState('error');
        } finally {
          state.loading = false;
        }
      }

      function applyCalendar(data) {
        state.today = data.today;
        state.maxDate = data.maxDate;
        state.maxNights = data.maxNights || 60;
        state.token = data.formToken || '';
        state.periods = (data.bookings || []).filter(p => D.parse(p.checkIn) && D.parse(p.checkOut));
        state.busy = new Set();
        state.periods.forEach(p => { for (let d = p.checkIn; d < p.checkOut; d = D.add(d, 1)) state.busy.add(d); });
        state.loaded = true;

        // Выбранные даты могли устареть (другой человек уже занял их, или сменился день)
        if (state.checkIn && (state.checkIn < state.today || state.busy.has(state.checkIn))) {
          state.checkIn = state.checkOut = null;
          showMsg('Выбранные даты стали недоступны. Календарь обновлён — выберите даты заново.', 'error');
        } else if (state.checkIn && state.checkOut && firstBusyNight(state.checkIn, state.checkOut)) {
          state.checkIn = state.checkOut = null;
          showMsg('Выбранные даты уже заняты. Календарь обновлён — выберите даты заново.', 'error');
        }
        const start = D.parse(state.checkIn || state.today);
        const idx = start.y * 12 + start.m - 1;
        if (!state.month || state.month.y * 12 + state.month.m - 1 < D.monthIndex(state.today)) {
          state.month = { y: Math.floor(idx / 12), m: (idx % 12) + 1 };
        }
        buildGrid();
        updateSummary();
      }

      function setCalState(kind) {
        calState.className = 'bk-calstate' + (kind === 'ok' ? '' : ' is-' + kind);
        calState.textContent = '';
        grid.setAttribute('aria-busy', kind === 'loading' || kind === 'refreshing' ? 'true' : 'false');
        if (kind === 'loading') {
          calState.textContent = 'Загружаем календарь…';
        } else if (kind === 'error') {
          const text = document.createElement('span');
          text.textContent = state.loaded
            ? 'Не удалось обновить календарь: возможно, он устарел. '
            : 'Не удалось загрузить календарь. Проверьте интернет или позвоните: ' + OWNER_PHONE + '. ';
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'bk-link';
          btn.textContent = 'Повторить';
          btn.addEventListener('click', loadCalendar);
          calState.append(text, btn);
        }
        grid.classList.toggle('is-hidden', !state.loaded);
        monthEl.textContent = state.loaded && state.month ? MONTHS[state.month.m - 1] + ' ' + state.month.y : '';
        updateNav();
      }

      // ---------- 4. Календарь: отрисовка ----------
      function updateNav() {
        if (!state.loaded || !state.month) { prevBtn.disabled = nextBtn.disabled = true; return; }
        const idx = state.month.y * 12 + state.month.m - 1;
        prevBtn.disabled = idx <= D.monthIndex(state.today);
        nextBtn.disabled = idx >= D.monthIndex(state.maxDate);
      }

      function buildGrid() {
        if (!state.loaded || !state.month) return;
        const y = state.month.y, m = state.month.m;
        monthEl.textContent = MONTHS[m - 1] + ' ' + y;
        grid.replaceChildren();
        cells.clear();
        const lead = D.dow(D.iso(y, m, 1)), dim = D.dim(y, m);
        const frag = document.createDocumentFragment();
        for (let i = 0; i < lead; i++) {
          const pad = document.createElement('span');
          pad.className = 'bk-pad';
          frag.append(pad);
        }
        for (let d = 1; d <= dim; d++) {
          const iso = D.iso(y, m, d);
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'bk-day';
          b.textContent = String(d);
          b.dataset.date = iso;
          cells.set(iso, b);
          frag.append(b);
        }
        grid.append(frag);
        updateNav();
        paint();
      }

      /** Только расставляет классы существующим кнопкам (без пересборки), поэтому клики не теряются. */
      function paint() {
        if (!cells.size) return;
        const a = state.checkIn, b = state.checkOut;
        let end = b;
        if (a && !b && state.hover && state.hover > a && !firstBusyNight(a, state.hover) && D.diff(a, state.hover) <= state.maxNights) {
          end = state.hover;     // предпросмотр выбранного диапазона при наведении
        }
        const choosingEnd = !!(a && !b);
        const rovingIso = cells.has(state.focusIso) ? state.focusIso : (cells.has(a) ? a : null);
        let roving = rovingIso;
        if (!roving) {
          for (const [iso] of cells) { if (iso >= state.today && !state.busy.has(iso)) { roving = iso; break; } }
          if (!roving) roving = cells.keys().next().value;
        }

        cells.forEach((el, iso) => {
          const past = iso < state.today, far = iso > state.maxDate;
          const night = state.busy.has(iso);
          const prevNight = state.busy.has(D.add(iso, -1));
          let cls = 'bk-day';
          if (past) cls += ' is-past';
          else if (far) cls += ' is-far';
          if (iso === state.today) cls += ' is-today';
          if (night) cls += ' is-busy' + (!prevNight ? ' is-busy-start' : '');
          else if (prevNight) cls += ' is-free-end';
          if (a && end && iso > a && iso < end) cls += ' is-range';
          if (iso === a) cls += ' is-start';
          if (end && iso === end) cls += ' is-end';

          // Занятая ночь не выбирается как заезд; день заезда следующих гостей можно выбрать как выезд
          const asCheckout = choosingEnd && iso > a && night && !prevNight;
          const disabled = past || far || (night && !asCheckout);
          el.className = cls;
          el.setAttribute('aria-disabled', disabled ? 'true' : 'false');
          el.setAttribute('aria-pressed', (iso === a || iso === b) ? 'true' : 'false');
          el.tabIndex = iso === roving ? 0 : -1;

          let label = D.long(iso);
          if (past) label += ', прошедшая дата';
          else if (far) label += ', слишком далеко';
          else if (night) label += asCheckout ? ', занято; можно выбрать как день выезда' : ', занято';
          else label += prevNight ? ', свободно, день выезда прежних гостей' : ', свободно';
          if (iso === a) label += ', дата заезда';
          if (iso === b) label += ', дата выезда';
          el.setAttribute('aria-label', label);
        });
      }

      function shiftMonth(k) {
        if (!state.loaded) return;
        const idx = state.month.y * 12 + state.month.m - 1 + k;
        if (idx < D.monthIndex(state.today) || idx > D.monthIndex(state.maxDate)) return;
        state.month = { y: Math.floor(idx / 12), m: (idx % 12) + 1 };
        state.hover = null;
        buildGrid();
        animateGrid(k);
      }

      // ---------- 5. Календарь: выбор дат и подсказки ----------
      function showMsg(text, kind) {
        msgEl.hidden = !text;
        msgEl.textContent = text || '';
        msgEl.className = 'bk-msg' + (kind ? ' is-' + kind : '');
      }

      function onDayClick(iso) {
        showMsg('');
        setFieldError('dates', '');
        const a = state.checkIn, b = state.checkOut;
        state.focusIso = iso;

        if (iso < state.today) { showMsg('Прошедшие даты выбрать нельзя.', 'info'); return; }
        if (iso > state.maxDate) { showMsg('Так далеко бронь онлайн не принимается. Выберите более близкие даты или позвоните: ' + OWNER_PHONE + '.', 'info'); return; }

        // Второй клик: выбираем дату выезда
        if (a && !b && iso > a) {
          if (D.diff(a, iso) > state.maxNights) {
            showMsg('Онлайн-заявку можно оставить не более чем на ' + state.maxNights + ' ' + nightsWord(state.maxNights) + '. Выберите более ранний выезд или позвоните: ' + OWNER_PHONE + '.', 'error');
            return;
          }
          const bad = firstBusyNight(a, iso);
          if (bad) {
            const p = periodWithNight(bad);
            showMsg('В выбранный период входят занятые даты' + (p ? ' (дом занят ' + periodText(p.checkIn, p.checkOut) + ')' : '') + '. '
              + 'Выезд возможен не позже ' + D.dm(bad) + ', либо выберите другие даты заезда и выезда.', 'error');
            paint();
            return;
          }
          state.checkOut = iso;
          afterSelectionChange();
          return;
        }
        if (a && !b && iso === a) {
          showMsg('Дата выезда должна быть позже даты заезда: нужна хотя бы одна ночь. Выберите другой день выезда.', 'info');
          return;
        }

        // Первый клик, перевыбор или клик раньше заезда: новая дата заезда
        if (state.busy.has(iso)) {
          const p = periodWithNight(iso);
          showMsg('Эта ночь уже занята' + (p ? ' (дом занят ' + periodText(p.checkIn, p.checkOut) + ')' : '') + '. Выберите свободную дату заезда.', 'error');
          return;
        }
        state.checkIn = iso;
        state.checkOut = null;
        afterSelectionChange();
      }

      function afterSelectionChange() {
        state.hover = null;
        updateSummary();
        paint();
      }

      function updateSummary() {
        const a = state.checkIn, b = state.checkOut;
        clearBtn.hidden = !a;
        if (!a) {
          summaryEl.textContent = 'Выберите дату заезда.';
        } else if (!b) {
          summaryEl.textContent = 'Заезд: ' + D.long(a) + '. Теперь выберите дату выезда.';
        } else {
          const n = D.diff(a, b);
          summaryEl.textContent = 'Заезд ' + D.long(a) + ', выезд ' + D.long(b) + ': ' + n + ' ' + nightsWord(n) + '.';
        }
      }

      function clearDates() {
        state.checkIn = state.checkOut = state.hover = null;
        showMsg('');
        updateSummary();
        paint();
      }

      grid.addEventListener('click', e => {
        const el = e.target.closest('.bk-day');
        if (el) onDayClick(el.dataset.date);
      });
      grid.addEventListener('mouseover', e => {
        if (!state.checkIn || state.checkOut) return;
        const el = e.target.closest('.bk-day');
        const iso = el ? el.dataset.date : null;
        if (iso !== state.hover) { state.hover = iso; paint(); }
      });
      grid.addEventListener('mouseleave', () => {
        if (state.hover) { state.hover = null; paint(); }
      });
      // Клавиатура: стрелки двигают фокус по дням, Enter/пробел выбирают
      grid.addEventListener('keydown', e => {
        const el = e.target.closest('.bk-day');
        if (!el) return;
        const step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
        if (!step) return;
        e.preventDefault();
        const target = D.add(el.dataset.date, step);
        const p = D.parse(target);
        if (!state.month || p.y !== state.month.y || p.m !== state.month.m) {
          const idx = p.y * 12 + p.m - 1;
          if (idx < D.monthIndex(state.today) || idx > D.monthIndex(state.maxDate)) return;
          state.month = { y: p.y, m: p.m };
          state.focusIso = target;
          buildGrid();
        } else {
          state.focusIso = target;
          paint();
        }
        const next = cells.get(target);
        if (next) next.focus();
      });
      prevBtn.addEventListener('click', () => shiftMonth(-1));
      nextBtn.addEventListener('click', () => shiftMonth(1));
      clearBtn.addEventListener('click', clearDates);

      // ---------- Свайпы (мобильная версия) ----------
      // Календарь: свайп влево — следующий месяц, вправо — предыдущий (стрелки по-прежнему работают).
      // Окно: свайп вниз закрывает его, если содержимое прокручено до самого верха.

      /** Короткая анимация смены месяца: новый месяц «заезжает» со стороны жеста. */
      function animateGrid(k) {
        grid.classList.remove('is-slide-next', 'is-slide-prev');
        void grid.offsetWidth;
        grid.classList.add(k > 0 ? 'is-slide-next' : 'is-slide-prev');
      }

      (function bindCalendarSwipe() {
        const area = grid.closest('.bk-cal') || grid;
        let x0 = 0, y0 = 0, t0 = 0, active = false;
        area.addEventListener('touchstart', e => {
          active = e.touches.length === 1;
          if (!active) return;
          x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; t0 = Date.now();
        }, { passive: true });
        area.addEventListener('touchend', e => {
          if (!active) return;
          active = false;
          const t = e.changedTouches[0];
          const dx = t.clientX - x0, dy = t.clientY - y0;
          if (Date.now() - t0 > 800) return;                                    // медленное движение — не свайп
          if (Math.abs(dx) < 48 || Math.abs(dx) < Math.abs(dy) * 1.5) return;   // нужен заметный горизонтальный жест
          shiftMonth(dx < 0 ? 1 : -1);
        }, { passive: true });
        area.addEventListener('touchcancel', () => { active = false; }, { passive: true });
      })();

      /** Убирает временные стили, которые ставит перетаскивание шторки. */
      function resetSheetDrag() {
        dialog.style.transition = '';
        dialog.style.transform = '';
        modal.style.opacity = '';
      }

      (function bindSheetSwipe() {
        const scroller = dialog.querySelector('.bk-scroll');
        let x0 = 0, y0 = 0, t0 = 0, dy = 0, canStart = false, decided = false, dragging = false;

        dialog.addEventListener('touchstart', e => {
          if (!state.open || e.touches.length !== 1) { canStart = false; return; }
          x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; t0 = Date.now();
          dy = 0; decided = false; dragging = false;
          canStart = scroller.scrollTop <= 0;                    // жест работает, только если окно прокручено до самого верха
        }, { passive: true });

        dialog.addEventListener('touchmove', e => {
          if (!canStart) return;
          const t = e.touches[0];
          const mx = t.clientX - x0, my = t.clientY - y0;
          if (!decided) {
            if (Math.abs(mx) < 8 && Math.abs(my) < 8) return;
            decided = true;
            dragging = my > 0 && my > Math.abs(mx) * 1.2 && scroller.scrollTop <= 0;
          }
          if (!dragging) return;
          if (e.cancelable) e.preventDefault();                  // страница под окном не «тянется» и не обновляется
          dy = Math.max(0, my);
          dialog.style.transition = 'none';
          dialog.style.transform = 'translateY(' + dy + 'px)';
          modal.style.opacity = String(1 - Math.min(0.6, dy / 500));
        }, { passive: false });

        function finish() {
          if (!dragging) { canStart = false; return; }
          dragging = false;
          canStart = false;
          const fast = dy / Math.max(1, Date.now() - t0) > 0.6;  // быстрый бросок тоже закрывает окно
          dialog.style.transition = 'transform 0.2s ease';
          if (dy > 110 || (dy > 50 && fast)) {
            dialog.style.transform = 'translateY(100%)';
            modal.style.opacity = '0';
            closeModal();
            setTimeout(resetSheetDrag, 320);                     // временные стили убираем, когда окно уже скрыто
          } else {
            dialog.style.transform = '';
            modal.style.opacity = '';
            setTimeout(resetSheetDrag, 220);
          }
        }
        dialog.addEventListener('touchend', finish);
        dialog.addEventListener('touchcancel', finish);
      })();

      // ---------- 6. Проверка формы (те же правила, что на сервере) ----------
      const NAME_RE = /^[\p{L}][\p{L}\p{M} .'’-]{0,49}$/u;
      const FIELDS = ['firstName', 'lastName', 'patronymic', 'phone', 'consent', 'dates'];

      function cleanLine(s) { return String(s || '').replace(/\s+/g, ' ').trim(); }

      /** +7XXXXXXXXXX (или +код для других стран); null — номер некорректен. */
      function normalizePhone(raw) {
        raw = String(raw || '').trim();
        if (!raw || raw.length > 30 || /[^\d\s()+\-.]/.test(raw)) return null;
        const plus = raw.charAt(0) === '+';
        const d = raw.replace(/\D+/g, '');
        if (!d) return null;
        let num;
        if (plus) {
          if (d.length < 8 || d.length > 15) return null;
          if (d.charAt(0) === '7' && d.length !== 11) return null;
          num = '+' + d;
        } else if (d.length === 11 && (d.charAt(0) === '8' || d.charAt(0) === '7')) {
          num = '+7' + d.slice(1);
        } else if (d.length === 10 && /^[3-9]/.test(d)) {
          num = '+7' + d;
        } else {
          return null;
        }
        if (/^(\d)\1+$/.test(num.slice(1))) return null;
        if (num.indexOf('+7') === 0 && !/^\+7[3-9]/.test(num)) return null;
        return num;
      }

      function prettyPhone(num) {
        const m = /^\+7(\d{3})(\d{3})(\d{2})(\d{2})$/.exec(num);
        return m ? '+7 ' + m[1] + ' ' + m[2] + '-' + m[3] + '-' + m[4] : num;
      }

      function setFieldError(name, text) {
        const err = byId('bkErr-' + name);
        if (err) err.textContent = text || '';
        const input = form.elements[name];
        if (input && input.setAttribute) {
          if (text) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
        }
      }

      function validate() {
        const errors = {};
        const v = {
          firstName: cleanLine(form.elements.firstName.value),
          lastName: cleanLine(form.elements.lastName.value),
          patronymic: cleanLine(form.elements.patronymic.value),
          phone: /^\+7[\s(]*$/.test(cleanLine(form.elements.phone.value)) ? '' : cleanLine(form.elements.phone.value),   // «+7 (» без цифр — поле пустое
          comment: String(form.elements.comment.value || '').replace(/\r\n?/g, '\n').trim(),
        };
        if (!v.firstName) errors.firstName = 'Укажите имя.';
        else if (!NAME_RE.test(v.firstName)) errors.firstName = 'Допустимы только буквы, пробел, дефис и апостроф.';
        if (v.lastName && !NAME_RE.test(v.lastName)) errors.lastName = 'Допустимы только буквы, пробел, дефис и апостроф.';
        if (v.patronymic && !NAME_RE.test(v.patronymic)) errors.patronymic = 'Допустимы только буквы, пробел, дефис и апостроф.';

        if (!v.phone) errors.phone = 'Укажите номер телефона, чтобы хозяин мог вам перезвонить.';
        else if (!normalizePhone(v.phone)) errors.phone = 'Проверьте номер телефона. Пример: +7 (917) 123-45-67.';

        if (!form.elements.consent.checked) errors.consent = 'Необходимо согласие на обработку персональных данных.';

        if (!state.checkIn) errors.dates = 'Выберите даты заезда и выезда в календаре.';
        else if (!state.checkOut) errors.dates = 'Выберите дату выезда в календаре.';
        return { errors: errors, values: v };
      }

      function showErrors(errors) {
        FIELDS.forEach(f => setFieldError(f, errors[f] || ''));
      }

      function focusFirstError(errors) {
        if (errors.dates && !errors.firstName && !errors.phone) {
          summaryEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
          return;
        }
        for (const f of ['firstName', 'lastName', 'patronymic', 'phone', 'consent']) {
          if (errors[f]) { form.elements[f].focus(); return; }
        }
      }

      /**
       * Маска телефона «+7 (917) 123-45-67». «+7» ставится сам и не стирается; вставка и автозаполнение
       * («8 917…», «+7 917…», «7917…», «+7+7 917…») превращаются в один и тот же номер; цифры разделены
       * скобками и дефисами. В поле всегда лежит текст в этом виде, сервер принимает его как есть.
       */
      function attachPhoneMask(input) {
        const PREFIX_LEN = 4;                                     // «+7 (» — столько символов занимает начало
        const digits = s => String(s).replace(/\D/g, '');

        /** Цифры номера без кода страны (не больше 10) из того, что оказалось в поле. */
        function parse(raw) {
          raw = String(raw || '').trim();
          let d;
          if (/^\+\s*7/.test(raw)) d = digits(raw.replace(/^\+\s*7/, ''));
          else if (/^7[\s(]/.test(raw)) d = digits(raw.slice(1));                    // стёрли «+»
          else d = digits(raw);
          while (d.length > 10 && /^[78]/.test(d)) d = d.slice(1);                  // лишний код страны: 8…, 7…, «+7+7…»
          return d.slice(0, 10);
        }

        function format(d) {
          let s = '+7 (' + d.slice(0, 3);
          if (d.length > 3) s += ') ' + d.slice(3, 6);
          if (d.length > 6) s += '-' + d.slice(6, 8);
          if (d.length > 8) s += '-' + d.slice(8, 10);
          return s;
        }

        /** Позиция курсора сразу после count-й цифры номера. */
        function caretAfter(text, count) {
          if (count <= 0) return Math.min(text.length, PREFIX_LEN);
          let seen = 0;
          for (let i = PREFIX_LEN; i < text.length; i++) {
            if (/\d/.test(text[i]) && ++seen === count) return i + 1;
          }
          return text.length;
        }

        function put(d, caretDigits) {
          const text = format(d);
          if (input.value !== text) input.value = text;
          const p = caretAfter(text, caretDigits);
          try { input.setSelectionRange(p, p); } catch (e) { /* поле не в фокусе */ }
        }

        input.addEventListener('input', () => {
          const raw = input.value;
          const pos = input.selectionStart == null ? raw.length : input.selectionStart;
          const all = parse(raw);
          put(all, Math.min(parse(raw.slice(0, pos)).length, all.length));
        });

        // Вставка: «+7+7 000…», «8 000…» и т. п. не должны давать двойной код страны
        input.addEventListener('paste', e => {
          e.preventDefault();
          const text = (e.clipboardData || window.clipboardData).getData('text') || '';
          let p = digits(text);
          while (p.length > 10 && /^[78]/.test(p)) p = p.slice(1);                // «+7+7…», «8…», «7…» — без лишнего кода
          p = p.slice(0, 10);
          const cur = parse(input.value);
          const from = parse(input.value.slice(0, input.selectionStart || 0)).length;
          const to = parse(input.value.slice(0, input.selectionEnd || 0)).length;
          const merged = (p.length >= 10 ? p : cur.slice(0, from) + p + cur.slice(to)).slice(0, 10);
          put(merged, p.length >= 10 ? merged.length : Math.min(from + p.length, merged.length));
          input.dispatchEvent(new Event('input', { bubbles: true }));
        });

        // Backspace и Delete перепрыгивают скобки и дефисы и никогда не трогают «+7 (»
        input.addEventListener('keydown', e => {
          if (e.ctrlKey || e.metaKey || e.altKey) return;
          const start = input.selectionStart, end = input.selectionEnd, v = input.value;
          if (e.key === 'Backspace' && start === end) {
            let p = start;
            while (p > PREFIX_LEN && !/\d/.test(v[p - 1])) p--;
            if (p <= PREFIX_LEN) { e.preventDefault(); input.setSelectionRange(PREFIX_LEN, PREFIX_LEN); return; }
            input.setSelectionRange(p, p);
          } else if (e.key === 'Delete' && start === end) {
            let p = Math.max(start, PREFIX_LEN);
            while (p < v.length && !/\d/.test(v[p])) p++;
            if (p >= v.length) { e.preventDefault(); return; }
            input.setSelectionRange(p, p);
          }
        });

        // При входе в поле сразу видно «+7 (», при выходе пустое поле очищается (чтобы сработала проверка «Укажите номер»)
        input.addEventListener('focus', () => {
          if (!parse(input.value).length) {
            input.value = '+7 (';
            setTimeout(() => { try { input.setSelectionRange(input.value.length, input.value.length); } catch (e) { } }, 0);
          }
        });
        input.addEventListener('blur', () => { if (!parse(input.value).length) input.value = ''; });

        // Автозаполнение браузера при загрузке страницы
        if (input.value) put(parse(input.value), 10);
      }

      // Подсказки на лету: убираем ошибку, как только поле исправлено
      FIELDS.slice(0, 5).forEach(name => {
        const input = form.elements[name];
        if (!input) return;
        input.addEventListener(name === 'consent' ? 'change' : 'input', () => setFieldError(name, ''));
      });
      attachPhoneMask(form.elements.phone);

      // ---------- 7. Отправка заявки ----------
      function showFormMsg(text, kind) {
        formMsg.hidden = !text;
        formMsg.textContent = text || '';
        formMsg.className = 'bk-msg' + (kind ? ' is-' + kind : '');
      }

      function setSending(on) {
        state.sending = on;
        submitBtn.disabled = on;
        submitBtn.classList.toggle('is-sending', on);
        submitBtn.setAttribute('aria-busy', on ? 'true' : 'false');
        submitLabel.textContent = on ? 'Отправляем…' : 'Отправить заявку';
      }

      function deviceInfo() {
        let tz = '';
        try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { }
        return {
          page: CONFIG.page,
          screen: Math.round(window.screen.width) + 'x' + Math.round(window.screen.height),
          lang: navigator.language || '',
          tz: tz,
          touch: (navigator.maxTouchPoints || 0) > 1,
        };
      }

      async function onSubmit(e) {
        e.preventDefault();
        if (state.sending) return;                       // защита от повторного нажатия
        showFormMsg('');
        const res = validate();
        showErrors(res.errors);
        if (Object.keys(res.errors).length) { focusFirstError(res.errors); return; }
        if (!state.token) {
          showFormMsg('Календарь не загружен, поэтому отправить заявку сейчас нельзя. Нажмите «Повторить» под календарём или позвоните: ' + OWNER_PHONE + '.', 'error');
          return;
        }

        setSending(true);
        try {
          const json = await requestJSON(CONFIG.api + '?action=submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              firstName: res.values.firstName, lastName: res.values.lastName, patronymic: res.values.patronymic,
              phone: res.values.phone, comment: res.values.comment,
              checkIn: state.checkIn, checkOut: state.checkOut,
              consent: true,
              website: form.elements.website.value,
              token: state.token,
              device: deviceInfo(),
            }),
          });
          if (json.ok) onSuccess(json.data);
          else await onServerError(json.error || {});
        } catch (err) {
          if (err instanceof NetError && err.message === 'bad-response') {
            showFormMsg('Сервер временно недоступен. Попробуйте чуть позже или позвоните: ' + OWNER_PHONE + '.', 'error');
          } else {
            showFormMsg('Не удалось получить ответ сервера. Проверьте интернет и нажмите «Отправить заявку» ещё раз: повторная отправка не создаст дубль. Или позвоните: ' + OWNER_PHONE + '.', 'error');
          }
        } finally {
          setSending(false);
        }
      }

      async function onServerError(err) {
        if (err.code === 'validation' && err.fields) {
          showErrors(err.fields);
          focusFirstError(err.fields);
          showFormMsg('Проверьте выделенные поля.', 'error');
        } else if (err.code === 'dates_unavailable') {
          // Пока человек заполнял форму, даты заняли: сбрасываем выбор и обновляем календарь
          state.checkIn = state.checkOut = null;
          showMsg('');
          await loadCalendar();
          updateSummary();
          paint();
          showFormMsg(err.message || 'Выбранные даты уже заняты. Календарь обновлён — выберите другие даты.', 'error');
          summaryEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
        } else if (err.code === 'form_expired') {
          await loadCalendar();
          showFormMsg('Форма обновлена. Нажмите «Отправить заявку» ещё раз.', 'info');
        } else {
          showFormMsg(err.message || 'Не удалось отправить заявку. Позвоните: ' + OWNER_PHONE + '.', 'error');
        }
      }

      function onSuccess(data) {
        form.reset();
        showErrors({});
        showFormMsg('');
        state.checkIn = state.checkOut = state.hover = null;
        updateSummary();
        paint();
        byId('bkDoneText').textContent = SUCCESS_TEXT;
        byId('bkDoneNum').textContent = data && data.requestNumber ? 'Номер заявки: ' + data.requestNumber : '';
        mainView.hidden = true;
        doneView.hidden = false;
        dialog.querySelector('.bk-scroll').scrollTop = 0;
        byId('bkDoneTitle').focus();
        loadCalendar();   // обновляем токен формы на случай новой заявки
      }

      form.addEventListener('submit', onSubmit);

      // ---------- 8. Открытие и закрытие окна ----------
      function lockScroll(on) {
        document.documentElement.classList.toggle('kd-lock', on);
      }

      function visibleFocusables() {
        return Array.from(dialog.querySelectorAll('button, input, textarea, a[href], [tabindex="0"]'))
          .filter(el => !el.disabled && el.tabIndex >= 0 && el.offsetParent !== null);
      }

      function openModal(opener) {
        if (state.open) return;
        state.open = true;
        lastFocus = opener || document.activeElement;
        clearTimeout(closeTimer);
        modal.hidden = false;
        resetSheetDrag();
        mainView.hidden = false;
        doneView.hidden = true;
        void modal.offsetWidth;                  // чтобы сработал переход появления
        modal.classList.add('is-open');
        lockScroll(true);
        if (CONFIG.useHistory) {
          try { history.pushState({ kdBooking: 1 }, ''); state.histPushed = true; } catch (e) { state.histPushed = false; }
        }
        loadCalendar();                           // каждый раз берём свежие данные с сервера
        dialog.focus();
      }

      function closeModal(fromPop) {
        if (!state.open) return;
        state.open = false;
        modal.classList.remove('is-open');
        closeTimer = setTimeout(() => { modal.hidden = true; }, 220);
        lockScroll(false);
        if (state.histPushed && !fromPop) { state.histPushed = false; history.back(); }
        state.histPushed = false;
        if (lastFocus && lastFocus.focus) lastFocus.focus();
      }

      // Закрытие: крестик, Esc, клик по затемнению (только если нажатие и отпускание — на нём,
      // чтобы выделение текста или перетаскивание не закрывали окно случайно)
      byId('bkClose').addEventListener('click', () => closeModal());
      byId('bkDoneClose').addEventListener('click', () => closeModal());
      modal.addEventListener('pointerdown', e => { downOnOverlay = (e.target === modal); });
      modal.addEventListener('click', e => { if (e.target === modal && downOnOverlay) closeModal(); downOnOverlay = false; });
      modal.addEventListener('keydown', e => {
        if (e.key === 'Escape') { e.preventDefault(); closeModal(); return; }
        if (e.key !== 'Tab') return;
        const list = visibleFocusables();
        if (!list.length) return;
        const first = list[0], last = list[list.length - 1];
        if (e.shiftKey && (document.activeElement === first || document.activeElement === dialog)) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      });
      window.addEventListener('popstate', () => {
        if (state.open) { state.histPushed = false; closeModal(true); }
      });

      // Кнопки «Забронировать»: любой элемент с атрибутом data-booking-open
      document.querySelectorAll('[data-booking-open]').forEach(btn => {
        btn.addEventListener('click', e => { e.preventDefault(); openModal(btn); });
      });
      const privacy = byId('bkPrivacy');
      if (privacy) privacy.setAttribute('href', CONFIG.privacyUrl);

      window.KDBooking = { open: () => openModal(document.activeElement) };
    })();
  </script>
  <script>
    // ====================================================================
    // СТАТИСТИКА ПОСЕЩЕНИЙ (без сторонних сервисов; данные хранятся на этом же сайте)
    // ====================================================================
    // Что делает: даёт устройству случайный ID, определяет «новый визит» (прошло больше 30 минут
    // с последней активности) и отправляет его в booking/track.php вместе с UTM-метками, адресом
    // источника, типом устройства и шириной экрана. Клики по ключевым кнопкам (телефон, бронирование,
    // мессенджеры) отправляются отдельно и только увеличивают счётчик у уже известного посетителя.
    // Якоря, обновление страницы и переходы между index.php и mobile.php визит не увеличивают.
    (function () {
      'use strict';

      const ENDPOINT = 'booking/track.php';
      const VISIT_GAP_MS = 30 * 60 * 1000;     // пауза, после которой заход считается новым визитом
      const COOKIE_DAYS = 3 * 365;
      const K_ID = 'kd_did';                   // ID устройства (localStorage и cookie)
      const K_LAST = 'kd_last';                // время последней активности (localStorage, общее для вкладок и версий сайта)
      const UUID_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/;

      const ls = {
        get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set(k, v) { try { localStorage.setItem(k, v); } catch (e) { } },
      };

      function uuid() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        const b = new Uint8Array(16);
        if (window.crypto && crypto.getRandomValues) crypto.getRandomValues(b);
        else for (let i = 0; i < 16; i++) b[i] = Math.random() * 256 | 0;
        b[6] = (b[6] & 15) | 64;
        b[8] = (b[8] & 63) | 128;
        const h = Array.from(b, x => x.toString(16).padStart(2, '0')).join('');
        return h.slice(0, 8) + '-' + h.slice(8, 12) + '-' + h.slice(12, 16) + '-' + h.slice(16, 20) + '-' + h.slice(20);
      }

      function readCookie() {
        const m = document.cookie.match(/(?:^|;\s*)kd_did=([^;]+)/);
        return m ? m[1] : '';
      }

      /** ID из localStorage; если его там нет — из cookie; если нет нигде — новый. Всегда дублируется в cookie на 3 года. */
      function deviceId() {
        let id = ls.get(K_ID) || '';
        if (!UUID_RE.test(id)) id = readCookie();
        if (!UUID_RE.test(id)) id = uuid();
        ls.set(K_ID, id);
        document.cookie = K_ID + '=' + id + '; max-age=' + (COOKIE_DAYS * 86400) + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        return id;
      }

      const id = deviceId();
      const coarse = !!(window.matchMedia && matchMedia('(pointer: coarse)').matches);
      const isMobile = /Android.+Mobile|iPhone|iPod|IEMobile|Windows Phone|Opera Mini|Mobi/i.test(navigator.userAgent)
        || (coarse && Math.min(screen.width, screen.height) <= 900);

      function send(payload) {
        const body = JSON.stringify(payload);
        try {
          if (navigator.sendBeacon && navigator.sendBeacon(ENDPOINT, new Blob([body], { type: 'application/json' }))) return;
        } catch (e) { }
        try {
          fetch(ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true, credentials: 'same-origin' }).catch(function () { });
        } catch (e) { }
      }

      /** firstLoad = true: заход с загрузкой страницы (есть UTM и referrer). Иначе возврат на давно открытую вкладку: источник неизвестен. */
      function sendVisit(firstLoad) {
        const p = { type: 'visit', id: id, device: isMobile ? 'mobile' : 'desktop', width: Math.round(screen.width || window.innerWidth || 0) };
        if (firstLoad) {
          const q = new URLSearchParams(location.search);
          ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'].forEach(function (k) { p[k] = (q.get(k) || '').slice(0, 100); });
          let ref = '';
          try { ref = sessionStorage.getItem('kd_ref0') || ''; sessionStorage.removeItem('kd_ref0'); } catch (e) { }   // referrer, сохранённый до редиректа на мобильную версию
          p.referrer = ref || document.referrer || '';
        }
        send(p);
      }

      /** Отмечает активность. Если с прошлой активности прошло больше 30 минут — это новый визит. */
      function touch(firstLoad) {
        const now = Date.now();
        const last = parseInt(ls.get(K_LAST) || '0', 10) || 0;
        const isNewVisit = !last || now - last > VISIT_GAP_MS;
        ls.set(K_LAST, String(now));            // сначала фиксируем время, чтобы вторая вкладка не продублировала визит
        if (isNewVisit) sendVisit(firstLoad);
      }

      touch(true);

      // Дальше активность только продлевает визит (не чаще раза в 20 секунд)
      let lastTouch = Date.now();
      function onActivity() {
        const now = Date.now();
        if (now - lastTouch < 20000) return;
        lastTouch = now;
        touch(false);
      }
      ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach(function (ev) {
        window.addEventListener(ev, onActivity, { passive: true, capture: true });
      });
      document.addEventListener('visibilitychange', function () { if (!document.hidden) onActivity(); });

      // ---------- клики по ключевым кнопкам ----------
      const lastClick = {};

      /** Название кнопки: явное data-track, иначе по типу ссылки. Пустая строка — кнопка не отслеживается. */
      function buttonName(el) {
        const t = el.closest('[data-track]');
        if (t) return t.getAttribute('data-track') || '';
        const a = el.closest('a[href]');
        if (a) {
          const h = a.getAttribute('href') || '';
          if (/^tel:/i.test(h)) return 'phone';
          if (/wa\.me|whatsapp/i.test(h)) return 'whatsapp';
          if (/t\.me|telegram/i.test(h)) return 'telegram';
          if (/max\.ru/i.test(h)) return 'max';
          if (/vk\.(com|ru)/i.test(h)) return 'vk';
        }
        return el.closest('[data-booking-open]') ? 'booking' : '';
      }

      document.addEventListener('click', function (e) {
        const name = e.target && e.target.closest ? buttonName(e.target) : '';
        if (!/^[a-z0-9_]{1,32}$/.test(name)) return;
        const now = Date.now();
        if (lastClick[name] && now - lastClick[name] < 1500) return;   // двойные нажатия не считаем
        lastClick[name] = now;
        touch(false);
        send({ type: 'click', id: id, button: name });
      }, true);
    })();
  </script>
  <script>
    // ====================================================================
    // ОТЗЫВЫ: отправка формы «Оставить отзыв» (с фото и видео)
    // ====================================================================
    // Отзыв уходит на сервер (booking/api.php?action=review), хозяин получает сообщение в Telegram
    // и сам решает, публиковать его на сайте или нет. До публикации отзыв на странице не появляется.
    // Фото и видео загружаются по одному (action=reviewUpload) в момент отправки, затем уходит сам отзыв
    // со списком загруженных файлов. Большие фото перед загрузкой сжимаются в браузере.
    (function () {
      'use strict';

      const API = 'booking/api.php';
      const PAGE = 'mobile';               // 'desktop' в index.php, 'mobile' в mobile.php
      const form = document.getElementById('reviewForm');
      if (!form) return;

      const msg = document.getElementById('reviewMsg');
      const btn = form.querySelector('.review_submit');
      const NAME_RE = /^[\p{L}][\p{L}\p{M} .'’-]{0,49}$/u;
      let token = '', tokenAt = 0, loadingToken = null, sending = false;

      // ---------- вложения ----------
      const fileInput = document.getElementById('reviewFiles');
      const attachBtn = document.getElementById('reviewAttach');
      const fileList = document.getElementById('reviewFileList');
      const attachHint = document.getElementById('reviewAttachHint');
      let limits = { photos: 6, videos: 1, photoBytes: 10 * 1048576, videoBytes: 50 * 1048576 };   // уточняются ответом сервера
      const picked = [];                      // { file, kind: 'photo' | 'video', preview, uploadId }

      const mb = n => n < 1048576 ? Math.max(1, Math.round(n / 1024)) + ' КБ' : (n / 1048576).toFixed(n >= 10485760 ? 0 : 1).replace('.', ',') + ' МБ';

      function kindOf(f) {
        if (/^image\/(jpeg|png|webp)$/.test(f.type)) return 'photo';
        if (/^video\/(mp4|webm)$/.test(f.type) || /\.(mp4|webm)$/i.test(f.name)) return 'video';
        return '';
      }

      function updateHint() {
        if (attachHint) attachHint.textContent = 'до ' + limits.photos + ' фото и ' + limits.videos + ' видео (видео до ' + mb(limits.videoBytes) + ')';
      }

      function renderFiles() {
        if (!fileList) return;
        fileList.replaceChildren(...picked.map((it, i) => {
          const li = document.createElement('li');
          li.className = 'review_file_item';
          const thumb = document.createElement(it.preview ? 'img' : 'span');
          thumb.className = 'review_file_thumb' + (it.preview ? '' : ' is-video');
          if (it.preview) { thumb.src = it.preview; thumb.alt = ''; } else { thumb.textContent = '▶'; }
          const name = document.createElement('span');
          name.className = 'review_file_name';
          name.textContent = it.file.name + ' (' + mb(it.file.size) + ')';
          const del = document.createElement('button');
          del.type = 'button';
          del.className = 'review_file_del';
          del.setAttribute('aria-label', 'Убрать файл ' + it.file.name);
          del.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>';
          del.addEventListener('click', () => {
            if (it.preview) URL.revokeObjectURL(it.preview);
            picked.splice(picked.indexOf(it), 1);
            renderFiles();
          });
          li.append(thumb, name, del);
          return li;
        }));
      }

      function addFiles(files) {
        const problems = [];
        Array.from(files).forEach(f => {
          const kind = kindOf(f);
          const photos = picked.filter(p => p.kind === 'photo').length;
          const videos = picked.filter(p => p.kind === 'video').length;
          if (!kind) problems.push('«' + f.name + '»: подходят фото JPG, PNG, WebP и видео MP4, WebM.');
          else if (kind === 'photo' && photos >= limits.photos) problems.push('Можно прикрепить не больше ' + limits.photos + ' фото.');
          else if (kind === 'video' && videos >= limits.videos) problems.push('Можно прикрепить не больше ' + limits.videos + ' видео.');
          else if (kind === 'video' && f.size > limits.videoBytes) problems.push('«' + f.name + '»: видео больше ' + mb(limits.videoBytes) + '.');
          else if (kind === 'photo' && f.size > 60 * 1048576) problems.push('«' + f.name + '»: слишком большой файл.');
          else picked.push({ file: f, kind: kind, preview: kind === 'photo' ? URL.createObjectURL(f) : '', uploadId: '' });
        });
        renderFiles();
        showMsg(problems.length ? problems.join(' ') : '', problems.length ? 'error' : '');
      }

      if (attachBtn && fileInput) {
        attachBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => { addFiles(fileInput.files); fileInput.value = ''; });
      }

      /** Фото с телефона весит 5–12 МБ: уменьшаем до 2000 px по длинной стороне (поворот из EXIF учитывается браузером). */
      async function shrinkImage(file) {
        if (file.size < 1.2 * 1048576) return file;
        try {
          const bmp = await createImageBitmap(file);
          const s = Math.min(1, 2000 / Math.max(bmp.width, bmp.height));
          const c = document.createElement('canvas');
          c.width = Math.max(1, Math.round(bmp.width * s));
          c.height = Math.max(1, Math.round(bmp.height * s));
          c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
          const blob = await new Promise(res => c.toBlob(res, 'image/jpeg', 0.86));
          return blob && blob.size < file.size ? new File([blob], 'photo.jpg', { type: 'image/jpeg' }) : file;
        } catch (e) { return file; }
      }

      /** Загрузка одного файла с процентами. Возвращает идентификатор, который сервер привяжет к отзыву. */
      function uploadOne(blob, name, onProgress) {
        return new Promise((resolve, reject) => {
          const fd = new FormData();
          fd.append('file', blob, name);
          fd.append('token', token);
          const x = new XMLHttpRequest();
          x.open('POST', API + '?action=reviewUpload');
          x.responseType = 'json';
          x.timeout = 180000;
          x.upload.onprogress = e => { if (e.lengthComputable) onProgress(e.loaded / e.total); };
          x.onerror = x.ontimeout = () => reject(new Error('Не удалось загрузить файл «' + name + '». Проверьте интернет и попробуйте ещё раз.'));
          x.onload = () => {
            const j = x.response;
            if (j && j.ok) { resolve(j.data.id); return; }
            const err = (j && j.error) || {};
            if (err.code === 'form_expired') token = '';
            reject(new Error(err.message || 'Не удалось загрузить файл «' + name + '» (ошибка сервера).'));
          };
          x.send(fd);
        });
      }

      function showMsg(text, kind) {
        msg.hidden = !text;
        msg.textContent = text || '';
        msg.className = 'review_msg' + (kind ? ' is-' + kind : '');
      }

      function mark(name, bad) {
        const el = form.elements[name];
        if (!el) return;
        if (bad) el.setAttribute('aria-invalid', 'true'); else el.removeAttribute('aria-invalid');
      }

      // Подписанный токен формы просим при первом касании формы: так он «созревает» к моменту отправки
      function getToken() {
        if (token) return Promise.resolve();
        if (!loadingToken) {
          loadingToken = fetch(API + '?action=token', { cache: 'no-store', credentials: 'same-origin' })
            .then(r => r.json())
            .then(j => {
              if (j && j.ok) {
                token = j.data.formToken;
                tokenAt = Date.now();
                if (j.data.media) { limits = j.data.media; updateHint(); }
              }
            })
            .catch(() => { })
            .then(() => { loadingToken = null; });
        }
        return loadingToken;
      }
      form.addEventListener('focusin', getToken, { once: true });

      /** +7XXXXXXXXXX (или +код другой страны); null — номер некорректен. Те же правила, что на сервере. */
      function normalizePhone(raw) {
        raw = String(raw || '').trim();
        if (!raw || raw.length > 30 || /[^\d\s()+\-.]/.test(raw)) return null;
        const d = raw.replace(/\D+/g, '');
        let num;
        if (raw.charAt(0) === '+') {
          if (d.length < 8 || d.length > 15 || (d.charAt(0) === '7' && d.length !== 11)) return null;
          num = '+' + d;
        } else if (d.length === 11 && (d.charAt(0) === '8' || d.charAt(0) === '7')) {
          num = '+7' + d.slice(1);
        } else if (d.length === 10 && /^[3-9]/.test(d)) {
          num = '+7' + d;
        } else {
          return null;
        }
        if (/^(\d)\1+$/.test(num.slice(1)) || (num.indexOf('+7') === 0 && !/^\+7[3-9]/.test(num))) return null;
        return num;
      }

      const line = s => String(s || '').replace(/\s+/g, ' ').trim();

      function validate() {
        const v = {
          firstName: line(form.elements.firstName.value),
          lastName: line(form.elements.lastName.value),
          phone: line(form.elements.phone.value),
          text: String(form.elements.text.value || '').replace(/\r\n?/g, '\n').trim(),
        };
        const bad = {};
        if (!v.firstName) bad.firstName = 'Укажите имя.';
        else if (!NAME_RE.test(v.firstName)) bad.firstName = 'В имени допустимы только буквы, пробел, дефис и апостроф.';
        if (v.lastName && !NAME_RE.test(v.lastName)) bad.lastName = 'В фамилии допустимы только буквы, пробел, дефис и апостроф.';
        if (v.phone && !normalizePhone(v.phone)) bad.phone = 'Проверьте номер телефона или оставьте поле пустым.';
        if (v.text.length < 10) bad.text = 'Напишите отзыв хотя бы в паре предложений.';
        if (!form.elements.consent.checked) bad.consent = 'Нужно согласие на обработку персональных данных.';
        return { v: v, bad: bad };
      }

      function showBad(bad) {
        ['firstName', 'lastName', 'phone', 'text', 'consent'].forEach(n => mark(n, !!bad[n]));
        const first = ['firstName', 'lastName', 'phone', 'text', 'consent'].find(n => bad[n]);
        if (first) {
          showMsg(bad[first], 'error');
          form.elements[first].focus();
        }
      }

      form.addEventListener('input', e => { if (e.target.name) mark(e.target.name, false); });

      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (sending) return;
        showMsg('');
        const r = validate();
        if (Object.keys(r.bad).length) { showBad(r.bad); return; }

        sending = true;
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        const label = btn.textContent;
        btn.textContent = 'Отправляем…';
        try {
          await getToken();
          if (!token) throw new Error('Не удалось связаться с сервером. Попробуйте ещё раз.');
          const wait = 2300 - (Date.now() - tokenAt);   // сервер не принимает форму, отправленную мгновенно
          if (wait > 0) await new Promise(res => setTimeout(res, wait));

          // Файлы: по одному, уже загруженные при прошлой попытке повторно не отправляются
          for (let i = 0; i < picked.length; i++) {
            const it = picked[i];
            if (it.uploadId) continue;
            const prefix = 'Загружаем файл ' + (i + 1) + ' из ' + picked.length;
            btn.textContent = prefix + '…';
            const blob = it.kind === 'photo' ? await shrinkImage(it.file) : it.file;
            it.uploadId = await uploadOne(blob, it.file.name, p => { btn.textContent = prefix + ' (' + Math.round(p * 100) + '%)'; });
          }
          btn.textContent = 'Отправляем…';

          const res = await fetch(API + '?action=review', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
              firstName: r.v.firstName, lastName: r.v.lastName, phone: r.v.phone, text: r.v.text,
              media: picked.map(it => it.uploadId),
              consent: true, website: form.elements.website.value, token: token,
              device: { page: PAGE, screen: Math.round(screen.width) + 'x' + Math.round(screen.height), lang: navigator.language || '', touch: (navigator.maxTouchPoints || 0) > 1 },
            }),
          });
          const j = await res.json();
          if (j && j.ok) {
            form.reset();
            picked.forEach(it => { if (it.preview) URL.revokeObjectURL(it.preview); });
            picked.length = 0;
            renderFiles();
            token = '';
            showMsg('Спасибо! Ваш отзыв отправлен на проверку. Он появится на сайте после того, как хозяин его опубликует.', 'ok');
          } else {
            const err = (j && j.error) || {};
            if (err.code === 'validation' && err.fields) showBad(err.fields);
            else {
              if (err.code === 'form_expired') token = '';
              if (err.code === 'media') picked.forEach(it => { it.uploadId = ''; });   // вложения устарели: загрузим заново
              showMsg(err.message || 'Не удалось отправить отзыв. Попробуйте позже.', 'error');
            }
          }
        } catch (err) {
          if (!token) token = '';
          showMsg(err && err.message ? err.message : 'Не удалось отправить отзыв. Проверьте интернет и попробуйте ещё раз.', 'error');
        } finally {
          sending = false;
          btn.disabled = false;
          btn.removeAttribute('aria-busy');
          btn.textContent = label;
        }
      });
    })();
  </script>
</body>

</html>