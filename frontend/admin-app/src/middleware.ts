/**
 * Middleware for Admin App
 *
 * This middleware handles locale detection and routing using next-intl.
 * It detects the user's locale from:
 * 1. URL prefix (/ja/*, /en/*)
 * 2. NEXT_LOCALE cookie
 * 3. Accept-Language header
 * 4. Default locale (ja)
 */

import createMiddleware from "next-intl/middleware";
import { i18nConfig } from "@/../../lib/i18n-config";

export default createMiddleware({
  locales: i18nConfig.locales,
  defaultLocale: i18nConfig.defaultLocale,
  localeDetection: true, // Enable Accept-Language detection
  localePrefix: "always", // Always add locale prefix to URLs (enables cookie setting)
});

export const config = {
  // Match all routes except:
  // - API routes (/api/*)
  // - Next.js internal routes (_next/*)
  // - Vercel routes (_vercel/*)
  // - Static files (files with extensions)
  matcher: ["/((?!api|_next|_vercel|.*\\..*).*)"],
};
