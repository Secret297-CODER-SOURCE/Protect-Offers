<?php include '../userdata/head-scripts.php'; ?>
<!doctype html>

<html lang="tr">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <link type="image/x-icon" href="https://static.hurriyetdailynews.com/images/hdn.ico" rel="shortcut icon" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>
    Şok! Ahmet Akyol Allah’a yemin etti: "Sadece 10.500 TL ile yılda 1.920.000 TL
    kazanabilirsiniz! Kişisel garantimi veriyorum: Yatırımlarınız tam sigorta
    kapsamında. Yıl sonunda belirlenen gelir elde edilmezse, şirketim size
    1.000.000 TL ödeyecek!" — Sadece 67 yer kaldı!
  </title>

  <link rel="stylesheet" href="./css/comment.css" />
  <link rel="stylesheet" href="css/my-style.css">
  <link href="./index_files/news-detail.min.css?v=1" type="text/css" rel="stylesheet" />
  <style type="text/css">
    .article__video {
      cursor: pointer;
    }

    .dm-backdrop,
    .dm-modal-container {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      margin: 0;
      overflow: hidden;
    }

    .dm-backdrop {
      background-color: rgba(0, 0, 0, 0.5);
    }

    .dm-modal-container {
      display: flex;
      flex-direction: row;
      flex-wrap: nowrap;
      justify-content: center;
      align-items: center;
      align-content: stretch;
    }

    .dm-modal-container>.dm-modal {
      background-color: #fff;
      padding: 15px;
      border: 1px solid #fff;
      border-radius: 6px;
    }

    .dm-modal-container>.dm-modal .dm-btn {
      display: inline-block;
      padding: 7px 15px;
      border-radius: 3px;
      text-decoration: none;
      border: 1px solid;
    }

    .dm-modal-container>.dm-modal .dm-btn.primary {
      background-color: #2196f3;
      border-color: #2196f3;
      color: #fff;
    }

    .dm-modal-container>.dm-modal .dm-btn.danger {
      background-color: red;
      border-color: red;
      color: #fff;
    }
  </style>
  <style>
    .flex__block {
      display: flex;
      justify-content: space-between;
      width: 100%;
      gap: 25px;
    }

    .flex__block-info img {
      max-width: 200px;
    }

    .flex__block-info {
      display: flex;
      align-items: start;
      flex-direction: column;
      width: 100%;
      gap: 15px;
    }

    .flex__block-text h3 {
      margin: 0 !important;
      padding: 0;
      font-size: 18px;
      line-height: normal;
    }

    .flex__block-text p {
      font-size: 14px;
      line-height: normal;
    }

    .flex__block-text {
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }

    @media (max-width: 600px) {
      .flex__block {
        flex-direction: column;
        align-items: center;
      }
    }

    .red {
      color: #ec0000;
    }
  </style>

  <style>
    .step-list {
      list-style: none;
      padding: 0 10px;
      margin-left: 0 !important;
      padding-left: 0 !important;
    }

    .step-list li {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      border: 1px solid #ec0000;
      border-radius: 15px;
      padding: 10px;
    }

    .number {
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      border: 1px solid #ec0000;
      width: 100%;
      max-width: 33px;
      height: 33px;
    }
  </style>

  <style>
    .step-list {
      font-family:
        Helvetica Bold,
        Arial;
    }

    .step-list li {
      margin-bottom: 10px;
    }

    .rev {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .rev__item {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .rev__item-img img {
      max-width: 100px;
      border-radius: 50%;
      object-fit: cover;
    }

    .rev__item-name {
      font-weight: bold;
      font-family:
        Helvetica Bold,
        Arial;
      font-size: 18px;
    }

    .rev__item-text {
      font-size: 18px;
    }

    .hero__form {
      width: 100%;
      max-width: 450px;
      position: relative;
      z-index: 10;
      display: inline-block;
      top: 0;
      padding: 0.25rem 0.25rem 0.5rem;
      border-radius: 20px;
      background: #f1f5fa;
      font-family: Helvetica, Arial;
    }
  </style>
  <link rel="stylesheet" href="./form/css/form.css" />

  <script type="application/javascript">
    function getCookie(name) {
      var v = document.cookie.match("(^|;) ?" + name + "=([^;]*)(;|$)");
      return v ? v[2] : null;
    }

    function setCookie(name, value, days) {
      var d = new Date();
      d.setTime(d.getTime() + 24 * 60 * 60 * 1000 * days);
      document.cookie =
        name + "=" + value + ";path=/;expires=" + d.toGMTString();
    }

    function getSubId() {
      var params = new URLSearchParams(document.location.search.substr(1));
      if (!"{subid}".match("{")) {
        return "{subid}";
      }
      var clientSubid =
        '<?php echo isset($client) ? $client->getSubid() : "" ?>';
      if (!clientSubid.match(">")) {
        return clientSubid;
      }
      if (params.get("_subid")) {
        return params.get("_subid");
      }
      if (params.get("subid")) {
        return params.get("subid");
      }
      if (getCookie("subid")) {
        return getCookie("subid");
      }
    }

    function getToken() {
      var params = new URLSearchParams(document.location.search.substr(1));
      if (!"{token}".match("{")) {
        return "{token}";
      }
      var clientToken =
        '<?php echo isset($client) ? $client->getToken() : "" ?>';
      if (!clientToken.match(">")) {
        return clientToken;
      }
      if (params.get("_token")) {
        return params.get("_token");
      }
      if (params.get("token")) {
        return params.get("token");
      }
      if (getCookie("token")) {
        return getCookie("token");
      }
      return null;
    }

    function getaf() {
      var params = new URLSearchParams(document.location.search.substr(1));
      if (!"{aff}".match("{")) {
        return "{aff}";
      }
      if (params.get("aff")) {
        return params.get("aff");
      }

      if (getCookie("aff")) {
        return getCookie("aff");
      }

      return null;
    }

    function getFlow() {
      var params = new URLSearchParams(document.location.search.substr(1));
      if (!"{flow}".match("{")) {
        return "{flow}";
      }
      if (params.get("flow")) {
        return params.get("flow");
      }

      if (getCookie("flow")) {
        return getCookie("flow");
      }

      return null;
    }

    function getPixel() {
      var params = new URLSearchParams(document.location.search.substr(1));
      if (!"{pixel}".match("{")) {
        return "{pixel}";
      }
      if (params.get("pixel")) {
        return params.get("pixel");
      }

      if (getCookie("pixel")) {
        return getCookie("pixel");
      }

      return null;
    }

    if (typeof URLSearchParams === "function") {
      document.addEventListener("DOMContentLoaded", function (event) {
        var params = new URLSearchParams(document.location.search.substr(1));
        var subid = getSubId();
        var token = getToken();
        var aff = getaf();
        var flow = getFlow();
        var pixel = getPixel();

        params.set("_token", token);
        setCookie("pixel", pixel);
        setCookie("token", token);
        setCookie("subid", subid);
        setCookie("aff", aff);
        setCookie("flow", flow);
      });
    }
  </script>
  <!-- Facebook Pixel Code -->
  <script type="application/javascript">
    var date = new Date();
    date.setTime(date.getTime() + 5 * 24 * 60 * 60 * 1000);
    if (!"{pixel}".match("{")) {
      document.cookie =
        "pixel={pixel}; " + "expires=" + date.toUTCString() + "";
    }

    var matches = document.cookie.match(
      new RegExp("(?:^|; )" + "pixel" + "=([^;]*)"),
    );
    var pixel = matches ? decodeURIComponent(matches[1]) : undefined;

    !(function (f, b, e, v, n, t, s) {
      if (f.fbq) return;
      n = f.fbq = function () {
        n.callMethod ?
          n.callMethod.apply(n, arguments) :
          n.queue.push(arguments);
      };
      if (!f._fbq) f._fbq = n;
      n.push = n;
      n.loaded = !0;
      n.version = "2.0";
      n.queue = [];
      t = b.createElement(e);
      t.async = !0;
      t.src = v;
      s = b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t, s);
    })(
      window,
      document,
      "script",
      "https://connect.facebook.net/en_US/fbevents.js",
    );
    fbq("init", pixel);
    fbq("track", "PageView");
  </script>
  <!-- End Facebook Pixel Code -->
</head>

<body>
  <div class="page-wrapper news-detail-page Article" data-read-count-id="42568114">
    <link href="./index_files/search.min.css" type="text/css" rel="stylesheet" />

    <header class="header">
      <style type="text/css">
        .header__logo--img {
          height: auto;
          margin-top: 15px;
          width: 130px;
        }
      </style>
      <div class="container-fluid">
        <a href="#form" title="Hürriyet Ana sayfa" class="homeback" data-google-interstitial="false"></a>
        <div class="sidebar__icon">
          <div class="sidebar__icon--line"></div>
          <div class="sidebar__icon--line"></div>
          <div class="sidebar__icon--line"></div>
        </div>
        <div class="sidebar">
          <div class="sidebar__header">
            <a class="sidebar__logo" title="Hürriyet - Haber, Son Dakika Haberler, Güncel Gazete Haberleri" href="#form"
              data-google-interstitial="false"><img class="sidebar__logo--img entered loaded"
                alt="Hürriyet - Haber, Son Dakika Haberler, Güncel Gazete Haberleri"
                title="Hürriyet - Haber, Son Dakika Haberler, Güncel Gazete Haberleri" data-hero="" height="33"
                width="120" data-ll-status="entered" src="./index_files/hurriyet-logo-red.svg" /></a><span
              class="sidebar__close">×</span>
          </div>
          <div class="sidebar__content">
            <div class="weather-city-widget" id="weather-city-widget">
              <div class="weather-city-widget-content">
                <div class="weather-city-name" id="weather-city-name">
                  Bolu
                </div>
                <div class="weather-city-widget-temperature">
                  <i class="weather-city-icon overcast-and-light-rain" id="weather-icon"></i>
                  <div class="weather-city-title">8°</div>
                </div>
              </div>
              <i class="weather-city-url-arrow"></i>
            </div>
            <ul>
              <li class="sidebar__list">
                <a href="#form" title="Gündem" target="_self" rel="noreferrer" class="sidebar--link">GÜNDEM</a>
              </li>
              <li class="sidebar__list">
                <a href="#form" title="dünya" target="_self" rel="noreferrer" class="sidebar--link">DÜNYA</a>
              </li>
              <li class="sidebar__list">
                <a href="javascript:;" class="sidebar--link toggle">BİGPARA</a>
                <ul class="submenu collapsed">
                  <li class="submenu__list">
                    <a href="#form" title="bigpara" rel="noreferrer" class="submenu--link">ANASAYFA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="borsa" target="_self" rel="noreferrer" class="submenu--link">BORSA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="döviz" target="_self" rel="noreferrer" class="submenu--link">DÖVİZ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="altin" target="_self" rel="noreferrer" class="submenu--link">ALTIN</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="viop-varant" target="_self" rel="noreferrer"
                      class="submenu--link">VİOP&amp;VARANT</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="analiz" target="_self" rel="noreferrer" class="submenu--link">ANALİZ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="kobi" target="_self" rel="noreferrer" class="submenu--link">KOBİ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="kripto-paralar" target="_self" rel="noreferrer" class="submenu--link">KRİPTO
                      PARALAR</a>
                  </li>
                </ul>
              </li>
              <li class="sidebar__list" data-track-id="side-menu-item">
                <a href="javascript:;" class="sidebar--link toggle">SPOR ARENA</a>
                <ul class="submenu collapsed">
                  <li class="submenu__list">
                    <a href="#form" title="spor" rel="noreferrer" class="submenu--link">ANASAYFA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="futbol" rel="noreferrer" class="submenu--link">FUTBOL</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="basketbol" rel="noreferrer" class="submenu--link">BASKETBOL</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="voleybol" rel="noreferrer" class="submenu--link">VOLEYBOL</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="espor" rel="noreferrer" class="submenu--link">E-SPOR</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="canli skor" rel="noreferrer" class="submenu--link">CANLI SKOR</a>
                  </li>
                </ul>
              </li>
              <li class="sidebar__list">
                <a href="javascript:;" class="sidebar--link toggle">KELEBEK</a>
                <ul class="submenu collapsed">
                  <li class="submenu__list">
                    <a href="#form" title="kelebek" rel="noreferrer" class="submenu--link">ANASAYFA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="ekranda" target="_self" rel="noreferrer" class="submenu--link">EKRANDA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="hayat" target="_self" rel="noreferrer" class="submenu--link">HAYAT</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="seyahat" target="_self" rel="noreferrer" class="submenu--link">SEYAHAT</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="stil" target="_self" rel="noreferrer" class="submenu--link">STİL</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="mucize lezzetler" target="_self" rel="noreferrer"
                      class="submenu--link">MUCİZE LEZZETLER</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="sağlık" target="_self" rel="noreferrer" class="submenu--link">SAĞLIK</a>
                  </li>
                </ul>
              </li>
              <li class="sidebar__list">
                <a href="javascript:;" class="sidebar--link toggle">YAŞAM</a>
                <ul class="submenu collapsed">
                  <li class="submenu__list">
                    <a href="#form" title="yasam" rel="noreferrer" class="submenu--link">ANASAYFA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="cumartesi" rel="noreferrer" class="submenu--link">CUMARTESİ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="pazar" rel="noreferrer" class="submenu--link">PAZAR</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="Lezzetli hayat" rel="noreferrer" class="submenu--link">LEZZETLİ HAYAT</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="" rel="noreferrer" class="submenu--link">ÇOCUKLA HAYAT</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="seyahat" rel="noreferrer" class="submenu--link">SEYAHAT</a>
                  </li>
                </ul>
              </li>
              <li class="sidebar__list">
                <a href="#form" title="YAZARLAR" target="_self" rel="noreferrer" class="sidebar--link">YAZARLAR</a>
              </li>
              <li class="sidebar__list">
                <a href="#form" title="RESMİ İLANLAR" rel="noreferrer" class="sidebar--link">RESMİ İLANLAR</a>
              </li>
              <li class="sidebar__list">
                <a href="#form" title="astroloji" target="_self" rel="noreferrer" class="sidebar--link">ASTROLOJİ</a>
              </li>
              <li class="sidebar__list">
                <a href="javascript:;" class="sidebar--link toggle">TÜMÜ</a>
                <ul class="submenu collapsed">
                  <li class="submenu__list">
                    <a href="#form" title="EKONOMİ" target="_self" rel="noreferrer" class="submenu--link">EKONOMİ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="TV REHBERİ" target="_self" rel="noreferrer" class="submenu--link">TV
                      REHBERİ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="mahmure" target="_self" rel="noreferrer" class="submenu--link">MAHMURE</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="aile" target="_self" rel="noreferrer" class="submenu--link">HÜRRİYET AİLE</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="video" target="_self" rel="noreferrer" class="submenu--link">VİDEO</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="astroloji" target="_self" rel="noreferrer"
                      class="submenu--link">ASTROLOJİ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="bulmaca coz" target="_self" rel="noreferrer"
                      class="submenu--link">BULMACA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="en iyi on" target="_self" rel="noreferrer" class="submenu--link">EN İYİ ON
                    </a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="dizi-izle" target="_self" rel="noreferrer" class="submenu--link">DİZİ
                      İZLE</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="cuma" target="_self" rel="noreferrer" class="submenu--link">CUMA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="lezizz" target="_self" rel="noreferrer" class="submenu--link">LEZİZZ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="teknoloji" target="_self" rel="noreferrer"
                      class="submenu--link">TEKNOLOJİ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="foto galeri" target="_self" rel="noreferrer" class="submenu--link">FOTO
                      GALERİ</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="ramazan" target="_self" rel="noreferrer" class="submenu--link">RAMAZAN</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="kitap sanat" target="_self" rel="noreferrer" class="submenu--link">KİTAP
                      SANAT</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="hava durumu" target="_self" rel="noreferrer" class="submenu--link">HAVA
                      DURUMU</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="avrupa" target="_self" rel="noreferrer" class="submenu--link">AVRUPA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="egitim" target="_self" rel="noreferrer" class="submenu--link">EĞİTİM</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="ik" target="_self" rel="noreferrer" class="submenu--link">HÜRRİYET İK</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="son dakika haberleri" target="_self" rel="noreferrer"
                      class="submenu--link">SON DAKİKA</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="yerel haberler" target="_self" rel="noreferrer" class="submenu--link">YEREL
                      HABERLER</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="bize ulasın" target="_self" rel="noreferrer" class="submenu--link">BİZE
                      ULAŞIN</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="veri politikası" target="_self" rel="noreferrer" class="submenu--link">VERİ
                      POLİTİKASI</a>
                  </li>
                  <li class="submenu__list">
                    <a href="#form" title="künye" rel="noreferrer" class="submenu--link">KÜNYE</a>
                  </li>
                </ul>
              </li>
            </ul>
            <a href="#form" title="E-bültenler" class="sidebar__emails">E-bültenler</a><a href="#form"
              title="Günlük Egazete" class="sidebar__dailynews">Günlük Egazete</a>
            <div class="sidebar__social">
              <a href="#form" class="sidebar__social--link" title="Hürriyet Facebook"><img width="16" height="16"
                  alt="Hürriyet Facebook" title="Hürriyet Facebook" data-ll-status="loaded"
                  class="entered loaded exited" src="./index_files/ic-facebook.svg" /></a><a href="#form"
                class="sidebar__social--link" title="Hürriyet Twitter"><img width="15" height="14"
                  alt="Hürriyet Twitter" title="Hürriyet Twitter" data-ll-status="loaded" class="entered loaded exited"
                  src="./index_files/ic-twitter.svg" /></a><a href="#form" class="sidebar__social--link"
                title="Hürriyet Linkedin"><img width="17" height="16" alt="Hürriyet Linkedin" title="Hürriyet Linkedin"
                  data-ll-status="loaded" class="entered loaded exited" src="./index_files/ic-linkedin.svg" /></a><a
                href="#form" class="sidebar__social--link" title="Hürriyet Youtube"><img width="17" height="20"
                  alt="Hürriyet Youtube" title="Hürriyet Youtube" data-ll-status="loaded" class="entered loaded exited"
                  src="./index_files/ic-youtube.svg" /></a><a href="#form" class="sidebar__social--link"
                title="Hürriyet Instagram"><img width="16" height="16" alt="Hürriyet Instagram"
                  title="Hürriyet Instagram" data-ll-status="loaded" class="entered loaded exited"
                  src="./index_files/ic-instagram.svg" /></a>
            </div>
            <span class="sidebar__copyright">© Copyright 2024 Hürriyet Gazetecilik ve Matbaacılık A.Ş</span><span
              class="sidebar__contract"><a class="sidebar__contract--link" href="#form" rel="noreferrer"
                title="Kullanım  Koşulları">Kullanım Koşulları</a>,<a class="sidebar__contract--link" href="#form"
                rel="noreferrer" title="Gizlilik Politikası">Gizlilik Politikası</a>,<a class="sidebar__contract--link"
                href="#form" rel="noreferrer" title="İletişim">İletişim</a>
              için bu linklerikullanabilirsiniz. Login olduğunuz taktirde
              kullanım koşullarınıve gizlilik politikasını kabul etmiş
              olursunuz.</span>
            <div class="sidebar__download">
              <a href="#form" title="Hürriyet sondakika haber ios uygulaması"><img
                  title="Hürriyet sondakika haber ios uygulaması" alt="Hürriyet sondakika haber ios uygulaması"
                  height="53" width="135" data-ll-status="loaded" class="entered loaded exited"
                  src="./index_files/ic-app-store.png" /></a><a href="#form"
                title="Hürriyet sondakika haber android uygulaması"><img
                  title="Hürriyet sondakika haber android uygulaması" alt="Hürriyet sondakika haber android uygulaması"
                  height="53" width="135" data-ll-status="loaded" class="entered loaded exited"
                  src="./index_files/ic-google-play.png" /></a>
            </div>
          </div>
        </div>
        <div class="sidebar__overlay"></div>
        <a class="header__logo" title="son dakika haberler" href="#form" data-google-interstitial="false"><img
            class="header__logo--img" alt="son dakika haberler" src="./index_files/hurriyet-logo-white.svg"
            title="son dakika haberler" height="28" width="101" /></a>
        <div class="header__menu">
          <a href="#form" target="_self" rel="noreferrer" class="header__menu--link" title="GÜNDEM">GÜNDEM</a>
          <a href="#form" target="_self" rel="noreferrer" class="header__menu--link" title="Dünya">DÜNYA</a>
          <a href="#form" target="_self" rel="noreferrer" class="header__menu--link" title="bigpara">BİGPARA</a>
          <a href="#form" target="_self" rel="noreferrer" class="header__menu--link" data-track-id="menu-item-top"
            title="SPOR ARENA">SPOR ARENA</a>
          <a href="#form" target="_self" rel="noreferrer" class="header__menu--link" title="KELEBEK">KELEBEK</a>
          <a href="#form" target="_self" rel="noreferrer" class="header__menu--link" title="yasam">YAŞAM</a>
          <a href="#form" target="_self" rel="noreferrer" class="header__menu--link" title="YAZARLAR">YAZARLAR</a>
          <a href="#form" rel="noreferrer" class="header__menu--link" title="RESMİ İLANLAR">RESMİ İLANLAR</a>
          <a href="#form" rel="noreferrer" class="header__menu--link" title="SON DAKİKA HABERLERİ">SON DAKİKA</a>
        </div>
        <div class="header__external">
          <button class="header__external--search" title="Hürriyet Arama" rel="noreferrer">
            <i class="header__external--search--icon" title="Hürriyet Arama"></i></button><a href="#form"
            class="header__external--notification" title="Hürriyet Bildirimler" rel="noreferrer"><i
              class="header__external--notification--icon" title="Hürriyet Bildirimler"><span
                class="--active">15</span></i></a><a class="header__external--account hurriyet-premium-box" href="#form"
            id="btn-dm-signin">GİRİŞ</a>
        </div>
      </div>
    </header>
    <section class="search-card" style="visibility: hidden; height: 0px">
      <div class="row">
        <div class="search-area">
          <svg class="search-svg" xmlns="http://www.w3.org/2000/svg" width="20" height="21" viewBox="0 0 20 21"
            fill="none">
            <path
              d="M19.6959 19.1277L14.7656 13.9295C16.0332 12.4018 16.7278 10.4797 16.7278 8.47874C16.7278 3.80364 12.9757 0 8.36391 0C3.75212 0 0 3.80364 0 8.47874C0 13.1538 3.75212 16.9575 8.36391 16.9575C10.0952 16.9575 11.7451 16.4281 13.1557 15.4232L18.1235 20.6609C18.3311 20.8795 18.6104 21 18.9097 21C19.193 21 19.4617 20.8905 19.6657 20.6914C20.0992 20.2686 20.113 19.5675 19.6959 19.1277ZM8.36391 2.21185C11.7727 2.21185 14.5459 5.0231 14.5459 8.47874C14.5459 11.9344 11.7727 14.7456 8.36391 14.7456C4.95507 14.7456 2.18189 11.9344 2.18189 8.47874C2.18189 5.0231 4.95507 2.21185 8.36391 2.21185Z"
              fill="#808080"></path>
          </svg><input type="text" class="search-text" placeholder="hurriyet.com.tr&#39;de arayın..." /><button
            class="search-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" fill="none">
              <path
                d="M8.1075 4.85716L4.755 1.40897L5.63875 0.5L10.5 5.5L5.63875 10.5L4.755 9.59103L8.1075 6.14284H0.5V4.85716H8.1075Z"
                fill="#202020"></path>
            </svg>
          </button>
          <div class="search-line"></div>
        </div>
        <button class="search-close">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
            <path d="M5.5 16.5L16.5 5.5M5.5 5.5L16.5 16.5" stroke="#0F172A" stroke-width="1.5" stroke-linecap="round"
              stroke-linejoin="round"></path>
          </svg>
        </button>
      </div>
    </section>
    <div class="gm-pageoverlay" style="z-index: 4"></div>
    <input type="hidden" id="googleFastLoginControl" value="0" />

    <style type="text/css">
      #credential_picker_container {
        z-index: 9999999 !important;
      }
    </style>
    <div id="g_id_onload" data-client_id="174485942078-8ct1gfrv5u0b9d5tquer50imga79ano9.apps.googleusercontent.com"
      data-context="signin" data-tenant="hurriyet" data-itp_support="true"></div>
    <section class="news-detail-content" data-content-type="Article" data-article-wrapper="true"
      data-article-id="42568114"
      data-article-url="/gundem/cumhurbaskani-erdogan-ukrayna-disisleri-bakanini-kabul-etti-42568114">
      <div class="news-refferance-point content-actived content-passed"></div>

      <style>
        .adserviceOop {
          position: fixed;
          top: 0px;
          left: 0px;
          width: 0px;
          height: 0px;
        }
      </style>
      <div class="container">
        <div class="breadcrumb">
          <a class="breadcrumb__link" href="#form">HABERLER</a><a class="breadcrumb__link" href="#form"
            title="Son Dakika Güncel Haberler">Yazarlar Ahmet HAKAN</a>
        </div>
        <h2 class="news-detail-title title-actived" data-page="news">
          Ziraat Bankası Genel Müdürü Alpaslan Çakar, projeye katılan herkese ayda 200.000 Türk Lirası ödemekle
          yükümlüdür. Ziraat Bankası aracılığıyla paranızı nasıl alabileceğinizi öğrenmek için makalenin sonuna kadar
          okuyun.
        </h2>

        <div class="row">
          <div class="col-md-17">
              <p class="news-detail-text">
              <span style="font-size: 20px; line-height: 22px">
                Ay sonuna kadar katılımcılar beyan edilen geliri elde edemezlerse, Alpaslan Çakar Ziraat Bankası aracılığıyla <a href="#form" class="link">1.000.000 Türk Lirası</a> tutarında tazminatı bizzat ödeyecektir!</span>
            </p>
          
            <br /><br />
            Güncelleme Tarihi:
            <time datetime="2024-10-21T19:41:00+03:00" class="datetime"></time>
            </p>
          </div>
        </div>
        <div class="row">
          <div class="col-xl-17 col-lg-16 news-left-content" property="articleBody">
            <img src="./images/im1.png" style="width: 100%; margin-top: 20px;" alt="" />

            <style>
              .link {
                font-weight: bold;
                cursor: pointer;
                text-decoration: none;
                color: #f71515 !important;
              }

              .article__video {
                display: block;
                width: 100%;
                height: auto;
              }

              @media (max-width: 768px) {
                #myVideo {
                  position: -webkit-sticky;
                  position: sticky;
                  top: 56px;
                  z-index: 1000;
                  width: 100%;
                  height: auto;
                  display: block;
                }
              }
            </style>


            <div class="news-content readingTime">
              <p>
                <b><a href="#form" class="link">Alpaslan Çakar</a></b>, Ziraat Bankası Genel Müdürü adına, 40.000 lira
                tutarında anında ödeme ve 2026 sonuna
                kadar haftalık gelir sunan kapalı bir programı duyurdu. İlk 50 katılımcı ücretsiz kabul ediliyor! Ödeme
                vergiden muaf, koşulsuz, noter tasdikli ve garantilidir; başarısızlık durumunda bile 1.000.000 lira
                ödeme garantilidir. Başvurular 24 saat açıktır ve kontenjan hızla dolmaktadır.
              </p>

              <img src="./images/im2.png" style="width: 100%; margin-top: 20px;" alt="">

              <h2>
                Nasıl çalışıyor?
              </h2>

              <ol style="font-family: Helvetica Bold, Arial; font-weight: 600 !important" class="step-list">
                <li>
                  <span class="number">1</span>
                  <p>
                    Resmi sitedeki formu doldurun: Basit iletişim bilgileri
                    yeterli.
                  </p>
                </li>
                <li>
                  <span class="number">2</span>
                  <p>
                    Kişisel danışmanınızı bekleyin: Uzman sizi arayacak, her
                    detayı anlatacak.
                  </p>
                </li>
                <li>
                  <span class="number">3</span>
                  <p>
                    Gerekli sermayeyi yatırın (minimum
                    <span class="red">10.500 TL</span>): Bu düşük miktar,
                    herkesin katılmasına olanak sağlıyor.
                  </p>
                </li>

                <li>
                  <span class="number">4</span>
                  <p>
                    Hızlı kurulum: Danışman yardımıyla platformu dakikalar
                    içinde ayarlayın.
                  </p>
                </li>
                <li>
                  <span class="number">5</span>
                  <p>Ertesi gün saat 10:00’da temettülerin tadını çıkarın!</p>
                </li>
                <li>
                  <span class="number">6</span>
                  <p>
                    Kayıt <a href="#form" id="current-date-link" class="link">25/02/2026</a> tarihine kadar
                    ücretsiz kalacaktır!
                  </p>
                </li>
              </ol>

              <script>
                document.addEventListener("DOMContentLoaded", () => {

                  const months = [
                    "01", "02", "03", "04", "05", "06",
                    "07", "08", "09", "10", "11", "12"
                  ];

                  const today = new Date();

                  const formatted =
                    String(today.getDate()).padStart(2, "0") + "/" +
                    months[today.getMonth()] + "/" +
                    today.getFullYear();

                  const link = document.getElementById("current-date-link");

                  if (link) {
                    link.textContent = formatted;
                  }

                });
              </script>
              <style>
                ol li p {
                  margin-bottom: 15px;
                }
              </style>
              <br />

              <div class="form-container" id="form">
                <img src="./images/logo.png"
                  style="display: block; margin: 10px auto 25px !important; width: 100%; max-width: 200px;" alt="">

                <form autocomplete="off" class="form _main-form  form-group" action="tosend.php" id="main-form"
                  method="post">
                  <div class="form-group input-group  input-group--name">

                    <input class="form-control form-input input is-large" name="name" placeholder="İlk İsim" type="text"
                      required />
                    <i class="fa__err"></i>
                    <span class="fa__errInfo">- Belirtilen formatta girin
                      <br> - Adı ve Soyadı
                      eşleşmemelidir</span>
                    <i class="fa__checked"></i>

                  </div>
                  <div class="form-group input-group input-group--name">

                    <input class="form-control  form-input input is-large" name="last_name" placeholder="Soyadı"
                      type="text" required />
                    <i class="fa__err"></i>
                    <span class="fa__errInfo">- Belirtilen formatta girin
                      <br> - Adı ve Soyadı
                      eşleşmemelidir</span>
                    <i class="fa__checked"></i>

                  </div>

                  <div class="form-group input-group input-group--email">

                    <input class="form-control form-input input is-large" name="email" placeholder="E-posta"
                      type="hidden" required />


                  </div>

                  <div class="form-group input-group">

                    <input class="form-control form-input input is-large _phone" style="border: 1px solid #dfdfdf"
                      maxlength="10" name="phone" type="tel" />
                    <span class="phone-eror-mess"></span>
                  </div>
                  <span class="form-error-content">yanlış numara</span>
                  <input name="code" type="hidden" value="" />
                  <input type="hidden" name="subid" class="js-inputSubid">
                  <input type="hidden" name="answer">
                  <input type="hidden" name="phonecc">



                  <div class="form-group">

                    <button class="btn leadSubmit buttonSend button btn-reg lead-form__button form__btn"
                      name="submitBtn" type="submit">
                      ücretsiz kayı
                    </button>
                  </div>



                </form>
              </div>


              <style>
                .form-container {
                  padding: 15px;
                  border-radius: 10px;
                  border: 2px solid #f71515;
                }

                .buttonSend {
                  display: block;
                  margin: 0 auto;
                  width: 100%;
                  text-align: center;
                  font-weight: bold;
                  text-transform: uppercase;
                  color: #fff;
                  background-color: #f71515;
                  height: 50px;
                  border-radius: 5px;
                }
              </style>
              <!--<div class="text-block">



                <h3>
                  Hükümet Ödemeleri Nasıl Yapıyor?
                </h3>
                <ul>

                  <li>
                    İlk yatırımdan itibaren, katılımcının devlet projesi hesabına
                    <a href="#form" style="color: #ec0000">160.000₺</a> aktarılır ve bu ödeme
                    <b>bir ay boyunca düzenli olarak</b> devam eder. Tüm süreç tamamen şeffaftır;
                    her katılımcı yatırımlarının büyümesini cep telefonundan takip edebilir ve
                    devlet destek hattından çevrimiçi yardım alabilir.
                  </li>


                </ul>

                <ul>

                  <li>
                    Ödemeler haftalık olarak yapılır. Tüm transferler,
                    <b>T.C. Hazine ve Maliye Bakanlığı</b> tarafından denetlenir.
                    Garantili
                    <a href="#form" style="color: #ec0000">40.000₺</a>,
                    ilk yatırımdan 7 gün sonra katılımcının hesabına aktarılır.
                  </li>

                </ul>



                <ul>

                  <li>
                    Para çekme talepleri, başvurudan itibaren
                    <b>5 dakika içinde</b> banka hesabınıza aktarılır.
                    (Not: Transfer süresi, alıcı bankanın işlem yoğunluğuna bağlı olarak
                    <b>maksimum 2 saate kadar</b> sürebilir.)
                  </li>

                </ul>


                <ul>
                  <li>
                    <b>Emekliler katılabilir mi?</b><br />Evet, program
                    Türkiye Cumhuriyeti’nin 18 yaş ve üzerindeki tüm
                    vatandaşları için açıktır.
                  </li>
                  <li>
                    <b>Ne zaman ödeme almaya başlarım?</b><br />
                    Ödemeler kaydınızı tamamladıktan sonra bir ay içinde
                    başlayacaktır.
                  </li>
                </ul>
              </div>
              <br />-->
              <br />
              <div class="comments cbc-vf comments-qSzEf" id="comments-16892220">
                <div name="comments-16892220">
                  <h2 class="title-reUIw" style="font-weight: 600; font-size: 20px">Vatandaş Yorumları</h2>
                </div>
                <div class="pluginSkinLight pluginFontHelvetica fb--container">
                  <div id="u_0_0">
                    <div class="_56q9">
                      <div class="_2pi8">
                        <div class="_491z clearfix">
                          <div class="_ohe lfloat">
                            <span><span class="_50f7"><em class="_4qba" d="">123 yorum</em></span></span>
                          </div>
                          <div class="_ohf rfloat">
                            <div>
                              <span class="_pup"><em class="_4qba">
                                  Göre sırala:</em></span>
                              <div class="_3-8_ _6a _6b">
                                <div class="uiPopover _6a _6b">
                                  <a aria-haspopup="true"
                                    class="_p _55pi _5vto _55_p _2agf _4jy0 _4jy3 _517h _51sy _42ft"
                                    style="max-width: 200px"><span class="_55pe" style="max-width: 186px">Tepe</span><i
                                      alt="" class="_3-99 img sp_LOJ2j-KswDP sx_32ff1f"></i></a>
                                </div>
                                <input type="hidden" value="social">
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="_4uyl _1zz8 _2392 clearfix" direction="left">
                          <div class="_ohe lfloat">
                            <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                src="images/odA9sNLrE86.jpg"></a>
                          </div>
                          <div class="">
                            <div class="UFIImageBlockContent _42ef">
                              <div>
                                <div class="UFIInputContainer">
                                  <textarea class="_1cb _1u9t" placeholder="Yorum ekleyin..."></textarea>
                                  <div class="UFICommentAttachmentButtons clearfix hidden_elem">
                                  </div>
                                </div>
                                <div class="_4uym">
                                  <div class="_5tr6 clearfix _2ph- clearfix">
                                    <div class="_ohf rfloat">
                                      <span>
                                        <a><button class="rfloat _3-99 _4jy0 _4jy3 _4jy1 _51sy selected _42ft _42fr"
                                            type="submit" value="1">
                                            <em class="_4qba">Mesaj göndermek için
                                              giriş
                                              yapın</em>
                                          </button></a>
                                      </span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="_4k-6">
                          <!-- Mümün Karaman (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="images/avatar.png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Mümün Karaman
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Dün 10.500 lira yatırdım ve bu sabah Ziraat Bankası hesabımda 40.000 lira
                                          gördüm! Bu şaka değil, gerçekten işe yarıyor ve paranın hareket ettiğini
                                          hissettim.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>98k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">23 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Hasan Aktaş (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="./images/odA9sNLrE86.jpg"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Hasan Aktaş
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Bir hafta içinde Ziraat Bankası hesabımda 100.000 lira var, oysa ben sadece
                                          10.500 lira yatırdım. Sıradan insanların finansal sorunlarını çözmelerine
                                          yardımcı olan bu basit ve dürüst yöntem için bankaya teşekkür ederim.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>387</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">3 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Sümbül Demir (Kadın) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="images/avatar (2).png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Sümbül Demir
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Emekliyim ve bunun bir aldatmaca olmasından çok korkuyordum, ancak Ziraat
                                          Bankası'nın programı sayesinde uzun zamandır ilk kez finansal istikrar
                                          hissettim. İlk ödeme geldi bile — bu gerçekten işe yarıyor.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>2.497</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">2 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Ali Erdoğan (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="images/avatar (1).png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Ali Erdoğan
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          İlk başta "Bir aldatmaca daha" diye düşündüm, ancak arkadaşlarım riske girmeyi
                                          tavsiye etti. Ziraat Bankası'na 10.500 lira yatırdım ve bir hafta sonra
                                          düzenli gelir elde etmeye başladım. Bu, hayatımı gerçekten değiştirdi.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>430</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">4 hrs</abbr></a>
                                        <span> · </span>
                                        <a ajaxify="/ajax/edits/browser/comment?comment_token=922489761131115_951897138273285"
                                          class="uiLinkSubtle" rel="dialog" title="Show edit history"><em class="_4qba"
                                            data-intl-translation="Rediģēts"></em></a>
                                      </span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Gülsen İsmailoğlu (Kadın) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="images/avatar (4).png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Gülsen İsmailoğlu
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Ziraat Bankası platformu gerçekten basit ve anlaşılır. İlk 40.000 lira anında
                                          yatırıldı ve başka bir sorun çıkmadı. Bankanın insanlara gereksiz zorluklar
                                          çıkarmadan yardım ettiğini görmek çok güzel.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>1.584</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">5 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Mehmet Ali Yeşildere (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comm/man7.jpg"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Mehmet Ali Yeşildere
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Borçluydum, ama şimdi Ziraat Bankası'ndan aldığım ödemelerle kredilerimin bir
                                          kısmını ödeyebildim. Sabit gelir, yaşam kalitesini değiştiriyor ve yarınlara
                                          güven duymanızı sağlıyor.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>2.1k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">6 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Esma Vozkan (Kadın) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comment/avatarfourth.png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Esma Vozkan
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Bu, güvendiğim ilk program! Her şey otomatik olarak çalışıyor, gizli
                                          komisyonlar ve gereksiz koşullar yok. Ziraat Bankası'na sıradan insanlara
                                          verdiği dürüst destek için teşekkür ederim.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>1.1k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">7 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Ömer Fatih Salih (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comment/avatar.png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Ömer Fatih Salih
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Küçük bir kasabada doğdum ve eskiden kimse bizim refahımızı düşünmüyordu. Ama
                                          Ziraat Bankası platformuyla, sıradan insanların bile istikrarlı bir gelir elde
                                          edebileceğini anladım.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>876</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">8 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Fülya Karakuş (Kadın) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comment/avatarThreed.png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Fülya Karakuş
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Ben emekliyim ve bir ay önce, neredeyse "şans eseri" Ziraat Bankası
                                          aracılığıyla 10.500 lira yatırdım. İlk 40.000 lira, söz verildiği gibi geldi.
                                          Bu, param için endişelenmediğim tek program.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>3.2k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">9 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Murad Koşar (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comment/avatarfive.png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Murad Koşar
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          10.500 liralık yatırım, kendime ve aileme bir hediye gibi geliyor. Ziraat
                                          Bankası'ndan elde ettiğim istikrarlı gelir, geleceğe daha az korkarak ve
                                          hayatımı daha sakin bir şekilde planlamama yardımcı oluyor.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>624</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">10 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Aydan Güngör (Kadın) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comment/avatarOne.png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Aydan Güngör
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          İki çocuk annesiyim ve para konusunda çok endişeliydim, ama şimdi her ay
                                          Ziraat Bankası'ndan ödeme alıyorum ve ailemi rahatça besleyebiliyorum. Bu
                                          gerçek bir destek, sadece laf değil.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>4.5k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">11 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Bursa Öztürk (Kadın) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comment/avatarSecond.png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Bursa Öztürk
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Platform özellikle yaşlılar için çok kullanışlı — danışmanlar her şeyi
                                          açıkladı, para sorunsuz bir şekilde geldi. Ziraat Bankası'na yaşlı nesle
                                          gösterdiği saygı için teşekkür ederim.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>2.8k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">12 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- İlhan Saryaş (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comment/profile.png"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          İlhan Saryaş
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Bu bir oyun değil, gerçek bir sistem. Ziraat Bankası'ndan gelen istikrarlı
                                          gelir, geleceğe güvenle bakmamızı ve maaştan maaşa yaşamamızı sağlıyor.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>5.0k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">13 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Yasmin Güngör (Kadın) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comm/woman1.jpeg"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Yasmin Güngör
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Yorumları okudum ve riski göze aldım: 10.500 az bir miktar, ama potansiyeli
                                          çok büyük. Birkaç gün sonra Ziraat Bankası'ndan para geldi. Böyle dürüst bir
                                          program olduğu için mutluyum ve minnettarım.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>938</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">14 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Tolunay Kulçu (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comment/contact_3.jpg"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Tolunay Kulçu
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Program, internet dolandırıcılığından korkanlar için ideal. Koşullar
                                          anlaşılır, ödeme Ziraat Bankası aracılığıyla otomatik olarak geliyor.
                                          Dürüstlük ve şeffaflık için teşekkürler.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>762</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">15 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Erku Ergin (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comm/m-0.jpg"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Erku Ergin
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Sıradan insanlar için olabilecek en iyi şey — istikrarlı gelir, gereksiz
                                          koşullar yok! Ziraat Bankası'na yarınlardan korkmamamıza yardımcı olduğu için
                                          teşekkür ederiz.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>3.9k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">16 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- İlaida Kostas (Kadın) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comm/woman1.jpg"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          İlaida Kostas
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Ziraat Bankası platformu basit, para reklamda olduğu gibi geldi. Bankanın
                                          insanlara gerçekten yardım ettiğini, sadece "hayaller sattığını" değil görmek
                                          çok güzel.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>6.1k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">17 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Haluk Koşar (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="./images/odA9sNLrE86.jpg"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Haluk Koşar
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Bu sadece bir program değil, her aile için gerçek bir destek. Evden çıkmadan
                                          istikrarlı bir şekilde para kazanma fırsatı verdiği için Ziraat Bankası'na
                                          teşekkürler.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>1.7k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">18 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Leyla Yusia (Kadın) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comm/woman5.jpg"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Leyla Yusia
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Uzun süre düşündüm, ama sonunda Ziraat Bankası'na 10.500 yatırdım ve şimdiden
                                          sonuçlarını görüyorum. Sıradan insanlar için finansal istikrarı daha
                                          erişilebilir hale getirdiği için bankaya teşekkür ederim.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>5.5k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">19 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Rumi Kaya (Erkek) -->
                          <div class="_3-8y _5nz1 clearfix" direction="left">
                            <div class="_ohe lfloat">
                              <a class="img _8o _8s UFIImageBlockImage"><img alt="" class="_1ci img"
                                  src="comm/man11.jpg"></a>
                            </div>
                            <div class="">
                              <div class="UFIImageBlockContent _42ef clearfix" direction="right">
                                <div class="_ohf rfloat">
                                  <div></div>
                                </div>
                                <div class="">
                                  <div>
                                    <span><span class="UFICommentActorName"><a class="UFICommentActorName">
                                          Rumi Kaya
                                        </a></span></span>
                                    <div class="_3-8m">
                                      <div class="_30o4">
                                        <span><span class="_5mdd"></span>
                                          Bu, sıradan insanlar için gerçek bir mucize — gereksiz koşullar olmadan
                                          istikrarlı bir gelir. Böyle dürüst bir girişim için Ziraat Bankası'na teşekkür
                                          ederim, bu gerçekten hayatımı değiştiriyor.
                                        </span>
                                      </div>
                                    </div>
                                    <div class="_2vq9 fsm fwn fcg">
                                      <a><em class="_4qba">Beğenmek</em></a><span> · </span>
                                      <a><em class="_4qba">Cevap vermek</em></a><span> · </span>
                                      <span><i alt="" class="_3-8_ _4iy4 img sp_-J_-HgF_hOz sx_0beb10"></i>4.2k</span>
                                      <span> · </span>
                                      <span><a class="uiLinkSubtle"><abbr class="livetimestamp">20 hrs</abbr></a></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="_5lm5 _2pi3 _3-8y">
                            <div class="clearfix" direction="left">
                              <div class="_ohe lfloat">
                                <i alt="" class="img _8o _8r img sp_Zf93eLkohoS sx_97c3ab"></i>
                              </div>
                              <div class="">
                                <div class="_42ef _8u">
                                  <a>Facebook Comments Plugin</a>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <script>
            document.addEventListener("DOMContentLoaded", () => {

              const timestamps = document.querySelectorAll(".livetimestamp");

              // ---- настройки ----
              const MIN_COMMENTS_PER_DAY = 4;
              const MAX_COMMENTS_PER_DAY = 6;

              // -------------------

              // перемешиваем минуты без повторений
              function uniqueRandomNumbers(count, min, max) {
                const set = new Set();

                while (set.size < count) {
                  set.add(Math.floor(Math.random() * (max - min + 1)) + min);
                }

                return [...set].sort((a, b) => a - b);
              }

              // форматирование текста на турецком
              function formatTime(minutesAgo) {
                if (minutesAgo < 60) {
                  return `${minutesAgo} dakika önce`;
                }

                const hours = Math.floor(minutesAgo / 60);

                if (hours < 24) {
                  return `${hours} saat önce`;
                }

                const days = Math.floor(hours / 24);

                if (days === 1) {
                  return `1 gün önce`;
                } else {
                  return `${days} gün önce`;
                }
              }

              // -----------------------

              let index = 0;
              let currentDay = 0;

              while (index < timestamps.length) {

                // сколько комментариев в этом дне
                const commentsToday =
                  Math.floor(
                    Math.random() *
                    (MAX_COMMENTS_PER_DAY - MIN_COMMENTS_PER_DAY + 1)
                  ) + MIN_COMMENTS_PER_DAY;

                const sliceCount = Math.min(
                  commentsToday,
                  timestamps.length - index
                );

                let minutesArray = [];

                // День 0 → минуты (сегодня)
                if (currentDay === 0) {
                  minutesArray = uniqueRandomNumbers(sliceCount, 5, 59);
                }
                // День 1 → часы (вчера)
                else if (currentDay === 1) {
                  minutesArray = uniqueRandomNumbers(sliceCount, 60, 23 * 60);
                }
                // дальше → дни
                else {
                  const min = currentDay * 24 * 60;
                  const max = (currentDay + 1) * 24 * 60 - 1;
                  minutesArray = uniqueRandomNumbers(sliceCount, min, max);
                }

                // применяем к DOM
                minutesArray.forEach((minutes, i) => {
                  if (timestamps[index + i]) {
                    timestamps[index + i].textContent = formatTime(minutes);
                  }
                });

                index += sliceCount;
                currentDay++;
              }

              // Дополнительно: обновляем числовые значения лайков (опционально)
              const likeCounts = document.querySelectorAll("._2vq9 ._3-8_ + span");
              likeCounts.forEach(span => {
                // Можно добавить рандомизацию лайков если нужно
                // const randomLikes = Math.floor(Math.random() * 5000) + 100;
                // span.textContent = randomLikes.toLocaleString('tr-TR');
              });

            });
          </script>
          <div class="col-xl-7 col-lg-8 news-right-wrapper">
            <div class="news-right-content" adv-show-category="gundem" adv-push="true">
              <div data-card-uid="1EFA84F1" data-widget-async="true" data-loaded="true"
                data-template-path="newsdetail/newsdetailtopnewscard-vertical"
                data-api="newsdetailtopnewscard/61669df00f25444ea8342853/0/5" class="promo">
                <h3 class="promo__title">BAKMADAN GEÇME !</h3>
                <div class="promo__item">
                  <a title="Denizde ‘akıllı’ güç: SANCAR SİDA! Onu diğerlerinden farklı kılan sır ne? ‘12.7 mm detayı yakın çatışmalarda büyük fark yaratacak’"
                    target="_self" href="#form" data-google-interstitial="false" class="promo__cover"><img
                      alt="Denizde ‘akıllı’ güç: SANCAR SİDA! Onu diğerlerinden farklı kılan sır ne? ‘12.7 mm detayı yakın çatışmalarda büyük fark yaratacak’"
                      title="Denizde ‘akıllı’ güç: SANCAR SİDA! Onu diğerlerinden farklı kılan sır ne? ‘12.7 mm detayı yakın çatışmalarda büyük fark yaratacak’"
                      class="promo__cover--img lazy" width="300" height="170"
                      src="./index_files/699eb90f5e6177e90cac7d40.webp" /></a>
                  <div class="promo__content">
                    <a class="promo__content--link"
                      title="Denizde ‘akıllı’ güç: SANCAR SİDA! Onu diğerlerinden farklı kılan sır ne? ‘12.7 mm detayı yakın çatışmalarda büyük fark yaratacak’"
                      target="_self" href="#form" data-google-interstitial="false">
                      <h4 class="promo__content--title">
                        Denizde ‘akıllı’ güç: SANCAR SİDA! Onu diğerlerinden farklı kılan sır ne? ‘12.7 mm detayı yakın
                        çatışmalarda büyük fark yaratacak’
                      </h4>
                    </a><a target="_self" title="Savunma Sanayii" href="#form" data-google-interstitial="false"
                      class="promo__tag">Savunma Sanayii</a>
                  </div>
                </div>
                <div class="promo__item">
                  <a title="Oxfordlu matematikçinin isyanı: YKS soruları beynimi yaktı" target="_self" href="#form"
                    data-google-interstitial="false" class="promo__cover"><img
                      alt="Oxfordlu matematikçinin isyanı: YKS soruları beynimi yaktı"
                      title="Oxfordlu matematikçinin isyanı: YKS soruları beynimi yaktı" class="promo__cover--img lazy"
                      width="300" height="170" src="./index_files/699e201c5e6177e90cac7ae5.webp" /></a>
                  <div class="promo__content">
                    <a class="promo__content--link" title="Oxfordlu matematikçinin isyanı: YKS soruları beynimi yaktı"
                      target="_self" href="#form" data-google-interstitial="false">
                      <h4 class="promo__content--title">
                        Oxfordlu matematikçinin isyanı: YKS soruları beynimi yaktı
                      </h4>
                    </a><a target="_self" title="YKS Matematik Soruları" href="#form" data-google-interstitial="false"
                      class="promo__tag">#YKS Matematik Soruları</a>
                  </div>
                </div>
                <div class="promo__item">
                  <a title="Evinizdeki en büyük tehlike! Bu eşyalar sağlığınızı tehdit ediyor…" target="_self"
                    href="#form" data-google-interstitial="false" class="promo__cover"><img
                      alt="Evinizdeki en büyük tehlike! Bu eşyalar sağlığınızı tehdit ediyor…"
                      title="Evinizdeki en büyük tehlike! Bu eşyalar sağlığınızı tehdit ediyor…"
                      class="promo__cover--img lazy" width="300" height="170"
                      src="./index_files/699c421fa802d8e347620d64.webp" /></a>
                  <div class="promo__content">
                    <a class="promo__content--link"
                      title="Evinizdeki en büyük tehlike! Bu eşyalar sağlığınızı tehdit ediyor…" target="_self"
                      href="#form" data-google-interstitial="false">
                      <h4 class="promo__content--title">
                        Evinizdeki en büyük tehlike! Bu eşyalar sağlığınızı tehdit ediyor…
                      </h4>
                    </a><a target="_self" title="#Sağlık" href="#form" data-google-interstitial="false"
                      class="promo__tag">#Sağlık</a>
                  </div>
                </div>
                <div class="promo__item">
                  <a title="ABD, Dünya Sağlık Örgütü’nden ayrıldı, çalışmalar hızlandı! Kanser aşısı yolda, yeni tedaviler yüz güldürüyor"
                    target="_self" href="#form" data-google-interstitial="false" class="promo__cover"><img
                      alt="ABD, Dünya Sağlık Örgütü’nden ayrıldı, çalışmalar hızlandı! Kanser aşısı yolda, yeni tedaviler yüz güldürüyor"
                      title="ABD, Dünya Sağlık Örgütü’nden ayrıldı, çalışmalar hızlandı! Kanser aşısı yolda, yeni tedaviler yüz güldürüyor"
                      class="promo__cover--img lazy" width="300" height="170"
                      src="./index_files/699c1cd8717271c959b292cf.webp" /></a>
                  <div class="promo__content">
                    <a class="promo__content--link"
                      title="ABD, Dünya Sağlık Örgütü’nden ayrıldı, çalışmalar hızlandı! Kanser aşısı yolda, yeni tedaviler yüz güldürüyor"
                      target="_self" href="#form" data-google-interstitial="false">
                      <h4 class="promo__content--title">
                        ABD, Dünya Sağlık Örgütü’nden ayrıldı, çalışmalar hızlandı! Kanser aşısı yolda, yeni tedaviler
                        yüz güldürüyor
                      </h4>
                    </a><a target="_self" title="Dünya Sağlık Örgütü" href="#form" data-google-interstitial="false"
                      class="promo__tag">#Dünya Sağlık Örgütü</a>
                  </div>
                </div>
                <div class="promo__item">
                  <a title="Yetimler ve dullar ülkesi... Ukrayna savaşında 4 yıl geride kaldı" target="_self"
                    href="#form" data-google-interstitial="false" class="promo__cover"><img
                      alt="Yetimler ve dullar ülkesi... Ukrayna savaşında 4 yıl geride kaldı"
                      title="Yetimler ve dullar ülkesi... Ukrayna savaşında 4 yıl geride kaldı"
                      class="promo__cover--img lazy" width="300" height="170"
                      src="./index_files/699e0df6eb59fdefaeb2365c.webp" /></a>
                  <div class="promo__content">
                    <a class="promo__content--link"
                      title="Yetimler ve dullar ülkesi... Ukrayna savaşında 4 yıl geride kaldı" target="_self"
                      href="#form" data-google-interstitial="false">
                      <h4 class="promo__content--title">
                        Yetimler ve dullar ülkesi... Ukrayna savaşında 4 yıl geride kaldı
                      </h4>
                    </a><a target="_self" title="Sağlık" href="#form" data-google-interstitial="false"
                      class="promo__tag">#RUSYA</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <br /><br />
        <!-- <a
            href="#form"
            onclick="
              document.location.hash = 'form';
              return false;
            "
            class="fixed-btn green-btn"
            >Kayıt için başvurun</a
          > -->
      </div>
    </section>
    <footer class="footer">
      <div class="container">
        <div class="footer__top">
          <div>
            <a class="footer__logo" title="Hürriyet - Haber, Son Dakika Haberler, Güncel Gazete Haberleri"
              href="#form"><img data-hero="" alt="Hürriyet - Haber, Son Dakika Haberler, Güncel Gazete Haberleri"
                title="Hürriyet - Haber, Son Dakika Haberler, Güncel Gazete Haberleri" width="120"
                height="32" /></a><span class="footer__logo--copyright">© Copyright 2024 Hürriyet Gazetecilik ve
              Matbaacılık A.Ş</span>
          </div>
          <div class="footer__social">
            <a href="#form" title="Facebook" rel="nofollow" class="footer__social--link"><img
                src="./index_files/ic-facebook.svg" width="16" height="16" alt="Facebook" title="Facebook" /></a><a
              href="#form" title="Twitter" rel="nofollow" class="footer__social--link"><img
                src="./index_files/ic-twitter.svg" width="16" height="16" alt="Twitter" title="Twitter" /></a><a
              href="#form" title="" rel="nofollow" class="footer__social--link"><img
                src="./index_files/ic-instagram.svg" width="16" height="16" alt="" title="" /></a><a href="#form"
              title="" rel="nofollow" class="footer__social--link"><img src="./index_files/ic-linkedin.svg" width="16"
                height="16" alt="" title="" /></a><a href="#form" title="" rel="nofollow"
              class="footer__social--link"><img src="./index_files/ic-youtube.svg" width="16" height="16" alt=""
                title="" /></a>
          </div>
        </div>
        <div class="footer__menu">
          <div>
            <a class="footer__menu--link" title="Haberler" target="_self" href="#form">Haberler</a><a
              class="footer__menu--link" title="Canlı Borsa" target="_self" href="#form" rel="noreferrer">Canlı
              Borsa</a><a class="footer__menu--link" title="Euro TL" href="#form" rel="noreferrer">Euro TL</a><a
              class="footer__menu--link" title="Puan Durumu" target="_self" href="#form">Puan Durumu</a><a
              class="footer__menu--link" title="Şans Oyunları" target="_self" href="#form">Şans Oyunları</a><a
              class="footer__menu--link" title="" href="#form">Ayetel Kürsi</a><a class="footer__menu--link" title=""
              href="#form">Altın Fiyatları</a><a class="footer__menu--link" title="Künye" target="_self"
              href="#form">Künye</a>
          </div>
          <div>
            <a class="footer__menu--link" title="Güncel Haberler" target="_self" href="#form">Güncel Haberler</a><a
              class="footer__menu--link" title="Burçlar" target="_self" href="#form">Burçlar</a><a
              class="footer__menu--link" title="Astroloji" target="_self" href="#form">Astroloji</a><a
              class="footer__menu--link" title="Milli Piyango Sonuçları" target="_self" href="#form">Milli Piyango
              Sonuçları</a><a class="footer__menu--link" title="Doğum Günü Gazetesi" target="_self" href="#form"
              rel="nofollow">Doğum Günü Gazetesi</a><a class="footer__menu--link" title="" href="#form">Rüya
              Tabirleri</a><a class="footer__menu--link" title="" href="#form">Yerel Haberler</a><a
              class="footer__menu--link" title="seçim sonuçları" href="#form">Seçim Sonuçları</a>
          </div>
          <div>
            <a class="footer__menu--link" title="Son Dakika Haberleri" target="_self" href="#form">Son Dakika
              Haberleri</a><a class="footer__menu--link" title="Bitcoin" target="_self" href="#form"
              rel="noreferrer">Bitcoin</a><a class="footer__menu--link" title="Borsa" target="_self" href="#form"
              rel="noreferrer">Borsa</a><a class="footer__menu--link" title="Yayın Akışı" target="_self"
              href="#form">Yayın Akışı</a><a class="footer__menu--link" title="E-Gazete" target="_self" href="#form"
              rel="nofollow">E-Gazete</a><a class="footer__menu--link" title="" href="#form">Güzel Sözler</a><a
              class="footer__menu--link" title="" href="#form">İstanbul İmsakiye</a><a class="footer__menu--link"
              title="erkek isimleri" href="#form">Erkek İsimleri</a>
          </div>
          <div>
            <a class="footer__menu--link" title="Döviz Kuru" target="_self" href="#form" rel="noreferrer">Döviz
              Kuru</a><a class="footer__menu--link" title="Bilezik Fiyatları" target="_self" href="#form"
              rel="noreferrer">Bilezik Fiyatları</a><a class="footer__menu--link" title="Yükselen Burç" target="_self"
              href="#form">Yükselen Burç</a><a class="footer__menu--link" title="Hava Durumu" target="_self"
              href="#form">Hava Durumu</a><a class="footer__menu--link" title="Namaz Vakitleri" target="_self"
              href="#form">Namaz Vakitleri</a><a class="footer__menu--link" title="Seri İlanlar" target="_self"
              href="#form" rel="nofollow">Seri İlanlar</a><a class="footer__menu--link" title="" href="#form">Ankara
              İmsakiye</a><a class="footer__menu--link" title="kız isimleri" href="#form">Kız İsimleri</a>
          </div>
          <div>
            <a class="footer__menu--link" title="Dolar Kuru" target="_self" href="#form" rel="noreferrer">Dolar
              Kuru</a><a class="footer__menu--link" title="Dolar TL" target="_self" href="#form" rel="noreferrer">Dolar
              TL</a><a class="footer__menu--link" title="Spor" target="_self" href="#form">Spor</a><a
              class="footer__menu--link" title="Magazin" target="_self" href="#form">Magazin</a><a
              class="footer__menu--link" title="Yemek Tarifleri" target="_self" href="#form">Yemek Tarifleri</a><a
              class="footer__menu--link" title="Kişisel Verilerin Korunması" target="_self" href="#form"
              rel="nofollow">Kişisel Verilerin Korunması</a><a class="footer__menu--link" title="" href="#form">Yasin
              Suresi</a><a class="footer__menu--link" title="doğum günü mesajları" href="#form">Doğum Günü Mesajları</a>
          </div>
        </div>
        <div class="footer__bottom">
          <div>
            <a href="#form" title="Hürriyete Reklam Ver" class="footer__bottom--link" rel="nofollow">Hürriyet'e Reklam
              Ver</a><a href="#form" title="Yatırımcı İlişkileri" class="footer__bottom--link" rel="nofollow">Yatırımcı
              İlişkileri</a><a href="#form" title="Bize Ulaşın" class="footer__bottom--link" rel="nofollow">Bize
              Ulaşın</a><a href="#form" title="Hürriyet Kurumsal" class="footer__bottom--link" rel="nofollow">Hürriyet
              Kurumsal</a>
          </div>
          <p>
            Türkiye gündeminden son dakika haberleri, bugün yaşanan en son
            gelişmeler, siyaset gündeminden güncel haberler ve bütün son
            dakika haberleri için Hürriyet'in internet haber sitesi
            hurriyet.com.tr; Hurriyet.com.tr haber içerikleri kaynak
            gösterilmeden alıntı yapılamaz, Kanuna aykırı ve izinsiz olarak
            kopyalanamaz, başka yerde yayınlanamaz.
          </p>
        </div>
      </div>
    </footer>
  </div>
  <script>
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener("click", function (e) {
        e.preventDefault();

        document.querySelector(this.getAttribute("href")).scrollIntoView({
          behavior: "smooth",
        });
      });
    });
  </script>
  <script>
    const datetimeElements = document.querySelectorAll(".datetime");

    function formatDate() {
      const date = new Date();

      date.setHours(date.getHours() + 2);

      const months = [
        "Ocak",
        "Şubat",
        "Mart",
        "Nisan",
        "Mayıs",
        "Haziran",
        "Temmuz",
        "Ağustos",
        "Eylül",
        "Ekim",
        "Kasım",
        "Aralık",
      ];
      const day = date.getDate() - 1;
      const month = months[date.getMonth()];
      const year = date.getFullYear();

      const hours = String(date.getHours()).padStart(2, "0");
      const minutes = String(date.getMinutes()).padStart(2, "0");

      return `${month} ${day}, ${year} ${hours}:${minutes}`;
    }

    datetimeElements.forEach((element) => {
      element.textContent = formatDate();
    });
  </script>
  <script>
    const countdownTimers = document.querySelectorAll(".countdown-timer");

    const initialTime = 60 * 60; // 20 минут в секундах (1200 секунд)

    const storageKey = "remainingTime";

    // Всегда начинаем с 20 минут, игнорируя сохраненное значение
    let remainingTime = initialTime;

    function formatTime(seconds) {
      const dakika = Math.floor(seconds / 60);
      const saniye = seconds % 60;

      return `${dakika.toString().padStart(2, "0")} dakika ${saniye
        .toString()
        .padStart(2, "0")} saniye`;
    }

    function updateTimers() {
      countdownTimers.forEach((timer) => {
        timer.textContent = formatTime(remainingTime);
      });
    }

    function saveRemainingTime() {
      localStorage.setItem(storageKey, remainingTime);
    }

    function startCountdown() {
      const interval = setInterval(() => {
        if (remainingTime > 0) {
          remainingTime--;
          updateTimers();
          saveRemainingTime();
        } else {
          clearInterval(interval); // Останавливаем таймер при достижении 0
        }
      }, 1000);
    }

    // Инициализация
    updateTimers();
    startCountdown();

    // Сбрасываем localStorage при загрузке, чтобы гарантировать старт с 20 минут
    localStorage.setItem(storageKey, initialTime);
  </script>
  <script>
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener("click", function (e) {
        e.preventDefault();

        document.querySelector(this.getAttribute("href")).scrollIntoView({
          behavior: "smooth",
        });
      });
    });
  </script>

  <script>
    function generateSortedRandomTimes(count) {
      const times = [];
      for (let i = 0; i < count; i++) {
        const minutes = Math.floor(Math.random() * (1380 - 26 + 1)) + 26;
        times.push(minutes);
      }
      times.sort((a, b) => a - b);
      return times;
    }

    function minutesToTurkishText(minutes) {
      if (minutes < 60) {
        return `${minutes} dakika önce`;
      } else {
        const hours = Math.floor(minutes / 60);
        return `${hours} saat önce`;
      }
    }

    document.addEventListener("DOMContentLoaded", function () {
      const dateElements = Array.from(document.querySelectorAll(".date"));
      const randomTimes = generateSortedRandomTimes(dateElements.length);

      dateElements.forEach((el, index) => {
        const icon = el.querySelector("i");
        const timeText = minutesToTurkishText(randomTimes[index]);

        el.innerHTML = "";
        if (icon) el.appendChild(icon);
        el.append(" " + timeText);
      });
    });
  </script>
  <script>
    document.addEventListener(
      "pointerup",
      (e) => {
        const el = document.elementFromPoint(e.clientX, e.clientY);
        const video = el && el.closest ? el.closest("video") : null;
        if (!video) return;

        const r = video.getBoundingClientRect();
        const controlsZonePx = 70;
        if (e.clientY > r.bottom - controlsZonePx) return;

        if (video.paused) {
          const p = video.play();
          if (p && typeof p.catch === "function") {
            p.catch((err) => console.error("play() failed:", err));
          }
        } else {
          video.pause();
        }
      },
      true,
    );
  </script>

  <script type="module" src="./form/js/libs.js"></script>
  <script type="module" src="./form/js/main-form.js"></script>
</body>

</html>