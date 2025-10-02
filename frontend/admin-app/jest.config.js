const nextJest = require("next/jest");
const base = require("../../jest.base");

const createJestConfig = nextJest({
  dir: __dirname,
});

const customJestConfig = {
  ...base,
  displayName: "admin-app",
  rootDir: __dirname,
  setupFilesAfterEnv: ["<rootDir>/../../jest.setup.ts"],
  moduleNameMapper: {
    "^@/(.*)$": "<rootDir>/src/$1",
  },
};

module.exports = createJestConfig(customJestConfig);
