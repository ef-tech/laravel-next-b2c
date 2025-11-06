/**
 * Type definitions for translation messages
 *
 * This file extends next-intl's IntlMessages interface to provide
 * type-safe translation key references throughout the application.
 */

interface Messages {
  errors: {
    network: {
      timeout: string;
      connection: string;
      unknown: string;
    };
    boundary: {
      title: string;
      retry: string;
      home: string;
      status: string;
      requestId: string;
    };
    validation: {
      title: string;
    };
    global: {
      title: string;
      retry: string;
      errorId: string;
      contactMessage: string;
    };
  };
}

declare module 'next-intl' {
  interface IntlMessages extends Messages {}
}
