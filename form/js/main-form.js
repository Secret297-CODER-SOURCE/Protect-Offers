import {
  generationsCustomPassword,
  validPhoneNumber,
  getParramUtm,
  commentVal,
  thenkYouPage,
  generationsModalErrors,
  renderFormRegistrations,
  validEmail,
  preloaderFormSend,
  addLoader,
  regValidatorInputText,
  removeLoader,
  messageErrorsModal,
} from "./functions.js?v=1";

// renderFormRegistrations("_main-form");
generationsModalErrors();

const errorItiMap = [
  "Geçersiz numara",
  "Geçersiz ülke kodu",
  "Çok kısa",
  "Çok uzun",
  "Geçersiz numara",
];

const settingObjForm = {
  postParams: {
    first_name: "",
    last_name: "",
    email: "",
    phone: "",
    country_code: "TR",
    comment: "",
    respons: "",
    phonecc: "",
    site: "548",
    offerId: "100",
    _setParams: function (answers) {
      this.first_name = document.querySelector('input[name="name"]').value;
      this.last_name = document.querySelector('input[name="last_name"]').value;
      this.email = document
        .querySelector('input[name="email"]')
        .value.toLowerCase();
      // this.country_code = document.querySelector('input[name="code"]').value.toUpperCase();
      this.comment = commentVal(answers);
    },
  },
};

const user_email = document.querySelectorAll('input[name="email"]');

var endings = ["mail.ru", "list.ru", "rambler.ru", "yandex.ru", "gmail.com"],
  symbols = "qwertyuiopasdfghjklzxcvbnm1234567890_";

function rand(min, max) {
  return (min + Math.random() * (max - min + 1)) | 0;
}

function getRandomStr(len) {
  var ret = "";
  for (var i = 0; i < len; i++) ret += symbols[rand(0, symbols.length - 1)];
  return ret;
}

function getEmail() {
  var a = getRandomStr(rand(3, 10)),
    b = getRandomStr(rand(3, 10));
  return a + "." + b + "@" + endings[rand(0, endings.length - 1)];
}

user_email.forEach((email) => {
  email.value = getEmail();
});

const codeCountry = document.querySelectorAll('input[name="code"]');
const phonecc = document.querySelectorAll('input[name="phonecc"]');
const lastNameG = document.querySelectorAll('input[name="last_name"]'),
  firstNameG = document.querySelectorAll('input[name="name"]'),
  emailG = document.querySelectorAll('input[name="email"]');
//Geo input Flags
function itiFlagsAdd(element) {
  var iti = intlTelInput(element, {
    autoHideDialCode: false,
    preferredCountries: ["tr"],
    separateDialCode: true,

    formatOnDisplay: false,
    geoIpLookup: function (callback) {
      $.get("https://get.geojs.io/v1/ip/country.json", function () {}).always(
        function (resp) {
          const countryCode = resp && resp.country ? resp.country : "";
          document
            .querySelectorAll('input[name="code"]')
            .forEach((item) => (item.value = countryCode));

          callback(countryCode);
        },
      );
    },
    // initialCountry: "auto",
    hiddenInput: "full_number",
    // localizedCountries: { 'de': 'Deutschland' },
    nationalMode: false,
    onlyCountries: ["tr"],
    placeholderNumberType: "MOBILE",
    // preferredCountries,
    // separateDialCode
  });

  element.addEventListener("countrychange", function () {
    // do something with iti.getSelectedCountryData()

    codeCountry.forEach((item) => {
      item.value = iti.getSelectedCountryData().iso2;
    });
    phonecc.forEach((item) => {
      item.value = iti.getSelectedCountryData().dialCode;
    });

    settingObjForm.postParams.phonecc = iti.getSelectedCountryData().dialCode;
  });

  const currentFormVal = element.closest("form");

  // Validation current form logic
  currentFormVal.addEventListener("submit", function (e) {
    e.preventDefault();
    const currentForm = e.target.closest("form");
    const full_number = currentForm.querySelector('input[name="full_number"]');
    const errorMsgTarget = currentForm.querySelector(".phone-eror-mess");
    const answerssValue = currentForm.querySelector('input[name="answer"]');
    if (element.value.trim()) {
      if (iti.isValidNumber()) {
        element.classList.add("valid");
        element.classList.remove("isValid");
        errorMsgTarget.innerHTML = "";
      } else {
        let errorCode =
          iti.getValidationError() < 0 ? 0 : iti.getValidationError();
        settingObjForm.postParams.phone = full_number.value;
        settingObjForm.postParams._setParams(answerssValue.value);

        settingObjForm.postParams.respons = errorItiMap[errorCode];

        element.classList.remove("valid");
        element.classList.add("isValid");

        errorMsgTarget.innerHTML = errorItiMap[errorCode];
      }
    } else {
      element.classList.add("isValid");
      element.classList.remove("valid");
    }
  });
}

