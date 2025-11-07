module.exports = {
  displayName: 'scripts',
  testEnvironment: 'node',
  testMatch: ['<rootDir>/__tests__/**/*.test.{ts,js}'],
  transform: {
    '^.+\\.ts$': [
      'ts-jest',
      {
        tsconfig: {
          module: 'commonjs',
          esModuleInterop: true,
          allowSyntheticDefaultImports: true,
        },
      },
    ],
  },
  moduleFileExtensions: ['ts', 'js'],
  collectCoverageFrom: ['<rootDir>/*.{ts,js}', '!<rootDir>/__tests__/**'],
};
