const originalEnv = process.env;

export function setEnv(env: Record<string, string>): void {
  process.env = { ...process.env, ...env };
}

export function resetEnv(): void {
  process.env = originalEnv;
}
