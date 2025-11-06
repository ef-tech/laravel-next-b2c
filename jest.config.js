module.exports = {
  projects: [
    '<rootDir>/frontend/admin-app',
    '<rootDir>/frontend/user-app',
    '<rootDir>/frontend/lib',
    '<rootDir>/scripts',
  ],
  // Root-level reporters configuration for all projects
  reporters: [
    'default',
    [
      'jest-junit',
      {
        outputDirectory: '<rootDir>/test-results/junit',
        // outputName can be set via JEST_JUNIT_OUTPUT_NAME environment variable
        suiteNameTemplate: '{filepath}',
        classNameTemplate: '{classname}',
        titleTemplate: '{title}',
        ancestorSeparator: ' › ',
        usePathForSuiteName: 'true',
      },
    ],
  ],
};
