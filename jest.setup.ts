import '@testing-library/jest-dom';
import 'whatwg-fetch';
import { TextEncoder, TextDecoder } from 'node:util';
import React from 'react';

// Polyfills
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder as typeof global.TextDecoder;

// Response polyfill for Jest
// Note: This is needed because NextResponse.json() internally calls Response.json()
// and Jest environment doesn't have Response.json() by default
if (!global.Response.json) {
  // @ts-expect-error - Adding missing method to Response
  global.Response.json = function (data: any, init?: ResponseInit) {
    const headers = new Headers(init?.headers || {});
    headers.set('Content-Type', 'application/json');

    const jsonString = JSON.stringify(data);

    return new Response(jsonString, {
      ...init,
      headers,
    });
  };
}

// Mock NextResponse.json to work properly in Jest
// This fixes the issue where response.body is consumed by NextResponse constructor
jest.mock('next/server', () => {
  const actual = jest.requireActual('next/server');

  return {
    ...actual,
    NextResponse: class NextResponse extends actual.NextResponse {
      static json(body: any, init?: ResponseInit) {
        const headers = new Headers(init?.headers || {});
        headers.set('Content-Type', 'application/json');

        return new actual.NextResponse(JSON.stringify(body), {
          ...init,
          status: init?.status || 200,
          headers,
        });
      }
    },
  };
});

// Next.js Image Mock
jest.mock('next/image', () => ({
  __esModule: true,
  default: (props: any) => {
     
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

// Console Error Suppression
const originalError = console.error;
beforeAll(() => {
  jest.spyOn(console, 'error').mockImplementation((...args) => {
    if (args[0]?.includes && args[0].includes('Warning:')) return;
    originalError(...args);
  });
});
