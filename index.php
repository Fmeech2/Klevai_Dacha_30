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
      background-color: #0A0A0A;
      color: white;
      margin: 0;
      border: 0;
      padding: 0;
      flex-direction: column;
      align-content: flex-start;
      flex-wrap: nowrap;
      align-items: center;
    }

    .shapka {
      width: 1200px;
      display: flex;
      color: white;
      margin: 0;
      border: 0;
      padding: 0;
    }

    .logo {
      width: 180px;
      height: auto;
      margin: 20px;
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

    a.nav_link:active,
    a.nav_link {
      text-decoration: none;
      color: #d3d3d3;
    }

    a.nav_link:hover {
      text-decoration: none;
      color: #afafaf;
      background-color: #4b4b4b;
    }

    .nav {
      padding-bottom: 20px;
      background-color: #66666600;
      font-size: 20px;
    }

    .nav_link {
      margin: 8px;
      background-color: #2e2e2e;
      padding: 8px 24px;
      border-radius: 10px;
    }


    main {
      width: 1200px;
      display: flex;
      margin-left: auto;
      margin-right: auto;
      background-color: #2c2c2cb4;
      color: white;
      font-size: 18px;
      flex-direction: column;

    }

    section {
      margin: 20px;

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
        <div>
          Астраханская область, Камызякский район,
        </div>
        <div>
          п. Ильинка Ильинка
        </div>
        <div>
          на реке Гандурино
        </div>
      </div>

      <div class="shapka_text_right">
        <div>
          Звони в любое время!
        </div>
        <div>
          Телефон: 8 (911) 172-55-58
        </div>
      </div>
    </div>
    <nav class="nav">
      <a href="#hero" class="nav_link">Главная</a>
      <a href="#gallery" class="nav_link">Фото</a>
      <a href="#reviews" class="nav_link">Отзывы</a>
      <a href="#news" class="nav_link">Новости</a>
      <a href="#contacts" class="nav_link">Контакты</a>
    </nav>
  </header>
  <main>

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
</body>

</html>