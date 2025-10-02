module.exports = {
  projects: ['<rootDir>/frontend/admin-app', '<rootDir>/frontend/user-app'],
  collectCoverageFrom: [
    'frontend/**/src/**/*.{ts,tsx,js,jsx}',
    '!frontend/**/src/**/*.d.ts',
  ],
};
