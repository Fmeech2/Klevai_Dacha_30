<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Клёвая Дача 30</title>
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
      background: #2F80ED;
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
.shapka_text_right_3 img {
  width: 32px;
  padding: 0;
  margin: 6px 5px 12px 0;
  transition: all 0.25s ease;          /* плавная анимация */
  cursor: pointer;
  transform-origin: center center;     /* точка вращения — центр иконки */
    text-decoration: none;
}

/* При наведении */
.shapka_text_right_3 img:hover {
  transform: scale(1.25) rotate(5deg); /* увеличение + поворот на 5° */
  filter: brightness(1.1);             /* чуть ярче (по желанию) */
}

/* При нажатии */
.shapka_text_right_3 img:active {
  transform: scale(1.05) rotate(2deg); /* лёгкое уменьшение + меньший поворот */
  filter: brightness(0.95);
  transition: all 0.1s ease;           /* быстрее реагирует на клик */
}

    .shapka.shrink .logo {
      transition: all 0.4s ease;
      height: auto;
      width: 45px;
      margin-left: 100px;
      /*filter: brightness(0) invert(1) opacity(1); Сделать картинку ЧБ Белой*/
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
      display: none;
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
      justify-content: center;
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
      display: none;
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
      margin-top: -613px;
      margin-right: -589px;
    }

    .gallery {
      margin-left: auto;
      margin-right: auto;
      width: 1160px;
      display: flex;
      flex-wrap: wrap;
      display: flex;
      margin-top: 0px;
    }

    .gallery_title {
      width: auto;
      font-size: 45px;
      font-weight: bold;
      margin-top: -60px;
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





    
    /* Обёртка логотипа */
.logo-wrapper {
  position: relative;
  display: inline-block;
  z-index: 10;
}

/* Основной логотип */
.logo-wrapper .logo {
  position: relative;
  z-index: 3;                 /* логотип поверх крюка */
  width: 100px;
  height: auto;
  transition: all 0.4s ease;
}

/* Крюк (изначально спрятан под логотипом) */
.logo-wrapper .hook {
  position: absolute;
  top: 10px;
  left: 10px;
  width: 100px;                /* подбери размер под свой крюк */
  height: auto;
  z-index: 1;                 /* под логотипом */
  opacity: 1;
  pointer-events: none;
  transition: all 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* При наведении на логотип — крюк выезжает вниз */
.logo-wrapper:hover .hook {
  opacity: 1;
  transform: translate(0px, 86px) scale(1);  /* ≈ 100px вниз */
}
.hook_background{
  position: absolute;
  top: 0px;
  left: 10px;
  width: 100px;                /* подбери размер под свой крюк */
  height: 116px;
  z-index: 2;                 /* под логотипом */
  opacity: 1;
  pointer-events: none;
  transition: all 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);

}
.logo-wrapper:hover .hook_background {
  opacity: 1;
  transform: translate(0px, 0px) scale(1);  /* ≈ 100px вниз */
}
.shrink .hook_background{
  transform: translate(0px, -200px) scale(1);  /* ≈ 100px вниз */
}
.shrink .hook{
  transform: translate(0px, -200px) scale(1);  /* ≈ 100px вниз */
}
.shrink .hook_background{
  transform: translate(0px, -200px) scale(1);  /* ≈ 100px вниз */
  top: -200px;
}
.shrink .hook{
  transform: translate(0px, -200px) scale(1);  /* ≈ 100px вниз */
  top: -200px;
}
.logo_messenger{
  
    text-decoration: none;
    height: 56px;
  }
  .shapka_text_right_3{
    height: 56px;

  }
  .shapka_text_right_1{
    margin-top: 16px;
  }


  .nav {
  display: flex;
  gap: 3px;                    /* расстояние между кнопками */
  padding: 17px 0 0px;
  margin: 0;
  width: 1200px;
  justify-content: right;
  margin-left: auto;
  margin-right: auto;
}

.nav_link {
  text-decoration: none;
}

.nav_link button {
  height: 36px;
  padding: 0 28px;
  border: none;
  border-radius: 9999px;        /* полностью круглые края */
  background-color: #F3F4F6;    /* светло-серый как на картинке */
  color: #1F2937;               /* тёмно-серый текст */
  font-size: 16px;
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.25s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* При наведении */
.nav_link button:hover {
  background-color: #E5E7EB;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}

/* При нажатии */
.nav_link button:active {
  transform: translateY(0);
  background-color: #D1D5DB;
  box-shadow: none;
}



.gallery {
width: 1240px;          /* или убери, если нужно на всю ширину */
  margin: 0px auto 0;
  padding: 0 0px;            /* если хочешь без боковых отступов — поставь 0 */
  box-sizing: border-box;
}

.gallery_title {
  text-align: center;
  font-size: 45px;
  font-weight: bold;
  margin-bottom: 30px;
}

/* Контейнер картинок */
.gallery_images {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;                  /* расстояние между картинками */
  justify-content: flex-start;
}

/* Базовый стиль всех картинок */
.gallery_img {
  height: 400px;              /* ← одинаковая высота для всех */
  object-fit: cover;          /* картинка подстраивается, не сжимается */
  border-radius: 10px;
  display: block;
  transition: transform 0.3s ease;
  margin-left: 0;
  margin-right: 0;
}

/* Маленькая картинка (по умолчанию) */
.gallery_img,
.gallery_img.small {
  width: 285px;
}

/* Большая картинка */
.gallery_img.large {
  width: 590px;
}

/* Небольшой hover-эффект (по желанию) */
.gallery_img:hover {
  transform: scale(1.02);
}
  </style>


  <header>
    <div class="shapka">
      <div class="shapka_center">
        <div class="logo-wrapper">
          <img src="img/Logo_zaglyshka_footer2.png" alt="Логотип КЛЁВАЯ ДАЧА" class="logo" />
          <img src="Синий фон.png" alt="Крюк" class="hook_background" />
          <img src="Крюк PNG.png" alt="Крюк" class="hook" />
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
              <img src="VK PNG.png" alt="Телеграм КЛЁВАЯ ДАЧА" class="logo " />
            </a>
              <a href="https://t.me/klevayadacha" target="_blank" class="logo_messenger">
              <img src="Max PNG.png" alt="Телеграм КЛЁВАЯ ДАЧА" class="logo " />
            </a>
            <a href="https://api.whatsapp.com/send?phone=79171725558" target="_blank" class="logo_messenger">
              <img src="WhatsApp PNG.png" alt="Телеграм КЛЁВАЯ ДАЧА" class="logo " />
            </a>
            <a href="https://t.me/+79171725558" target="_blank" class="logo_messenger">
              <img src="Telegram PNG.png" alt="Телеграм КЛЁВАЯ ДАЧА" class="logo " />
            </a>

          </div>
        </div>
      </div>
    </div>
  </header>
  <main>

    <section class="header_background" id="header_background"></section>     
     <nav class="nav">
      <a href="#hero" class="nav_link"><button>Главная</button></a>
      <a href="#gallery" class="nav_link"><button>Фото</button></a>
      <a href="#reviews" class="nav_link"><button>Отзывы</button></a>
      <a href="#news" class="nav_link"><button>Новости</button></a>
      <a href="#contacts" class="nav_link"><button>Контакты</button></a>
    </nav>
    <img src="Сайт рыбалки заглушка.png" alt="Логотип КЛЁВАЯ ДАЧА" class="logo_footer" style="width: 1284px; margin: auto;" />
    <section class="hero" id="hero">
      
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
          <script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A673fbc03c9a505d1f076be50421331af568be73dd1821bf600b252d53241b5a3&amp;width=556&amp;height=433&amp;lang=ru_RU&amp;scroll=true"></script>
        </div>
      </div>
    </section>

    <section class="gallery" id="gallery">
      <div class="gallery_title">
        Фотогалерея
      </div>
           <nav class="nav" style="justify-content: left; padding-left: 0; margin-left: 0;">
      <a href="#hero" class="nav_link"><button>Фото</button></a>
      <a href="#gallery" class="nav_link"><button>Фото от посетителей</button></a>
    </nav>
    
<div class="gallery">
<div class="gallery_images">

  <!-- Вариант 1: 4 маленьких -->
  <img src="img/image 2.png" alt="..." class="gallery_img">
  <img src="img/image 3.png" alt="..." class="gallery_img">
  <img src="img/image 4.png" alt="..." class="gallery_img">
  <img src="img/image 5.png" alt="..." class="gallery_img">

  <!-- Вариант 2: 1 большая + 2 маленьких -->
  <img src="img/image 6.png" alt="..." class="gallery_img small">
  <img src="img/image 11.png" alt="..." class="gallery_img small">
  <img src="img/image 8.png" alt="..." class="gallery_img large">

  <!-- Вариант 3: 2 больших -->
  <img src="img/image 9.png" alt="..." class="gallery_img large">
  <img src="img/image 12.png" alt="..." class="gallery_img small">
  <img src="img/image 13.png" alt="..." class="gallery_img small">

  
  <img src="img/image 14.png" alt="..." class="gallery_img large">
  <img src="img/image 7.png" alt="..." class="gallery_img large">

  
  <img src="img/image 15.png" alt="..." class="gallery_img large">
  <img src="img/image 10.png" alt="..." class="gallery_img small">
  <img src="img/image 16.png" alt="..." class="gallery_img small">

</div>
 <img src="img/Отзывы.png" alt="Логотип КЛЁВАЯ ДАЧА" class="logo_footer" style="width: 1200px; margin: auto; margin-top: 98px;" />
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