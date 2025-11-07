/**
 * Type definitions for translation messages
 *
 * This file provides type-safe translation key references
 * throughout the application by augmenting next-intl's type system.
 */

type Messages = {
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
      networkError: string;
      timeout: string;
      connectionError: string;
      retryableMessage: string;
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
};

// Augment next-intl's type system
declare interface IntlMessages extends Messages {}