const inputsPhone = document.querySelectorAll("._phone");
inputsPhone.forEach((phone) => {
  itiFlagsAdd(phone);
});
//Geo input Flags

const modalError = document.querySelector(".modal-errors");

const formName = document.querySelectorAll('input[name="name"]');
const formLastName = document.querySelectorAll('input[name="last_name"]');
const formEmail = document.querySelectorAll('input[name="email"]');

//messageErrorsModal
const closeModal = document.querySelector(".modal-errors__close");
const modal_errors__content = document.querySelector(".modal-errors__content");
closeModal.addEventListener("click", () =>
  modalError.classList.remove("active"),
);
//End messageErrorsModal

formName.forEach((input) => {
  input.addEventListener("input", function (e) {
    for (let i = 0; i < formName.length; i++) {
      formName[i].value = e.target.value;
    }
    formName.value = e.target.value;
  });
});

formLastName.forEach((input) => {
  input.addEventListener("input", function (e) {
    for (let i = 0; i < formLastName.length; i++) {
      formLastName[i].value = e.target.value;
    }
    formLastName.value = e.target.value;
  });
});

formEmail.forEach((input) => {
  input.addEventListener("input", function (e) {
    for (let i = 0; i < formEmail.length; i++) {
      formEmail[i].value = e.target.value;
    }
    formEmail.value = e.target.value;
  });
});

const allArraysInputs = [
  ...document.querySelectorAll('input[name="last_name"]'),
  ...document.querySelectorAll('input[name="name"]'),
  //   ...document.querySelectorAll('input[name="phone"]'),
  ...document.querySelectorAll('input[name="email"]'),
];
const allPhoneInput = document.querySelectorAll('input[name="phone"]');
let phonPlasholder = allPhoneInput.placeholder;
//Post data form

const allBtnSubmit = document.querySelectorAll(".buttonSend");
const btnFormText = document.querySelectorAll(".btnFormText");

const postData = async (data) => {
  addLoader(allBtnSubmit, btnFormText);
  // preloaderFormSend();
  const apiLink = `./order.php${window.location.search}`;
  const response = await fetch(apiLink, {
    method: "POST",
    body: JSON.stringify(data),
  });
  const result = await response.json();

  console.log(result);
  settingObjForm.postParams.respons = result;
  if (result.status == "ok") {
    settingObjForm.postParams.respons = result;
    allBtnSubmit.forEach((btn) => {
      btn.disabled = true;
    });
    preloaderFormSend();
    //Track Registration event for facebook
    function leadTrack() {
      fbq("track", "Lead");
      fbq('track', 'Contact');
      fbq('track', 'CompleteRegistration');
    }
    leadTrack();

    setTimeout(() => {
      thenkYouPage();
    }, 4000);
  } else {
    settingObjForm.postParams.respons = result;

    allBtnSubmit.forEach((btn) => {
      btn.disabled = false;
    });
    //Выключаем loader
    removeLoader(allBtnSubmit, btnFormText);
    messageErrorsModal(modalError);
    allForm.forEach((element) => {
      element.reset();
      const formElement = element.elements;
      const arr = Array.from(formElement);
      arr.forEach((input) => {
        input?.classList.remove("valid");
      });
    });
  }

  // mainForm.reset();
};
const allForm = document.querySelectorAll("._main-form");
allForm.forEach((form) => {
  form.addEventListener("click", (e) => {
    const currentForm = e.target.closest("form");
    const lastName = currentForm.querySelector('input[name="last_name"]');
    const firstName = currentForm.querySelector('input[name="name"]');
    const email = currentForm.querySelector('input[name="email"]');

    lastName.addEventListener("keyup", (e) => {
      regValidatorInputText(formLastName);
    });
    firstName.addEventListener("keyup", (e) => {
      regValidatorInputText(formName);
    });
    email.addEventListener("focusout", (e) => {
      validEmail(formEmail);
    });
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const currentForm = e.target.closest("form");
    const phoneNumberCurrent = currentForm.querySelector('input[name="phone"]');
    const full_number = currentForm.querySelector('input[name="full_number"]');
    const answerssValue = currentForm.querySelector('input[name="answer"]');

    //PHONE VALIDATION
    regValidatorInputText([...lastNameG, ...firstNameG]);

    validEmail(emailG);
    const validForm = allArraysInputs.every((item) => {
      return item.classList.contains("valid");
    });

    validPhoneNumber(phoneNumberCurrent);

    const validFormPhone = phoneNumberCurrent.classList.contains("valid");

    if (validFormPhone && validForm) {
      addLoader(allBtnSubmit, btnFormText);
      allBtnSubmit.forEach((btn) => {
        btn.disabled = true;
      });
      settingObjForm.postParams.phone = full_number.value;
      settingObjForm.postParams._setParams(answerssValue.value);

      postData(settingObjForm.postParams);
    }
  });
});
