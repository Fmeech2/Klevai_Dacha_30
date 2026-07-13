<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Document</title>
</head>

<body>
  <style>
    body {
      margin: 0;
      border: 0;
      padding: 0;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    div {}

    header {
      width: 100%;
      display: flex;
      justify-content: center;
      color: white;
      margin: 0;
      border: 0;
      padding: 0;
      flex-direction: column;
      align-content: flex-start;
      flex-wrap: nowrap;
      align-items: center;
      /* Главное для закрепления */
      position: fixed;
      /* или fixed */
      top: 0;
      /* обязательно */
      z-index: 1000;
      /* чтобы шапка была поверх всего */
    }

    .shapka {
      background: linear-gradient(180deg, #0A0A0A, transparent 390%);
      width: 100%;
      height: 108px;
      display: flex;
      color: white;
      margin: 0;
      border: 0;
      padding: 0;
    }

    .shapka_center {
      display: flex;
      flex-direction: row;
      justify-content: center;
      align-items: center;
      width: 1200px;
      margin-left: auto;
      margin-right: auto;
    }

    .logo {
      width: 100px;
      height: auto;
      margin: 10px;
      flex-shrink: 0;
      /* чтобы не сжимался слишком сильно */
      object-fit: contain;
      /* сохраняет пропорции */
      transition: all 0.4s ease;
      filter: brightness(1) invert(0);
    }

    .prev_logo_text {
      display: flex;
      flex-direction: column;
      justify-content: center;
      margin-left: auto;
      margin-right: 110px;
    }

    .prev_logo_text_attachment {
      font-size: 20px;
      line-height: 1.2;
    }

    .prev_logo_text_attachment_1 {
      font-size: 16px;
    }

    .prev_logo_text_attachment_2 {
      font-size: 24px;
      font-weight: bold;
    }

    .prev_logo_text_attachment_3 {
      font-size: 16px;
    }

    .prev_logo_text_attachment_4 {
      font-size: 16px;
    }

    .shapka_text_center {
      display: flex;
      flex-direction: column;
      justify-content: center;
      margin-left: 20px;
      margin-left: auto;
      margin-right: auto;
    }

    .shapka_text_right {
      display: flex;
      flex-direction: column;
      justify-content: center;
      margin-left: auto;
      margin-right: 20px;
      margin-left: 20px;
      margin-left: auto;
    }

    /* Класс, который будет добавляться при прокрутке */
    .shapka.shrink {
      height: 50px;
      transition: all 0.4s ease;
    }

    .shapka.shrink .shapka_center {
      display: flex;
      justify-content: flex-start;

    }

    /* Скрываем ненужные блоки */
    .shapka.shrink .shapka_text_center,
    .shapka.shrink .shapka_text_right {
      margin-right: 400px;
      margin-left: -300px;
      opacity: 0;
      transform: translateX(200px);
      /* улетание вправо */
      visibility: hidden;
      transition: all 0.4s ease;
    }

    .shapka.shrink .shapka_text_center_1,
    .shapka.shrink .shapka_text_center_2,
    .shapka.shrink .shapka_text_center_3,
    .shapka.shrink .shapka_text_right_1,
    .shapka.shrink .shapka_text_right_2 {
      height: 21px;
      width: 320px;
    }

    .shapka.shrink .logo {
      transition: all 0.4s ease;
      height: auto;
      width: 45px;
      margin-left: 100px;
      filter: brightness(0) invert(1) opacity(1);
    }

    .shapka.shrink .prev_logo_text {
      display: flex;
      flex-direction: row;
      width: 820px;
    }

    .shapka.shrink .prev_logo_text_attachment {
      display: flex;
      align-items: end;
      width: max-content;
    }

    .shapka.shrink .prev_logo_text_attachment_1 {
      margin-left: 40px;
      font-size: 18px;
    }

    .shapka.shrink .prev_logo_text_attachment_2 {
      margin-left: 10px;
    }

    .shapka.shrink .prev_logo_text_attachment_3 {
      margin-left: 10px;
      font-size: 18px;
    }

    .shapka.shrink .prev_logo_text_attachment_4 {
      margin-left: 10px;
      font-size: 18px;
    }


    .nav {
      padding-bottom: 20px;
      font-size: 20px;
      margin-top: 0px;
      display: flex;
    }

    a.nav_link button:active,
    a.nav_link button,
    a.nav_link button:hover {
      text-decoration: none;
      color: rgba(255, 255, 255, 0.8);
      border: none;
      height: 45px;
      margin-top: 0px;
      font-size: 18px;
    }

    a.nav_link button:hover {
      background: linear-gradient(0deg, #0A0A0A, transparent 75%);
      transition: 0.2s linear;
    }

    .nav_link button {
      margin: 8px;
      background: linear-gradient(0deg, #0A0A0A, transparent 275%);
      padding: 0px 20px;
      border-radius: 0 0 8px 8px;
      transition: 0.2s linear;
    }

    a.nav_link {
      height: 40px;
      text-decoration: none;
      margin-left: 0;
      margin-right: 0;
    }



    main {
      width: 100%;
      display: flex;
      margin-left: auto;
      margin-right: auto;
      background-color: #2c2c2cb4;
      color: white;
      font-size: 18px;
      flex-direction: column;
      margin-top: 0px;
    }

    .header_background {
      width: 100%;
      height: 108px;
      background-color: #0A0A0A;
      margin-top: 10px;
    }


    section {
      margin: 0px;
    }

    .hero_start {
      display: flex;
      flex-direction: row;
      width: 960px;
      margin-left: auto;
      margin-right: auto;
      align-items: center;
      margin-top: 70px;
    }

    .hero_text {
      display: flex;
      flex-direction: column;
      justify-content: center;
      margin-left: 20px;
    }

    .hero_text_item_1 {
      font-size: 24px;
    }

    .hero_text_item_2 {
      font-size: 32px;
      font-weight: bold;
    }

    .hero_text_item_3 {
      font-size: 24px;
    }

    .hero_text_next_1 {
      font-size: 24px;
      margin-top: 20px;
      margin-left: auto;
      margin-right: auto;
      width: 960px;
      display: flex;
      padding-left: 40px;
    }

    .hero_text_next_2 {
      font-size: 24px;
      margin-top: 20px;
      margin-left: auto;
      margin-right: auto;
      width: 960px;
      display: flex;
      padding-left: 40px;
    }

    .hero_text_next_3 {
      font-size: 24px;
      margin-top: 20px;
      margin-left: auto;
      margin-right: auto;
      width: 960px;
      display: flex;
      padding-left: 40px;
    }

    .hero_text_next_4 {
      font-size: 24px;
      margin-top: 20px;
      margin-left: auto;
      margin-right: auto;
      width: 960px;
      display: flex;
      padding-left: 40px;
    }

    .buy_button {
      margin: 20px;
      background-color: #2e2e2e;
      padding: 7px 20px;
      border-radius: 10px;
      width: fit-content;
      cursor: pointer;
      font-size: 32px;
      margin-left: 250px;
    }

    .buy_button:hover {
      background-color: #4b4b4b;
    }

    .buy_button:active {
      background-color: #818181;
    }

    .map {
      display: flex;
      justify-content: flex-end;
      width: 1160px;
      flex-direction: row;
      margin: 20px;
      margin-top: 40px;
    }

    .map_text {
      display: flex;
      flex-direction: column;
      justify-content: center;
      margin-right: 20px;
      margin-left: 100px;
      font-size: 20px;
    }

    .map_yandex {
      width: 556px;
      height: 433px;
      margin-right: 32px;
      border: 2px solid #ffffff;
      background-color: #ffffff52;
      border-radius: 8px;
    }

    .gallery {
      display: flex;
      flex-wrap: wrap;
    }

    .gallery_img {
      width: 560px;
      margin-left: 10px;
      margin-right: 10px;
      margin-top: 20px;
      height: auto;
      border-radius: 10px;
    }

    footer {
      width: 1200px;
      display: flex;
      margin-left: auto;
      margin-right: auto;
      background-color: #2c2c2cb4;
      color: white;
    }

    .logo_footer {
      width: 100px;
      height: auto;
      margin: 20px;
      margin-left: 80px;
    }

    .footer_text {
      padding: 20px;
      display: flex;
      text-align: center;
      justify-content: center;
      align-content: center;
      flex-direction: column;
    }
  </style>


  <header>
    <div class="shapka">
      <div class="shapka_center">
        <img src="img/Logo_zaglyshka.png" alt="Логотип КЛЁВАЯ ДАЧА" class="logo" />
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
            п. Ильинка Ильинка
          </div>
          <div class="shapka_text_center_3">
            на реке Гандурино
          </div>
        </div>

        <div class="shapka_text_right">
          <div class="shapka_text_right_1">
            Звони в любое время!
          </div>
          <div class="shapka_text_right_2">
            Телефон: 8 (911) 172-55-58
          </div>
        </div>
      </div>
    </div>
    <nav class="nav">
      <a href="#hero" class="nav_link"><button>Главная</button></a>
      <a href="#gallery" class="nav_link"><button>Фото</button></a>
      <a href="#reviews" class="nav_link"><button>Отзывы</button></a>
      <a href="#news" class="nav_link"><button>Новости</button></a>
      <a href="#contacts" class="nav_link"><button>Контакты</button></a>
    </nav>
  </header>
  <main>
    <section class="header_background" id="header_background"></section>
    <section class="hero" id="hero">
      <div class="hero_start">
        <div class="hero_text">
          <div class="hero_text_item_1">Гостевой дом</div>
          <div class="hero_text_item_2">"КЛЁВАЯ ДАЧА"</div>
          <div class="hero_text_item_3"> в Астрахани</div>
        </div>
        <div class="buy_button">
          Забронировать
        </div>
      </div>
      <div class="hero_text_next_1">
        6000 руб./сутки (Дом на 4 человек, мангальная зона, морозильная варильница)
      </div>
      <div class="hero_text_next_2">
        Уютный дом на 4 человек, Авангард...
      </div>
      <div class="hero_text_next_3">
        Зона вывески обо хорошем плете
      </div>
      <div class="hero_text_next_4">
        Казань, в ........
      </div>
      <div class="map">
        <div class="map_text">
          <div>
            Астраханская область, Камызякский район, п. Ильинка Ильинка на реке Гандурино
          </div>
          <div>
            Телефон: 8 (911) 172-55-58
          </div>
        </div>
        <div class="map_yandex">
          <script type="text/javascript" charset="utf-8" async
            src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3Ae68fb5c69dac82ecd8947b8a2656c4699d4770fe404154de16540404ec4d5026&amp;width=556&amp;height=433&amp;lang=ru_RU&amp;scroll=true"></script>
        </div>
      </div>
    </section>

    <section class="gallery" id="gallery">
      <img src="img/Dacha.png" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="gallery_img" />
      <img src="img/Dacha.png" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="gallery_img" />
      <img src="img/Dacha.png" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="gallery_img" />
      <img src="img/Dacha.png" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="gallery_img" />

    </section>

    <section class="reviews" id="reviews">
    </section>

    <section class="news" id="news">
    </section>

    <section class="contacts" id="contacts">
    </section>

  </main>

  <footer>
    <img src="img/Logo_zaglyshka_footer2.png" alt="Логотип КЛЁВАЯ ДАЧА" class="logo_footer" />

    <div class="footer_text">
      © Клёвая Дача 2026. Все права защищены.
    </div>
  </footer>
  <script>
    // Ждём загрузки страницы
    document.addEventListener('scroll', () => {
      const shapka = document.querySelector('.shapka');

      if (window.scrollY > 50) {
        shapka.classList.add('shrink');
      } else {
        shapka.classList.remove('shrink');
      }
    });
  </script>
</body>

</html>