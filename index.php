<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Крутая Дача 30</title>
  <link rel="icon" href="img/Logo_zaglyshka_icon.png">
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
      background: linear-gradient(0deg, #0a0a0ab5, #0a0a0ab5);
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

    .nav.shrink {
      margin-top: 0px;
    }



    main {
      width: 100%;
      display: flex;
      margin-left: auto;
      margin-right: auto;
      background-color: white;
      color: #4d5b7c;
      font-size: 18px;
      flex-direction: column;
      margin-top: 0px;
    }

    .header_background {
      width: 100%;
      height: 108px;
      background-color: #0A0A0A;
      margin-top: 0px;
    }


    section {
      margin: 0px;
    }

    .hero_start {
      display: flex;
      flex-direction: row;
      width: 1030px;
      margin-left: auto;
      margin-right: auto;
      align-items: center;
      margin-top: 190px;
      margin-bottom: 150px;
    }

    .hero_text {
      display: flex;
      flex-direction: column;
      justify-content: center;
      margin-left: 20px;
      background-color: #fff;
      border-radius: 10px;
      border: 4px solid #f8f8f8;
      padding: 15px;
      margin-left: 110px;
    }

    .hero_text_item_1 {

      font-size: 28px;
      line-height: 0.5;
      margin-left: 1px;
    }

    .hero_text_item_2 {
      font-size: 38px;
      font-weight: bold;
    }

    .hero_text_item_3 {
      line-height: 0.1;
      margin-left: 19px;
      font-size: 24px;
      margin-bottom: 10px;
    }

    .hero_center {
      background-color: #212121;
      width: 1070px;
      margin-left: auto;
      margin-right: auto;
      border-radius: 35px;
      padding: 20px;
      color: white;
    }

    .hero_text_next_conteiner {
      display: flex;
      justify-content: center;
      flex-direction: row;
      margin-left: auto;
      margin-right: auto;
      background-color: #000;
      border-radius: 15px;
      padding: 20px;
    }

    .hero_text_left {
      width: 500px;
    }

    .hero_text_right {
    width: 500px;
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-start;
    margin-left: auto;
    }

    .hero_text_right img {
      width: 206px;
      height: 300px;
      border-radius: 15px;
      object-fit: cover;
      margin-left: 20px;
    }

    .hero_text_next_1 {
      font-size: 24px;
      margin-top: 20px;
      width: 100%;
      display: flex;
      justify-content: center;
      margin-left: auto;
      margin-right: auto;
      margin-bottom: 40px;
      color: #fff;
      font-family: Inter, sans-serif;
      font-size: 75px;
      font-weight: 600;
      line-height: 100%;
    }



    .hero_text_next_2 {
      font-size: 32px;
      margin-top: 67px;
      margin-left: auto;
      margin-right: auto;
      width: 100%;
      display: flex;
    }

    .hero_text_next_3 {
      font-size: 24px;
      margin-top: 0px;
      line-height: 0.9;
      margin-left: auto;
      margin-right: auto;
      width: 100%;
      display: flex;
      color: #838383;
    }

    .hero_text_next_4 {
      font-size: 16px;
      margin-top: 10px;
      margin-right: auto;
      display: flex;
      color: var(--white);
      letter-spacing: -.05em;
      background-color: #202020;
      padding: 3px 4px;
      font-size: .875rem;
      line-height: 120%;
      width: max-content;
    }

    .hero_text_next_5 {
      font-size: 16px;
      margin-top: 5px;
      margin-right: auto;
      display: flex;
      color: var(--white);
      letter-spacing: -.05em;
      background-color: #202020;
      padding: 3px 4px;
      font-size: .875rem;
      line-height: 120%;
      width: max-content;
    }

    .hero_text_next_6 {
      font-size: 16px;
      margin-top: 5px;
      margin-right: auto;
      display: flex;
      color: var(--white);
      letter-spacing: -.05em;
      background-color: #202020;
      padding: 3px 4px;
      font-size: .875rem;
      line-height: 120%;
      width: max-content;
    }

    .buy_button {
      margin: 20px;
      background-color: #0069ff;
      color: white;
      padding: 7px 20px;
      border-radius: 10px;
      width: fit-content;
      cursor: pointer;
      font-size: 32px;
      margin-left: auto;
      margin-right: 220px;
      font-weight: 600;
    }

    .buy_button:hover {
      background-color: #4b4b4b;
    }

    .buy_button:active {
      background-color: #818181;
    }

    .map {
      margin-left: auto;
      margin-right: auto;
      display: flex;
      justify-content: flex-end;
      width: 1160px;
      flex-direction: row;
      margin-bottom: 20px;
      margin-top: 40px;
    }

    .map_text {
      display: flex;
      flex-direction: column;
      justify-content: center;
      margin-right: 20px;
      margin-left: 85px;
      font-size: 20px;
      background-color: #f5f5f5;
      margin-top: auto;
      margin-bottom: auto;
      border-radius: 20px;
      padding: 15px;
      color: #1f1f1f;
      font-family: Inter, sans-serif;
      font-size: 20px;
      line-height: 20px;
      font-weight: 500;
      line-height: 1.2;
    }

    .map_text_item_1 {}

    .map_text_item_2 {
      margin-top: 8px;
      font-size: 14px;
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
      margin-left: auto;
      margin-right: auto;
      width: 1160px;
      display: flex;
      flex-wrap: wrap;
      display: flex;
      margin-top: 40px;
    }

    .gallery_title {
      width: auto;
      font-size: 45px;
      font-weight: bold;
      margin-top: 20px;
      margin-bottom: 20px;
      margin-left: auto;
      margin-right: auto;
    }

    .gallery_imeges {
      width: 100%;
      margin-left: auto;
      margin-right: auto;
      display: flex;
      flex-wrap: wrap;

      margin-left: 10px;
      margin-right: 10px;
      margin-top: 20px;
      height: auto;
      border-radius: 10px;
    }

    .gallery_img {
      width: 555px;
      margin-left: 10px;
      margin-right: 10px;
      margin-top: 20px;
      height: auto;
      border-radius: 10px;
    }

    footer {
      width: 100%;
      display: flex;
      margin-left: auto;
      margin-right: auto;
      background-color: #1f1f1f;
      color: white;
      margin-top: 60px;
      font-family: Inter, sans-serif;
    }

    .footer_conteiner {
      width: 1200px;
      display: flex;
      margin-left: auto;
      margin-right: auto;
      background-color: #1f1f1f;
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
      <div class="hero_center">
        <div class="hero_text_next_1">
          6000 руб./сутки
        </div>
        <div class="hero_text_next_conteiner">
          <div class="hero_text_left">
            <div class="hero_text_next_2">
              Гостевой дом "КЛЁВАЯ ДАЧА"
            </div>
            <div class="hero_text_next_3">
              Дом на 4 человек, мангальная зона, морозильная варильница
            </div>
            <div class="hero_text_next_4">
              Уютный дом на 4 человек, Авангард...
            </div>
            <div class="hero_text_next_5">
              Зона вывески обо хорошем плете
            </div>
            <div class="hero_text_next_6">
              Казань, в ........
            </div>
          </div>

          <div class="hero_text_right">
            <img src="img/zag7.jpg" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="hero_img" />
            <img src="img/zag9.jpg" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="hero_img" />
          </div>
        </div>
      </div>
      </div>
      <div class="map">
        <div class="map_text">
          <div class="map_text_item_1">
            Астраханская область, Камызякский район, п. Ильинка Ильинка на реке Гандурино
          </div>
          <div class="map_text_item_2">
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
      <div class="gallery_title">
        Фотогалерея
      </div>
      <div class="gallery_images">
        <img src="img/zag1.png" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="gallery_img" />
        <img src="img/zag2.png" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="gallery_img" />
        <img src="img/zag3.png" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="gallery_img" />
        <img src="img/zag4.png" alt="Гостевой дом КЛЁВАЯ ДАЧА" class="gallery_img" />
      </div>
    </section>

    <section class="reviews" id="reviews">
    </section>

    <section class="news" id="news">
    </section>

    <section class="contacts" id="contacts">
    </section>

  </main>

  <footer>
    <div class="footer_conteiner">
      <img src="img/Logo_zaglyshka_footer2.png" alt="Логотип КЛЁВАЯ ДАЧА" class="logo_footer" />

      <div class="footer_text">
        © Клёвая Дача 2026. Все права защищены.
      </div>
    </div>
  </footer>
  <script>
    // Ждём загрузки страницы
    document.addEventListener('scroll', () => {
      const shapka = document.querySelector('.shapka');
      const nav = document.querySelector('.nav');

      if (window.scrollY > 50) {
        shapka.classList.add('shrink');
        nav.classList.add('shrink');
      } else {
        shapka.classList.remove('shrink');
        nav.classList.remove('shrink');
      }
    });
  </script>
</body>

</html>