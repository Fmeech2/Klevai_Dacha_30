<?php require __DIR__ . '/avito/render_reviews.php'; ?>

<!doctype html>
<html lang="ru">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script>
    // Переадресация на мобильную версию (mobile.html).
    // Когда уходим: телефон/небольшой планшет (по User-Agent или сенсорный экран с короткой стороной <= 900px)
    //               или узкое окно браузера (<= 768px).
    // Не уходим, если посетитель сам выбрал «Полную версию» (?desktop=1 — запоминаем на время вкладки).
    (function () {
      try {
        var KEY = 'kd_full_version';
        var coarse = !!(window.matchMedia && matchMedia('(pointer: coarse)').matches);

        // Выбрана полная версия?
        var wantFull = /[?&]desktop=1(&|$)/.test(location.search);
        if (wantFull) { try { sessionStorage.setItem(KEY, '1'); } catch (e) { } }
        else { try { wantFull = sessionStorage.getItem(KEY) === '1'; } catch (e) { } }

        if (wantFull) {
          // На телефоне показываем полную версию как «сайт для компьютера»: ширина 1280, масштаб подберёт браузер
          if (coarse) {
            var vp = document.querySelector('meta[name="viewport"]');
            if (vp) vp.setAttribute('content', 'width=1280');
          }
          return;
        }

        var ua = /Android.+Mobile|iPhone|iPod|IEMobile|Windows Phone|Opera Mini|Mobi/i.test(navigator.userAgent);
        var smallTouch = coarse && Math.min(screen.width, screen.height) <= 900;
        var narrow = window.innerWidth <= 768;

        if (ua || smallTouch || narrow) {
          location.replace('mobile.php' + location.hash);
        }
      } catch (e) { /* при любой ошибке просто остаёмся на этой странице */ }
    })();
  </script>
  <title>Клёвая Дача 30</title>
  <link rel="icon" href="img/Logo_zaglyshka_icon.png">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="reviews_photos.css">
</head>

