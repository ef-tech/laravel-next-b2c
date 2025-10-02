import '@testing-library/jest-dom';
import 'whatwg-fetch';
import { TextEncoder, TextDecoder } from 'node:util';
import React from 'react';

// Polyfills
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder as typeof global.TextDecoder;

// Next.js Image Mock
jest.mock('next/image', () => ({
  __esModule: true,
  default: (props: any) => {
    // eslint-disable-next-line jsx-a11y/alt-text, @next/next/no-img-element
    return React.createElement('img', props);
  },
}));

// Next.js Font Mock
jest.mock('next/font/local', () => ({
  __esModule: true,
  default: () => ({
    className: '',
  }),
}));

// Next.js Navigation Mock
jest.mock('next/navigation', () => require('next-router-mock'));

// MSW Setup
let server: any;
let http: any;
let HttpResponse: any;

try {
  const msw = require('msw/node');
  const mswCore = require('msw');
  server = msw.setupServer();
  http = mswCore.http;
  HttpResponse = mswCore.HttpResponse;

  beforeAll(() => server.listen({ onUnhandledRequest: 'warn' }));
  afterEach(() => server.resetHandlers());
  afterAll(() => server.close());
} catch (error) {
  // MSW not available, skip setup
}

// Console Error Suppression
const originalError = console.error;
beforeAll(() => {
  jest.spyOn(console, 'error').mockImplementation((...args) => {
    if (args[0]?.includes && args[0].includes('Warning:')) return;
    originalError(...args);
  });
});

// Export MSW utilities for tests
export { http, HttpResponse };
