import i18n from "i18next";
import { initReactI18next } from "react-i18next";

import en from "./locales/en.json";
import pt from "./locales/pt.json";

const resources = {
  en: {
    translation: en,
  },
  pt: {
    translation: pt,
  },
} as const;

void i18n.use(initReactI18next).init({
  resources,
  lng: navigator.language.toLowerCase().startsWith("pt") ? "pt" : "en",
  fallbackLng: "en",
  interpolation: {
    escapeValue: false,
  },
});

export { i18n };
