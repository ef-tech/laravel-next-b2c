/**
 * Shared i18n configuration for User App and Admin App
 *
 * This configuration defines the supported locales and default locale
 * for the entire frontend application.
 */

export const i18nConfig = {
  /**
   * List of supported locales
   */
  locales: ['ja', 'en'] as const,

  /**
   * Default locale (fallback)
   */
  defaultLocale: 'ja' as const,
} as const;

/**
 * Type definition for supported locales
 */
export type Locale = (typeof i18nConfig.locales)[number];
