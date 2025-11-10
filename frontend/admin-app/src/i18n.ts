/**
 * i18n Request Configuration for Admin App
 *
 * This configuration handles locale validation, message loading,
 * and fallback behavior for the Next.js App Router.
 */

import { getRequestConfig } from "next-intl/server";
import { i18nConfig, type Locale } from "@shared/i18n-config";

export default getRequestConfig(async ({ locale }) => {
  // Validate locale against supported locales
  const validLocale: string = i18nConfig.locales.includes(locale as Locale)
    ? (locale as string)
    : i18nConfig.defaultLocale;

  // Load messages for the validated locale
  return {
    locale: validLocale,
    messages: (await import(`../messages/${validLocale}.json`)).default,
  };
});
