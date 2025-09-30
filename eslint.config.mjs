// Root ESLint config for lint-staged
// This config enables ESLint to run from the repository root
// while respecting individual workspace configurations

export default [
  {
    ignores: [
      "**/node_modules/**",
      "**/.next/**",
      "**/out/**",
      "**/build/**",
      "**/dist/**",
      "**/*.min.*",
      "**/next-env.d.ts",
      "backend/**",
      ".kiro/**",
      ".claude/**",
      ".git/**",
      ".husky/**",
    ],
  },
];