<body id="start">
  <div class="scroll-rope" id="scrollRope">
    <img src="img/Крюк ползёт.png" alt="" class="scroll-rope_cap">
    <div class="scroll-rope_body"></div>
  </div>

  <!-- Обёртка страницы: сюда применяется масштаб на узких окнах (см. .page в CSS) -->
  <div class="page" id="page">

  <header>
    <div class="shapka">
      <div class="shapka_center">
        <div class="logo-wrapper">

          <a href="#start" class="logo-wrapper">
            <img src="img/Logo_zaglyshka_footer2.png" alt="Логотип КЛЁВАЯ ДАЧА" class="logo logo-default">
          </a>
          <img src="img/Синий фон.png" alt="Крюк" class="hook_background" />
          <img src="img/Крюк PNG.png" alt="Крюк" class="hook" />
        </div>
        <div class="prev_logo_text">
          <div class="prev_logo_text_attachment prev_logo_text_attachment_1">
            Гостевой комплекс
          </div>
          <div class="prev_logo_text_attachment prev_logo_text_attachment_2">
            КЛЁВАЯ ДАЧА
          </div>
          <div class="prev_logo_text_attachment prev_logo_text_attachment_3">
            Аренда дома на Реке
          </div>
          <div class="prev_logo_text_attachment prev_logo_text_attachment_4">
            в дельте Волги
          </div>
        </div>

        <div class="shapka_text_center">
          <div class="shapka_text_center_1">
            Астраханская область, Камызякский район,
          </div>
          <div class="shapka_text_center_2">
            поселок Нижненикольский
          </div>
          <div class="shapka_text_center_3">
            На реке Гандурино.
          </div>
        </div>

        <div class="shapka_text_right">
          <div class="shapka_text_right_1">
            Звони в любое время!
          </div>
          <div class="shapka_text_right_2">
            Телефон: +7 917 172-55-58
          </div>
          <div class="shapka_text_right_3">
            <a href="https://vk.ru/club11109186" target="_blank" class="logo_messenger">
              <img src="img/VK PNG.png" alt="VK КЛЁВАЯ ДАЧА" class=" " />
            </a>
            <a href="https://max.ru/u/f9LHodD0cOLFu70KB7BkQSMHHAYgfCx8tbYvh3qt9_pkbZJNzbhYcXr5BbI" target="_blank" class="logo_messenger">
              <img src="img/Max PNG.png" alt="Max КЛЁВАЯ ДАЧА" class=" " />
            </a>
            <a href="https://api.whatsapp.com/send?phone=79171725558" target="_blank" class="logo_messenger">
              <img src="img/WhatsApp PNG.png" alt="WhatsApp КЛЁВАЯ ДАЧА" class=" " />
            </a>
            <a href="https://t.me/@ALEXYI91" target="_blank" class="logo_messenger">
              <img src="img/Telegram PNG.png" alt="Телеграм КЛЁВАЯ ДАЧА" class=" " />
            </a>

          </div>
        </div>
      </div>
    </div>
  </header>
  <main>

    <section class="header_background" id="header_background"></section>
    <nav class="nav" id="hero">
      <a href="#start" class="nav_link videleno"><button>Главная</button></a>
      <a href="#gallery" class="nav_link"><button>Фото</button></a>
      <a href="#reviews" class="nav_link"><button>Отзывы</button></a>
      <a href="#news" class="nav_link"><button>Новости</button></a>
      <a href="#contacts" class="nav_link"><button>Контакты</button></a>
    </nav>





    <section class="hero">
      <div class="hero_left">
        <div class="hero_left_1"> Хотите идеальный отдых у воды? </div>
        <div class="hero_left_2">В гостевом доме „Клёвая дача“ вас ждут комфорт, все удобства, просторная беседка и
          мангал, коптильня — и,
          конечно, отличная рыбалка. Собирайте компанию, берите снасти — и приезжайте за впечатлениями!»</div>
        <div class="hero_left_4">+7 (917) 172-55-58</div>
        <a class="hero_left_5" href="">Написать в Вацап</a>
        <button class="hero_left_BUTTON" type="button" data-booking-open>Забронировать</button>
      </div>
      <div class="hero_right">
        <img src="img/Рыба на превью.png" class="hero_right_PROMO">
        <div class="hero_right_1">
          <div class="hero_right_2">Гостевой дом "КЛЁВАЯ ДАЧА"</div>
          <div class="hero_right_3">
            <div class="hero_right_3_text_verh">
              <div class="hero_right_3_text_verh_1">Мангальная зона </div>
              <div class="hero_right_3_text_verh_2">Красивый вид на реку с вкусными шашлыками </div>
              <div class="hero_right_3_text_niz">
                <div class="hero_right_3_img_bg">
                  <div class="hero_right_3_text_niz_Tage">Дом на 4 человек</div>
                </div>
                <div class="hero_right_3_img_bg">
                  <div class="hero_right_3_text_niz_Tage">от 6000 руб./сутки</div>
                </div>
              </div>
            </div>
            <div class="hero_right_3_img">
              <img src="img/Шашлык превью.png" class="hero_right_3_img_1">
              <img src="img/Прихожая превью.png" class="hero_right_3_img_2">
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="map-section">
      <!-- Картинка-фон -->
      <img src="img/Лодка.png" alt="" class="map_bg">

      <div class="map">
        <div class="map_text">
          <div class="map_text_item_1">
            Астраханская область, Камызякский район, поселок Нижненикольский <br>
            На реке Гандурино.
          </div>
          <div class="map_text_item_2">
            Телефон: +7 (917) 172-55-58
          </div>
        </div>
        <div class="map_yandex">
          <iframe id="map_104546197" frameborder="0" width="556px" height="433px" src="https://makemap.2gis.ru/widget?data=eJyllU1rHEcQhv9L57qIrs_u3msgxhCIIQdDjA4bbVseMtpZRmOILPTfU9NfIigGQ3RaPVNT_dbbVTXPblnPec3nd3l5yNs65Ud3_PTstqdrdkf3Sz5tX9fsDu66Lte8buX5szvnx7t1um7TcrEoe_y4rctf-edlXlYDP-UkJDj4x-m8fXFH7f__dj3dTduTO8LBfXt_Oee_3RFfDu6-injaj2gKfp0u-XfTdbm3bHeLqZ0up62o_MThJkUmxAPLTUJBH24PlZJShZxIBvTcqIQ4QoFRK40xpEZDgsSFEtqLgyJIpaQigyrWvMSK4zQAjY0SwNAAsWVg8a_KYuoZmAdVaqEaRXcYbzwAhQLtTB8b9NEKLTSChB7qWWI6sNovFB0JKEql4DVwjyVpFJMn6BQiY6EsAbjXGzBJoZESx0bZfupOAb2Oi5DAuwtGhcK4iQDCXKjGANqopog11s7qoapeoEJB6RKUlGto9Bw7lRi5xkZzt2cQAAiFJjFHulwrrVD0CICNEgNipeppUPKpUfO5JxChIgHRR-z3K94qqhS9790kyYyoNIRBlYMWF9A6REbn-bR36U4DjtgEkMr9oETwXViKQWqsJLOp0xC03ASq2CGdMlEtQs2oTiMr1QwBAg4JAlVYEB6Dogmqu1aDNd-gtUMw2CTGcb-Bar12IWmkpZAajfo6ESlVBeRF6kWUzqsJCIJNRIeYQCqNLKNJVevtkE0E6FsqbdDKoFgXVWobYIyE-VzMNYc4pU6DDV6lnLBTu1esymxisGtAc6x4Q2yedWgv1srYhoM6tVFsoQFlUOXaeMQRzcdb24bT2Raktf_L4X_vYwx_Evgf2sefp3ke76mclZKrdET5G3nd2_Tfe_vDMj_dm5o3S7vv53017UtXgccqFqC2y1V5tKiyb7H2XEfjJuwrXvB1vf477_DRpjX-gI93vXCPET7vH7Bt2uZcPX1jcrcAfP_7nhfTZXvjRK0iaq-NrPmb2sDycntwD6frh-Vxqgc-u_m07YVYrH2qkrWngPhwcPP-uH1GbB-KjVxC-6a5b8vyYOqsgZwpX-b545ec5z8K3dav-eUfZ2j_GQ" sandbox="allow-modals allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation-by-user-activation"></iframe>
          </div>
      </div>
    </div>

    <section class="gallery_Start" id="gallery">
      <div class="gallery_title">
        Фотогалерея
      </div>
      <nav class="nav" style="justify-content: left; padding-left: 0; margin-left: 0; margin-bottom: 20px ;">
        <button class="nav_button videleno" data-gallery="main">Фото</button>
        <button class="nav_button" data-gallery="visitors">Фото от посетителей</button>
      </nav>


      <div class="gallery">
        <div class="gallery_images" id="gallery_main">

          <!-- Вариант 1: 4 маленьких -->
          <img src="img_saita/1.jpg" alt="..." class="gallery_img">
          <img src="img_saita/2.jpg" alt="..." class="gallery_img small">

          <!-- Вариант 2: 1 большая + 2 маленьких -->
          <img src="img_saita/3.jpg" alt="..." class="gallery_img small">
          <img src="img_saita/4.jpg" alt="..." class="gallery_img small">
          <img src="img_saita/5.jpg" alt="..." class="gallery_img large">

          <!-- Вариант 3: 2 больших -->
          <img src="img_saita/6.jpg" alt="..." class="gallery_img large">
          <img src="img_saita/7.jpg" alt="..." class="gallery_img small">
          <img src="img_saita/8.jpg" alt="..." class="gallery_img small">


          <img src="img_saita/9.jpg" alt="..." class="gallery_img large">
          <img src="img_saita/10.jpg" alt="..." class="gallery_img large">


          <img src="img_saita/11.jpg" alt="..." class="gallery_img large">
          <img src="img_saita/12.jpg" alt="..." class="gallery_img small pos_right">

          
          <img src="img_saita/13.jpg" alt="..." class="gallery_img">
          <img src="img_saita/14.jpg" alt="..." class="gallery_img">
          <img src="img_saita/15.jpg" alt="..." class="gallery_img">

        </div>

        <!-- Галерея посетителей (заглушки) -->
        <div class="gallery_images is-hidden" id="gallery_visitors" hidden>

          <img src="img_gostei/1.jpg" alt="..." class="gallery_img small">
          <img src="img_gostei/2.jpg" alt="..." class="gallery_img small">
          <img src="img_gostei/3.jpg" alt="..." class="gallery_img large">


          <img src="img_gostei/4.jpg" alt="..." class="gallery_img large">
          <img src="img_gostei/5.jpg" alt="..." class="gallery_img small">
          <img src="img_gostei/6.jpg" alt="..." class="gallery_img small">


          <img src="img_gostei/7.jpg" alt="..." class="gallery_img large">
          <img src="img_gostei/9.jpg" alt="..." class="gallery_img large">

          <img src="img_gostei/8.jpg" alt="..." class="gallery_img small">
          <img src="img_gostei/10.jpg" alt="..." class="gallery_img small">
          <img src="img_gostei/11.jpg" alt="..." class="gallery_img small">
          <img src="img_gostei/12.jpg" alt="..." class="gallery_img small">

          <img src="img_gostei/13.jpg" alt="..." class="gallery_img small">
          <img src="img_gostei/14.jpg" alt="..." class="gallery_img small">

        </div>
      </div>



      <nav class="nav" style="justify-content: left; padding-left: 0; margin-left: 0; margin-bottom: 20px ;">
        <a href="#hero" class="nav_link videleno"><button>Видео</button></a>
      </nav>
      <div class="gallery_images gallery_videos">

        <div class="video-wrapper">
          <video class="gallery_img gallery_video" muted preload="metadata">
            <source src="video/video_2026-09-11_22-33-57.mp4" type="video/mp4">
          </video>
          <div class="play-icon">▶</div>
        </div>

        <div class="video-wrapper">
          <video class="gallery_img gallery_video" muted preload="metadata">
            <source src="video/video_2026-09-11_22-34-23.mp4" type="video/mp4">
          </video>
          <div class="play-icon">▶</div>
        </div>

      </div>



    </section>

    <section class="reviews" id="reviews">
      <h2 class="reviews_title">Отзывы</h2>


      <?= render_reviews() ?>

      <!-- Форма -->
      <div class="review_form_wrapper">
        <h3 class="review_form_title">Оставить отзыв</h3>

        <form class="review_form" action="#" method="post">
          <div class="review_form_row">
            <input type="text" name="name" class="review_input" placeholder="Имя*" required>
            <input type="text" name="surname" class="review_input" placeholder="Фамилия">
          </div>

          <input type="tel" name="phone" class="review_input review_input_phone" placeholder="Номер телефона">

          <textarea name="review" class="review_textarea" placeholder="Ваш отзыв" rows="4"></textarea>

          <button type="submit" class="review_submit">Отправить</button>
        </form>
      </div>
    </section>

    <section class="news" id="news">
    </section>

    <section class="contacts" id="contacts">
    </section>

  </main>

  <footer>
  <!-- Крепление верёвки -->
  <img src="img/Крюк ползёт низ.png" alt="" class="rope-anchor" id="ropeAnchor" />

  <div class="footer_conteiner">
    <img src="img/Logo_zaglyshka_footer2.png" alt="Логотип КЛЁВАЯ ДАЧА" class="logo_footer" />

    <div class="footer_text">
      © Клёвая Дача 2026. Все права защищены.
    </div>

    <div class="footer_contacts">
      <div class="footer_phone_block">
        <div class="footer_phone_label">Телефон:</div>
        <div class="footer_phone_number">+7 (917) 172-55-58</div>
        <div class="footer_phone_name">Алексей</div>
      </div>

      <div class="footer_phone_block">
        <div class="footer_phone_label">Телефон:</div>
        <div class="footer_phone_number">+7 (967) 337-99-57</div>
        <div class="footer_phone_name">Анна</div>
      </div>

      <div class="footer_email">
        Astrakhan@klevaydacha30.ru
      </div>

      <div class="footer_socials shapka_text_right_3">
        <a href="https://vk.ru/club11109186" target="_blank" class="logo_messenger">
          <img src="img/VK PNG.png" alt="VK КЛЁВАЯ ДАЧА"  class="img_icons_footers"/>
        </a>
        <a href="https://max.ru/u/f9LHodD0cOLFu70KB7BkQSMHHAYgfCx8tbYvh3qt9_pkbZJNzbhYcXr5BbI" target="_blank" class="logo_messenger">
          <img src="img/Max PNG.png" alt="Max КЛЁВАЯ ДАЧА" class="img_icons_footers" />
        </a>
        <a href="https://api.whatsapp.com/send?phone=79171725558" target="_blank" class="logo_messenger">
          <img src="img/WhatsApp PNG.png" alt="WhatsApp КЛЁВАЯ ДАЧА"  class="img_icons_footers"/>
        </a>
        <a href="https://t.me/@ALEXYI91" target="_blank" class="logo_messenger">
          <img src="img/Telegram PNG.png" alt="Телеграм КЛЁВАЯ ДАЧА"  class="img_icons_footers"/>
        </a>
      </div>
    </div>
  </div>
