/**
 * Jest Configuration for Shared Library (frontend/lib)
 *
 * This configuration is for testing shared library code that is used across
 * both admin-app and user-app. The shared library files (api-client, api-error,
 * network-error) are excluded from coverage in individual apps and tested here.
 */

module.exports = {
  displayName: 'shared-lib',
  testEnvironment: 'jsdom',
  rootDir: __dirname,
  setupFilesAfterEnv: ['<rootDir>/../../jest.setup.ts'],
  testMatch: [
    '<rootDir>/__tests__/**/*.(test|spec).(ts|tsx)',
    '<rootDir>/__tests__/**/*.(test|spec).(js|jsx)',
  ],
  transform: {
    '^.+\\.(ts|tsx)$': [
      'ts-jest',
      {
        isolatedModules: true,
        tsconfig: {
          jsx: 'react',
          esModuleInterop: true,
          allowSyntheticDefaultImports: true,
        },
      },
    ],
  },
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/../user-app/src/$1',
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
  },
  transformIgnorePatterns: [
    'node_modules/(?!(msw|@mswjs|until-async|next-intl|use-intl|.*\\.mjs$))',
  ],
  collectCoverageFrom: [
    '*.{ts,tsx,js,jsx}',
    '!*.d.ts',
    '!index.{ts,tsx}',
    '!jest.config.cjs',
  ],
  coverageThreshold: {
    global: {
      branches: 100,
      functions: 100,
      lines: 100,
      statements: 100,
    },
  },
};
