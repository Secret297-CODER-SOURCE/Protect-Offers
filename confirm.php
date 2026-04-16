<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TEBRIKLER!</title>
  <link rel="shortcut icon" href="./img/logo.png" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="css/style.min.css" />

  <style>
    body {
      opacity: 1;
    }

    a,
    a:visited {
      text-decoration: none;
      color: inherit;
      display: block;
      height: 100%;
    }

    a:hover {
      text-decoration: none;
    }

    button {
      cursor: pointer;
    }

    button::-moz-focus-inner {
      padding: 0;
      border: 0;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
      font-size: inherit;
      font-weight: inherit;
    }

    /*==== Variables ==============*/

    /*---MAIN DARK THEME---*/

    :root {
      --header-bg: #1f1f1f;
      --main-bg: #232323;
      --main-heading-color: #ffffff;
      --text-color: #c8c8c8;
    }

    /*---DARK BLUE THEME---*/

    /* :root {
            --header-bg: #0A0721;
            --main-bg: #110E2B;
            --main-heading-color: #FFFFFF;
            --text-color: #C8C8C8;
        } */

    /*---LIGHT THEME---*/

    /* :root {
            --header-bg: #F8F8F8;
            --main-bg: #F8F8F8;
            --main-heading-color: #222222;
            --text-color: #484848;
        } */

    /*==== Basic ==================*/

    .main-wrapper {
      width: 100%;
      min-height: calc(100vh - 14rem);
      height: auto;
      margin: 0;
      padding: 0;
    }

    .container {
      width: 72%;
      min-height: calc(100vh - 5rem);
      margin: 0 auto;
      position: relative;
      z-index: 3;
      display: flex;
      align-items: center;
      justify-content: flex-start;
    }

    /*==== Header ==================*/

    .header {
      opacity: 1;
    }

    .logo-img {
      width: 9rem;
      max-width: 10rem;
      height: auto;
    }

    /*==== Main =====================*/

    .main {
      background-image: url(./img/top-content/bg.png);
      background-repeat: no-repeat;
      background-size: cover;
      background-position: center;
      width: 100%;
      min-height: calc(100vh - 7rem);
      height: 100vh;
      position: relative;
      overflow: hidden;
    }

    .overlay {
      display: none;
    }

    .main-content {
      padding: 2rem 0;
    }

    .completed-img-desk,
    .completed-img-mob {
      width: 16rem;
      height: auto;
      background: #fff;
      padding: 15px;
      border-radius: 12px;
    }

    .completed-img-mob {
      display: none;
    }

    .main-heading {
      text-align: center;
      color: var(--main-heading-color);
      font-weight: 700;
      font-size: 40px;
      line-height: 30px;
      margin: 3rem 0 2rem 0;
      font-family: "Montserrat", sans-serif;
    }

    .thnx-text {
      color: var(--text-color);
      max-width: 590px;
      font-weight: 400;
      font-size: 18px;
      line-height: 27px;
      font-family: "Montserrat", sans-serif;
    }

    .main-bg-img-desk,
    .main-bg-img-mob {
      position: absolute;
      top: 0;
      right: 0;
      height: 100%;
    }

    .main-bg-img-mob {
      display: none;
    }

    /*==== Counter ====================*/

    .counter-wrapper {
      max-width: 390px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 3rem;
      margin: 0 auto;
    }

    .numbers-wrapper {
      text-align: center;
      font-family: "Poppins", sans-serif;
    }

    .numbers-block-outer {
      width: 100px;
      height: 100px;
      padding: 1px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: -webkit-linear-gradient(#e60004, #980003);
      margin-bottom: 8px;
    }

    .numbers-block-inner {
      width: 100%;
      height: 100%;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
    }

    .current-number {
      background: -webkit-linear-gradient(#e60004, #980003);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-weight: 600;
      font-size: 48px;
    }

    .time-measure {
      color: var(--text-color);
      font-size: 18px;
      line-height: 27px;
    }

    @media (max-width: 768px) {
      .logo-img {
        width: 6rem;
        max-width: 6.6rem;
      }

      .container {
        width: 80%;
        min-height: calc(100vh - 3.5rem);
      }

      .main {
        background-position: bottom;
        min-height: calc(100vh - 5.5rem);
      }

      .completed-img-desk {
        display: none;
      }

      .completed-img-mob {
        display: block;
      }

      .main-heading {
        font-size: 32px;
        line-height: 39px;
      }

      .thnx-text {
        max-width: 420px;
        font-size: 20px;
        line-height: 24px;
      }

      .main-bg-img-desk {
        display: none;
      }

      .main-bg-img-mob {
        display: block;
      }

      .counter-wrapper {
        max-width: 320px;
        margin-top: 2.5rem;
      }

      .numbers-block-outer {
        width: 85px;
        height: 85px;
      }

      .current-number {
        font-size: 40px;
      }

      .time-measure {
        font-size: 16px;
        line-height: 24px;
      }
    }

    @media (max-width: 576px) {
      .main-content {
        text-align: center;
      }

      .completed-img-mob {
        margin: 0 auto;
      }

      .main-bg-img-mob {
        display: none;
      }

      .completed-img {
        width: 2rem;
      }

      .main-heading {
        font-size: 20px;
        line-height: 24px;
        margin: 1.5rem 0;
      }

      .thnx-text {
        max-width: 100%;
        font-size: 20px;
        line-height: 21px;
      }

      .counter-wrapper {
        margin: 2rem auto 0 auto;
      }

      .numbers-block-outer {
        width: 72px;
        height: 72px;
      }

      .current-number {
        font-size: 32px;
      }

      .time-measure {
        font-size: 14px;
        line-height: 21px;
      }
    }

    /*  PRELOADER  */

    .preloader {
      width: 100px;
      height: 100px;
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translateX(-50%) translateY(-50%);
      -webkit-animation: rotatePreloader 2s infinite ease-in;
      animation: rotatePreloader 2s infinite ease-in;
    }

    button .preloader {
      width: 20px;
      height: 20px;
    }

    @-webkit-keyframes rotatePreloader {
      0% {
        transform: translateX(-50%) translateY(-50%) rotateZ(0);
      }

      100% {
        transform: translateX(-50%) translateY(-50%) rotateZ(-360deg);
      }
    }

    @keyframes rotatePreloader {
      0% {
        transform: translateX(-50%) translateY(-50%) rotateZ(0);
      }

      100% {
        transform: translateX(-50%) translateY(-50%) rotateZ(-360deg);
      }
    }

    .preloader div {
      position: absolute;
      width: 100%;
      height: 100%;
      opacity: 0;
    }

    .preloader div:before {
      content: "";
      position: absolute;
      left: 50%;
      top: 0;
      width: 10%;
      height: 10%;
      background-color: var(--main-heading-color);
      transform: translateX(-50%);
      border-radius: 50%;
    }

    .preloader div:nth-child(1) {
      transform: rotateZ(0);
      -webkit-animation: rotateCircle1 2s infinite linear;
      animation: rotateCircle1 2s infinite linear;
      z-index: 9;
    }

    @-webkit-keyframes rotateCircle1 {
      0% {
        opacity: 0;
      }

      0% {
        opacity: 1;
        transform: rotateZ(36deg);
      }

      7% {
        transform: rotateZ(0);
      }

      57% {
        transform: rotateZ(0);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle1 {
      0% {
        opacity: 0;
      }

      0% {
        opacity: 1;
        transform: rotateZ(36deg);
      }

      7% {
        transform: rotateZ(0);
      }

      57% {
        transform: rotateZ(0);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .preloader div:nth-child(2) {
      transform: rotateZ(36deg);
      -webkit-animation: rotateCircle2 2s infinite linear;
      animation: rotateCircle2 2s infinite linear;
      z-index: 8;
    }

    @-webkit-keyframes rotateCircle2 {
      5% {
        opacity: 0;
      }

      5.0001% {
        opacity: 1;
        transform: rotateZ(0);
      }

      12% {
        transform: rotateZ(-36deg);
      }

      62% {
        transform: rotateZ(-36deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle2 {
      5% {
        opacity: 0;
      }

      5.0001% {
        opacity: 1;
        transform: rotateZ(0);
      }

      12% {
        transform: rotateZ(-36deg);
      }

      62% {
        transform: rotateZ(-36deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .preloader div:nth-child(3) {
      transform: rotateZ(72deg);
      -webkit-animation: rotateCircle3 2s infinite linear;
      animation: rotateCircle3 2s infinite linear;
      z-index: 7;
    }

    @-webkit-keyframes rotateCircle3 {
      10% {
        opacity: 0;
      }

      10.0002% {
        opacity: 1;
        transform: rotateZ(-36deg);
      }

      17% {
        transform: rotateZ(-72deg);
      }

      67% {
        transform: rotateZ(-72deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle3 {
      10% {
        opacity: 0;
      }

      10.0002% {
        opacity: 1;
        transform: rotateZ(-36deg);
      }

      17% {
        transform: rotateZ(-72deg);
      }

      67% {
        transform: rotateZ(-72deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .preloader div:nth-child(4) {
      transform: rotateZ(108deg);
      -webkit-animation: rotateCircle4 2s infinite linear;
      animation: rotateCircle4 2s infinite linear;
      z-index: 6;
    }

    @-webkit-keyframes rotateCircle4 {
      15% {
        opacity: 0;
      }

      15.0003% {
        opacity: 1;
        transform: rotateZ(-72deg);
      }

      22% {
        transform: rotateZ(-108deg);
      }

      72% {
        transform: rotateZ(-108deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle4 {
      15% {
        opacity: 0;
      }

      15.0003% {
        opacity: 1;
        transform: rotateZ(-72deg);
      }

      22% {
        transform: rotateZ(-108deg);
      }

      72% {
        transform: rotateZ(-108deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .preloader div:nth-child(5) {
      transform: rotateZ(144deg);
      -webkit-animation: rotateCircle5 2s infinite linear;
      animation: rotateCircle5 2s infinite linear;
      z-index: 5;
    }

    @-webkit-keyframes rotateCircle5 {
      20% {
        opacity: 0;
      }

      20.0004% {
        opacity: 1;
        transform: rotateZ(-108deg);
      }

      27% {
        transform: rotateZ(-144deg);
      }

      77% {
        transform: rotateZ(-144deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle5 {
      20% {
        opacity: 0;
      }

      20.0004% {
        opacity: 1;
        transform: rotateZ(-108deg);
      }

      27% {
        transform: rotateZ(-144deg);
      }

      77% {
        transform: rotateZ(-144deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .preloader div:nth-child(6) {
      transform: rotateZ(180deg);
      -webkit-animation: rotateCircle6 2s infinite linear;
      animation: rotateCircle6 2s infinite linear;
      z-index: 4;
    }

    @-webkit-keyframes rotateCircle6 {
      25% {
        opacity: 0;
      }

      25.0005% {
        opacity: 1;
        transform: rotateZ(-144deg);
      }

      32% {
        transform: rotateZ(-180deg);
      }

      82% {
        transform: rotateZ(-180deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle6 {
      25% {
        opacity: 0;
      }

      25.0005% {
        opacity: 1;
        transform: rotateZ(-144deg);
      }

      32% {
        transform: rotateZ(-180deg);
      }

      82% {
        transform: rotateZ(-180deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .preloader div:nth-child(7) {
      transform: rotateZ(216deg);
      -webkit-animation: rotateCircle7 2s infinite linear;
      animation: rotateCircle7 2s infinite linear;
      z-index: 3;
    }

    @-webkit-keyframes rotateCircle7 {
      30% {
        opacity: 0;
      }

      30.0006% {
        opacity: 1;
        transform: rotateZ(-180deg);
      }

      37% {
        transform: rotateZ(-216deg);
      }

      87% {
        transform: rotateZ(-216deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle7 {
      30% {
        opacity: 0;
      }

      30.0006% {
        opacity: 1;
        transform: rotateZ(-180deg);
      }

      37% {
        transform: rotateZ(-216deg);
      }

      87% {
        transform: rotateZ(-216deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .preloader div:nth-child(8) {
      transform: rotateZ(252deg);
      -webkit-animation: rotateCircle8 2s infinite linear;
      animation: rotateCircle8 2s infinite linear;
      z-index: 2;
    }

    @-webkit-keyframes rotateCircle8 {
      35% {
        opacity: 0;
      }

      35.0007% {
        opacity: 1;
        transform: rotateZ(-216deg);
      }

      42% {
        transform: rotateZ(-252deg);
      }

      92% {
        transform: rotateZ(-252deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle8 {
      35% {
        opacity: 0;
      }

      35.0007% {
        opacity: 1;
        transform: rotateZ(-216deg);
      }

      42% {
        transform: rotateZ(-252deg);
      }

      92% {
        transform: rotateZ(-252deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .preloader div:nth-child(9) {
      transform: rotateZ(288deg);
      -webkit-animation: rotateCircle9 2s infinite linear;
      animation: rotateCircle9 2s infinite linear;
      z-index: 1;
    }

    @-webkit-keyframes rotateCircle9 {
      40% {
        opacity: 0;
      }

      40.0008% {
        opacity: 1;
        transform: rotateZ(-252deg);
      }

      47% {
        transform: rotateZ(-288deg);
      }

      97% {
        transform: rotateZ(-288deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle9 {
      40% {
        opacity: 0;
      }

      40.0008% {
        opacity: 1;
        transform: rotateZ(-252deg);
      }

      47% {
        transform: rotateZ(-288deg);
      }

      97% {
        transform: rotateZ(-288deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .preloader div:nth-child(10) {
      transform: rotateZ(324deg);
      -webkit-animation: rotateCircle10 2s infinite linear;
      animation: rotateCircle10 2s infinite linear;
      z-index: 0;
    }

    @-webkit-keyframes rotateCircle10 {
      45% {
        opacity: 0;
      }

      45.0009% {
        opacity: 1;
        transform: rotateZ(-288deg);
      }

      52% {
        transform: rotateZ(-324deg);
      }

      102% {
        transform: rotateZ(-324deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    @keyframes rotateCircle10 {
      45% {
        opacity: 0;
      }

      45.0009% {
        opacity: 1;
        transform: rotateZ(-288deg);
      }

      52% {
        transform: rotateZ(-324deg);
      }

      102% {
        transform: rotateZ(-324deg);
      }

      100% {
        transform: rotateZ(-324deg);
        opacity: 1;
      }
    }

    .logo {
      max-width: 200px;
      width: 100%;
    }
  </style>
</head>

<body>
  <div class="wrapper">
    <header class="header _header-scroll">
      <div class="header__container">
        <a href="#form" class="header__logo"><img loading="lazy" class="logo" src="img/logo.png" alt="Koç" /></a>
        <div class="header__menu menu">
          <nav class="menu__body">
            <ul class="menu__list">
              <li class="menu__item">
                <a data-goto="#what" data-goto-header="" href="#form" class="menu__link">Avantajlar</a>
              </li>
              <li class="menu__item">
                <a data-goto="#how" data-goto-header="" href="#form" class="menu__link">Nasıl çalışır</a>
              </li>
              <li class="menu__item">
                <a data-goto="#why" data-goto-header="" href="#form" class="menu__link">Görüşler</a>
              </li>
              <li class="menu__item">
                <a data-goto="#calculator" data-goto-header="" href="#form" class="menu__link">Kâr Hesaplayıcı</a>
              </li>
              <li class="menu__item">
                <a data-goto="#faq" data-goto-header="" href="#form" class="menu__link">SSS</a>
              </li>
            </ul>
          </nav>
        </div>
        <div class="header__actions">
          <a href="#form" data-goto-header="" data-goto-top="25px" class="header__button button button--ttu"
            type="button" style="color: #000">
            Yatırıma Başla
          </a>
          <button type="button" class="menu__icon icon-menu">
            <span></span>
          </button>
        </div>
      </div>
    </header>
    <div class="main-wrapper">
      <div class="overlay"></div>
      <main class="main">
        <div class="container">
          <div class="main-content">
            <h1 class="main-heading">
              <span class="main-heading-1st" style="text-align: center; text-transform: uppercase">Tebrikler</span>
              <br /><br />
              <span class="main-heading-2nd" style="
                    font-size: 20px;
                    line-height: 130%;
                    text-align: center;
                    font-weight: 400;
                  ">Kontenjanınız ayrıldı. <br />
                Kaydınız başarıyla tamamlandı. Katılım sürecini başlatmak için
                yöneticimizin sizinle iletişime geçmesini bekleyin. Size iyi
                günler dileriz!
              </span>
            </h1>
            <div class="counter-wrapper">
              <div class="numbers-wrapper">
                <div class="numbers-block-outer">
                  <div class="numbers-block-inner">
                    <span class="current-number hours">23</span>
                  </div>
                </div>
                <span class="time-measure hours-span">saat</span>
              </div>
              <div class="numbers-wrapper">
                <div class="numbers-block-outer">
                  <div class="numbers-block-inner">
                    <span class="current-number minutes">59</span>
                  </div>
                </div>
                <span class="time-measure minutes-span">dakika</span>
              </div>
              <div class="numbers-wrapper">
                <div class="numbers-block-outer">
                  <div class="numbers-block-inner">
                    <span class="current-number seconds">59</span>
                  </div>
                </div>
                <span class="time-measure seconds-span">saniye</span>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
    <footer class="footer">
      <div class="footer__container">
        <div class="footer__items">
          <div class="footer__item">
            <a href="#form" class="footer__logo"><img loading="lazy" class="logo" src="img/logo.png" alt="Koç" /></a>
            <div class="footer__menu menu-footer">
              <!-- <nav class="menu-footer__body">
                <ul class="menu-footer__list">
                  <li class="menu-footer__item">
                    <a href="#form" class="menu-footer__link">TEBRIKLER</a>
                  </li>
                </ul>
              </nav> -->
            </div>
            <div class="footer__actions">
              <!-- <button
                  data-popup="#popup"
                  class="footer__button button button--ttu"
                  type="button"
                >
                  Videoyu izleyin
                </button> -->
              <a href="#form" class="footer__button button button--ttu" type="button" style="color: #000">
                Yatırıma Başla
              </a>
            </div>
          </div>
        </div>
      </div>
    </footer>
  </div>

  <script>
    /* COUNTER */

    var localStorage = window.localStorage,
      startDate;

    if (localStorage.getItem("startDate")) {
      startDate = localStorage.getItem("startDate");
    } else {
      startDate = new Date();
      localStorage.setItem("startDate", startDate);
    }
    setTimer(startDate);

    function setTimer(startDate) {
      var deadline = new Date(
        Date.parse(startDate) + 01 * 24 * 60 * 60 * 1000,
      );
      initializeClock("countdown", deadline);
    }

    // Countdown
    function getTimeRemaining(endtime) {
      var t = Date.parse(endtime) - Date.parse(new Date());
      var seconds = Math.floor((t / 1000) % 60);
      var minutes = Math.floor((t / 1000 / 60) % 60);
      var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
      return {
        total: t,
        hours: hours,
        minutes: minutes,
        seconds: seconds,
      };
    }

    function initializeClock(id, endtime) {
      var clock = document.querySelector(".counter-wrapper");
      var hoursSpan = clock.querySelector(".hours");
      var minutesSpan = clock.querySelector(".minutes");
      var secondsSpan = clock.querySelector(".seconds");

      function updateClock() {
        var t = getTimeRemaining(endtime);

        hoursSpan.innerHTML = ("0" + t.hours).slice(-2);
        minutesSpan.innerHTML = ("0" + t.minutes).slice(-2);
        secondsSpan.innerHTML = ("0" + t.seconds).slice(-2);

        if (t.total <= 0) {
          startDate = new Date();
          localStorage.setItem("startDate", startDate);
          var deadline = new Date(
            Date.parse(startDate) + 24 * 60 * 60 * 1000,
          );
          initializeClock("countdown", deadline);
          clearInterval(timeinterval);
        }
      }

      updateClock();
      var timeinterval = setInterval(updateClock, 1000);
    }
  </script>

  <div id="popup" aria-hidden="true" class="popup">
    <div class="popup__wrapper">
      <div class="popup__content">
        <div class="popup__actions">
          <button data-close type="button" class="popup__close">
            <svg width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path opacity="0.850056" d="M1.73333 1.48883L21.5469 21.0288" stroke="#0091ff" stroke-linecap="square" />
              <path opacity="0.850056" d="M21.2667 1.48883L1.45309 21.0288" stroke="#0091ff" stroke-linecap="square" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
  <script src="js/app.min.js?_v=20250130083147" defer></script>
  <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
</body>

</html>