</footer>

  </div><!-- /.page -->


  <!-- Лайтбокс -->
  <div class="lightbox" id="lightbox">
    <button class="lightbox-btn lightbox-prev" id="lightboxPrev">‹</button>

    <img src="" alt="" class="lightbox-media" id="lightboxImg">
    <video class="lightbox-media" id="lightboxVideo" controls></video>

    <button class="lightbox-btn lightbox-next" id="lightboxNext">›</button>

    <button class="lightbox-close" id="lightboxClose">×</button>
  </div>


  <!-- ===================== БРОНИРОВАНИЕ: модальное окно =====================
       Разметка одинаковая в index.html и mobile.html; оформление — в style.css / mobile.css (классы bk-*).
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
                <input class="bk-input" type="tel" name="phone" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="+7 917 123-45-67" required aria-describedby="bkErr-phone">
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
                <span>Я согласен(на) на обработку персональных данных в соответствии с <a href="privacy.html" id="bkPrivacy" target="_blank" rel="noopener">политикой сайта</a>.</span>
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
    // ================= Масштаб страницы на узких окнах =================
    // Вёрстка рассчитана на ширину до 1320px (колонка 1200px + выступающая галерея).
    // Если окно уже — пропорционально уменьшаем страницу (CSS zoom на .page), чтобы шапка
    // и контент не обрезались и не появлялась горизонтальная прокрутка. От 1320px и шире — масштаб 1.
    const DESIGN_WIDTH = 1320;
    const rootEl = document.documentElement;
    let pageK = 1;

    function fitPage() {
      pageK = Math.min(1, rootEl.clientWidth / DESIGN_WIDTH);
      rootEl.style.setProperty('--page-k', pageK.toFixed(4));
    }

    // ================= Шапка: большая <-> маленькая =================
    // Только переключаем класс. Все анимации (шапка, логотип, тексты, крючки)
    // делает CSS с общими --shapka-dur / --shapka-ease, поэтому они синхронны,
    // а при быстрой прокрутке туда-обратно плавно перенацеливаются с текущей точки.
    const shapka = document.querySelector('.shapka');
    const navTop = document.querySelector('.nav');
    const SHRINK_AT = 50;
    let isShrunk = false;

    function updateHeader() {
      const next = window.scrollY > SHRINK_AT;
      if (next === isShrunk) return;
      isShrunk = next;
      shapka.classList.toggle('shrink', next);
      navTop.classList.toggle('shrink', next);
    }

    // ================= Ползущая верёвка слева =================
    // Высота верёвки считается так, чтобы она всегда доходила от крюка до низа окна,
    // а в конце страницы — ровно до узла в футере (см. .scroll-rope и .rope-anchor в CSS).
    const rope = document.getElementById('scrollRope');
    const ROPE_START = 108;        // с какой прокрутки появляется (высота большой шапки)
    const ROPE_TOP_GAP = 50;       // на какой высоте от верха окна останавливается крюк
    // Крепление в футере, в пикселях при масштабе 1 (должно совпадать с .rope-anchor в CSS):
    const ANCHOR_BOTTOM = 120;     // bottom у .rope-anchor
    const KNOT_END = 11.9;         // от низа картинки до места, где обрывается верёвка внутри узла

    function updateRope() {
      // clientHeight — высота окна без горизонтальной полосы прокрутки (innerHeight её включал)
      const vh = rootEl.clientHeight;
      const y = window.scrollY;
      const start = ROPE_START * pageK;

      if (y < start) {
        rope.style.opacity = '0';
        rope.style.height = '0px';
        rope.style.transform = 'translateY(0)';
        return;
      }

      const maxScroll = rootEl.scrollHeight - vh;
      const progress = (y - start) / Math.max(1, maxScroll - start);   // 0..1
      const clamped = Math.min(Math.max(progress, 0), 1);
      const move = clamped * (vh - ROPE_TOP_GAP * pageK);              // на сколько поднялся крюк
      const ropeTop = vh - move;                                       // y крюка в окне

      // Где сейчас (в координатах окна) место, куда верёвка уходит в узел. Футер — последний блок
      // страницы, поэтому считаем от низа страницы, не читая размеры отмасштабированных элементов.
      const remaining = Math.max(0, maxScroll - y);                    // сколько ещё осталось прокрутить
      const knotY = vh + remaining - (ANCHOR_BOTTOM + KNOT_END) * pageK;

      const length = Math.max(0, Math.min(vh, knotY) - ropeTop);

      rope.style.opacity = '1';
      rope.style.height = length + 'px';
      rope.style.transform = `translateY(-${move}px)`;
    }

    function onScroll() {
      updateHeader();
      updateRope();
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    // Раньше при изменении размера окна положение крючка не пересчитывалось
    window.addEventListener('resize', () => {
      fitPage();
      onScroll();
    });
    fitPage();
    onScroll();

    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxVideo = document.getElementById('lightboxVideo');
    const btnPrev = document.getElementById('lightboxPrev');
    const btnNext = document.getElementById('lightboxNext');
    const btnClose = document.getElementById('lightboxClose');

    let currentGallery = [];
    let currentIndex = 0;

    // Получаем все медиа из видимой галереи
    function getVisibleGalleryItems() {
      // Собираем все элементы с классом .gallery_img на странице,
      // которые сейчас видимы
      const allItems = Array.from(document.querySelectorAll('.gallery_img'));

      return allItems.filter(item => {
        // Проверяем, не скрыт ли родительский контейнер
        const parentGallery = item.closest('.gallery_images');
        //if (!parentGallery) return true;
        return true;

        // Если это галерея посетителей и она скрыта — пропускаем
        if (parentGallery.id === 'gallery_visitors' && parentGallery.classList.contains('is-hidden')) {
          return false;
        }

        // Если это основная галерея и она скрыта — пропускаем
        if (parentGallery.id === 'gallery_main' && parentGallery.classList.contains('is-hidden')) {
          return false;
        }

        return true;
      });
    }

    function showMedia(index) {
      if (!currentGallery.length) return;

      const item = currentGallery[index];

      // Сброс
      lightboxImg.classList.remove('active');
      lightboxVideo.classList.remove('active');
      lightboxVideo.pause();
      lightboxVideo.removeAttribute('src');
      lightboxVideo.load();

      if (item.tagName === 'VIDEO') {
        // Видео
        const source = item.querySelector('source');
        lightboxVideo.src = source ? source.src : item.currentSrc || item.src;
        lightboxVideo.classList.add('active');
        // Не ставим autoplay сразу, чтобы избежать ошибок
        lightboxVideo.play().catch(() => { });
      } else {
        // Фото
        lightboxImg.src = item.src;
        lightboxImg.alt = item.alt || '';
        lightboxImg.classList.add('active');
      }
    }

    function openLightbox(index) {
      currentGallery = getVisibleGalleryItems();
      if (!currentGallery.length) return;

      currentIndex = index;
      showMedia(currentIndex);

      lightbox.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lightbox.classList.remove('active');
      lightboxVideo.pause();
      lightboxVideo.currentTime = 0;
      lightboxVideo.removeAttribute('src');
      document.body.style.overflow = '';
    }

    // Навешиваем клик на все текущие и будущие .gallery_img
    function bindGalleryClicks() {
      document.querySelectorAll('.gallery_img').forEach(item => {
        // Убираем старые обработчики, чтобы не дублировались
        item.onclick = null;

        item.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          const items = getVisibleGalleryItems();
          const realIndex = items.indexOf(item);

          if (realIndex !== -1) {
            openLightbox(realIndex);
          }
        });
      });
    }

    // Первичная привязка
    bindGalleryClicks();

    // Если переключаешь галереи кнопками — перепривязываем
    document.querySelectorAll('.nav_button[data-gallery]').forEach(button => {
      button.addEventListener('click', () => {
        // Небольшая задержка, чтобы класс is-hidden успел примениться
        setTimeout(bindGalleryClicks, 50);
      });
    });

    // Кнопки навигации
    btnPrev.addEventListener('click', (e) => {
      e.stopPropagation();
      if (!currentGallery.length) return;
      currentIndex = (currentIndex - 1 + currentGallery.length) % currentGallery.length;
      showMedia(currentIndex);
    });

    btnNext.addEventListener('click', (e) => {
      e.stopPropagation();
      if (!currentGallery.length) return;
      currentIndex = (currentIndex + 1) % currentGallery.length;
      showMedia(currentIndex);
    });

    // Закрытие
    btnClose.addEventListener('click', (e) => {
      e.stopPropagation();
      closeLightbox();
    });

    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) {
        closeLightbox();
      }
    });

    // Клавиатура
    document.addEventListener('keydown', (e) => {
      if (!lightbox.classList.contains('active')) return;

      if (e.key === 'ArrowLeft') {
        btnPrev.click();
      } else if (e.key === 'ArrowRight') {
        btnNext.click();
      } else if (e.key === 'Escape') {
        closeLightbox();
      }
    });






    const galleryButtons = document.querySelectorAll('.nav_button[data-gallery]');
    const galleryMain = document.getElementById('gallery_main');
    const galleryVisitors = document.getElementById('gallery_visitors');

    galleryButtons.forEach(button => {
      button.addEventListener('click', () => {
        const target = button.dataset.gallery;

        // Активная кнопка
        galleryButtons.forEach(btn => btn.classList.remove('videleno'));
        button.classList.add('videleno');

        // Переключаем галереи
        if (target === 'main') {
          galleryMain.classList.remove('is-hidden');
          galleryVisitors.classList.add('is-hidden');
        } else {
          galleryMain.classList.add('is-hidden');
          galleryVisitors.classList.remove('is-hidden');
        }
      });
    });
  </script>
  <script>
    // ====================================================================
    // БРОНИРОВАНИЕ: модальное окно, календарь доступности, отправка заявки
    // ====================================================================
    // Блок не зависит от галереи, меню и шапки: свой масштаб видимости, свои обработчики,
    // наружу выставлен только window.KDBooking.open(). Код одинаков в index.html и mobile.html,
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
        page: 'desktop',            // 'desktop' в index.html, 'mobile' в mobile.html
        api: 'booking/api.php',      // общий серверный обработчик
        privacyUrl: 'privacy.html',  // страница политики обработки персональных данных
        useHistory: false,     // true: кнопка «Назад» на телефоне закрывает окно
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
          phone: cleanLine(form.elements.phone.value),
          comment: String(form.elements.comment.value || '').replace(/\r\n?/g, '\n').trim(),
        };
        if (!v.firstName) errors.firstName = 'Укажите имя.';
        else if (!NAME_RE.test(v.firstName)) errors.firstName = 'Допустимы только буквы, пробел, дефис и апостроф.';
        if (v.lastName && !NAME_RE.test(v.lastName)) errors.lastName = 'Допустимы только буквы, пробел, дефис и апостроф.';
        if (v.patronymic && !NAME_RE.test(v.patronymic)) errors.patronymic = 'Допустимы только буквы, пробел, дефис и апостроф.';

        if (!v.phone) errors.phone = 'Укажите номер телефона, чтобы хозяин мог вам перезвонить.';
        else if (!normalizePhone(v.phone)) errors.phone = 'Проверьте номер телефона. Пример: +7 917 123-45-67.';

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

      // Подсказки на лету: убираем ошибку, как только поле исправлено
      FIELDS.slice(0, 5).forEach(name => {
        const input = form.elements[name];
        if (!input) return;
        input.addEventListener(name === 'consent' ? 'change' : 'input', () => setFieldError(name, ''));
      });
      form.elements.phone.addEventListener('blur', () => {
        const n = normalizePhone(form.elements.phone.value);
        if (n) form.elements.phone.value = prettyPhone(n);
      });

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
</body>

</html>