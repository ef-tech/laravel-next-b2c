import '@testing-library/jest-dom';
import 'whatwg-fetch';
import { TextEncoder, TextDecoder } from 'node:util';
import React from 'react';

// Polyfills
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder as typeof global.TextDecoder;

// BroadcastChannel Polyfill for MSW
if (typeof global.BroadcastChannel === 'undefined') {
  global.BroadcastChannel = class BroadcastChannel {
    constructor(public name: string) {}
    postMessage() {}
    close() {}
    addEventListener() {}
    removeEventListener() {}
    dispatchEvent() {
      return true;
    }
  } as any;
}

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
