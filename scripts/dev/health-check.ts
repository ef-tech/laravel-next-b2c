#!/usr/bin/env tsx
/**
 * Health check and dependency validation module
 *
 * This module handles:
 * - Dependency tool checks (Docker, Node.js, PHP, make version validation)
 * - Port conflict detection (lsof/ss command, PID/process name retrieval)
 * - Service health checks (PostgreSQL, Redis, Laravel API, Next.js apps)
 * - OS detection (macOS, Linux, Windows WSL)
 */

import { exec, execSync } from 'node:child_process';
import { promisify } from 'node:util';
import type {
  OSType,
  DependencyTool,
  PortCheckResult,
  DependencyCheckError,
  HealthCheckError,
} from './types.js';

const execAsync = promisify(exec);

/**
 * Detect operating system type
 *
 * @returns OS type (macos, linux, windows-wsl, unknown)
 */
export function detectOS(): OSType {
  const platform = process.platform;

  if (platform === 'darwin') {
    return 'macos';
  }

  if (platform === 'linux') {
    // Check if running under WSL
    try {
      const output = execSync('uname -r', { encoding: 'utf-8' });
      if (output.toLowerCase().includes('microsoft') || output.toLowerCase().includes('wsl')) {
        return 'windows-wsl';
      }
    } catch {
      // If uname fails, assume regular Linux
    }
    return 'linux';
  }

  return 'unknown';
}

/**
 * Parse version string to compare
 *
 * @param version - Version string (e.g., "1.2.3")
 * @returns Array of version numbers
 */
function parseVersion(version: string): number[] {
  return version.split('.').map((part) => {
    const num = parseInt(part.replace(/[^\d]/g, ''), 10);
    return isNaN(num) ? 0 : num;
  });
}

/**
 * Compare two version strings
 *
 * @param actual - Actual version
 * @param required - Required version
 * @returns true if actual >= required
 */
function compareVersions(actual: string, required: string): boolean {
  const actualParts = parseVersion(actual);
  const requiredParts = parseVersion(required);

  for (let i = 0; i < Math.max(actualParts.length, requiredParts.length); i++) {
    const actualPart = actualParts[i] || 0;
    const requiredPart = requiredParts[i] || 0;

    if (actualPart > requiredPart) return true;
    if (actualPart < requiredPart) return false;
  }

  return true; // Equal versions
}

/**
 * Check if a dependency tool is installed and meets version requirements
 *
 * @param tool - Dependency tool information
 * @returns true if tool is available and meets requirements
 */
export async function checkDependencyTool(
  tool: DependencyTool
): Promise<{ available: boolean; version?: string; error?: DependencyCheckError }> {
  try {
    const { stdout } = await execAsync(`${tool.versionCommand} 2>&1`);
    const versionMatch = stdout.match(/(\d+\.\d+\.\d+)/);

    if (!versionMatch) {
      return {
        available: false,
        error: new DependencyCheckError(
          `Could not determine ${tool.name} version`,
          tool.name
        ),
      };
    }

    const actualVersion = versionMatch[1];

    if (!compareVersions(actualVersion, tool.requiredVersion)) {
      return {
        available: false,
        version: actualVersion,
        error: new DependencyCheckError(
          `${tool.name} version ${actualVersion} is below required version ${tool.requiredVersion}`,
          tool.name,
          tool.requiredVersion,
          actualVersion
        ),
      };
    }

    return { available: true, version: actualVersion };
  } catch (error) {
    return {
      available: false,
      error: new DependencyCheckError(
        `${tool.name} is not installed or not in PATH`,
        tool.name
      ),
    };
  }
}

/**
 * Check all required dependency tools
 *
 * @returns Object with tool check results
 */
export async function checkDependencies(): Promise<{
  success: boolean;
  results: Record<string, { available: boolean; version?: string }>;
  errors: DependencyCheckError[];
}> {
  const tools: DependencyTool[] = [
    {
      name: 'Docker',
      command: 'docker',
      versionCommand: 'docker --version',
      requiredVersion: '20.10.0',
      installUrl: 'https://docs.docker.com/get-docker/',
    },
    {
      name: 'Node.js',
      command: 'node',
      versionCommand: 'node --version',
      requiredVersion: '18.0.0',
      installUrl: 'https://nodejs.org/',
    },
    {
      name: 'PHP',
      command: 'php',
      versionCommand: 'php --version',
      requiredVersion: '8.4.0',
      installUrl: 'https://www.php.net/downloads',
    },
    {
      name: 'make',
      command: 'make',
      versionCommand: 'make --version',
      requiredVersion: '3.81.0',
      installUrl: 'https://www.gnu.org/software/make/',
    },
    {
      name: 'jq',
      command: 'jq',
      versionCommand: 'jq --version',
      requiredVersion: '1.5.0',
      installUrl: 'https://stedolan.github.io/jq/download/',
    },
  ];

  const results: Record<string, { available: boolean; version?: string }> = {};
  const errors: DependencyCheckError[] = [];

  for (const tool of tools) {
    const result = await checkDependencyTool(tool);
    results[tool.name] = {
      available: result.available,
      version: result.version,
    };

    if (result.error) {
      errors.push(result.error);
    }
  }

  return {
    success: errors.length === 0,
    results,
    errors,
  };
}

/**
 * Check if a port is in use
 *
 * @param port - Port number to check
 * @returns Port check result with PID and process name if in use
 */
export async function checkPort(port: number): Promise<PortCheckResult> {
  const os = detectOS();

  try {
    if (os === 'macos' || os === 'linux' || os === 'windows-wsl') {
      // Try lsof first (works on macOS and most Linux)
      try {
        const { stdout } = await execAsync(`lsof -i :${port} -t -sTCP:LISTEN 2>/dev/null`);
        const pid = parseInt(stdout.trim(), 10);

        if (!isNaN(pid) && pid > 0) {
          // Get process name
          try {
            const { stdout: psOutput } = await execAsync(`ps -p ${pid} -o comm= 2>/dev/null`);
            const processName = psOutput.trim();

            return {
              port,
              inUse: true,
              pid,
              processName: processName || undefined,
            };
          } catch {
            return {
              port,
              inUse: true,
              pid,
            };
          }
        }
      } catch {
        // lsof failed or port not in use, try ss on Linux
        if (os === 'linux' || os === 'windows-wsl') {
          try {
            const { stdout } = await execAsync(
              `ss -tlnp | grep :${port} | awk '{print $6}' | grep -oP 'pid=\\K[0-9]+' 2>/dev/null`
            );
            const pid = parseInt(stdout.trim(), 10);

            if (!isNaN(pid) && pid > 0) {
              try {
                const { stdout: psOutput } = await execAsync(`ps -p ${pid} -o comm= 2>/dev/null`);
                const processName = psOutput.trim();

                return {
                  port,
                  inUse: true,
                  pid,
                  processName: processName || undefined,
                };
              } catch {
                return {
                  port,
                  inUse: true,
                  pid,
                };
              }
            }
          } catch {
            // Port not in use
          }
        }
      }
    }

    return { port, inUse: false };
  } catch (error) {
    // Error checking port, assume not in use
    return { port, inUse: false };
  }
}

/**
 * Check multiple ports for conflicts
 *
 * @param ports - Array of port numbers to check
 * @returns Array of port check results
 */
export async function checkPorts(ports: number[]): Promise<PortCheckResult[]> {
  const results = await Promise.all(ports.map((port) => checkPort(port)));
  return results;
}

/**
 * Wait for a service to be ready by checking URL
 *
 * @param url - Service URL to check
 * @param timeout - Timeout in milliseconds (default: 30000)
 * @param retryInterval - Retry interval in milliseconds (default: 1000)
 * @returns true if service is ready
 */
export async function waitForService(
  url: string,
  timeout: number = 30000,
  retryInterval: number = 1000
): Promise<{ success: boolean; error?: HealthCheckError }> {
  const startTime = Date.now();

  while (Date.now() - startTime < timeout) {
    try {
      const response = await fetch(url, {
        method: 'GET',
        signal: AbortSignal.timeout(5000),
      });

      if (response.ok) {
        return { success: true };
      }
    } catch {
      // Service not ready yet, continue waiting
    }

    await new Promise((resolve) => setTimeout(resolve, retryInterval));
  }

  return {
    success: false,
    error: new HealthCheckError(
      `Service at ${url} did not become ready within ${timeout}ms`,
      url,
      timeout
    ),
  };
}

/**
 * Wait for multiple services to be ready
 *
 * @param services - Object with service names and URLs
 * @param timeout - Timeout in milliseconds per service
 * @returns Object with service readiness results
 */
export async function waitForServices(
  services: Record<string, string>,
  timeout: number = 30000
): Promise<{
  success: boolean;
  results: Record<string, boolean>;
  errors: HealthCheckError[];
}> {
  const results: Record<string, boolean> = {};
  const errors: HealthCheckError[] = [];

  for (const [name, url] of Object.entries(services)) {
    const result = await waitForService(url, timeout);
    results[name] = result.success;

    if (result.error) {
      errors.push(result.error);
    }
  }

  return {
    success: errors.length === 0,
    results,
    errors,
  };
}

/**
 * Main function for testing
 */
async function main() {
  console.log('=== OS Detection ===');
  const os = detectOS();
  console.log(`Detected OS: ${os}`);

  console.log('\n=== Dependency Check ===');
  const depResult = await checkDependencies();
  console.log('Dependencies check:', depResult.success ? '✓ Passed' : '✗ Failed');

  for (const [name, result] of Object.entries(depResult.results)) {
    if (result.available) {
      console.log(`  ✓ ${name}: ${result.version}`);
    } else {
      console.log(`  ✗ ${name}: Not available`);
    }
  }

  if (depResult.errors.length > 0) {
    console.log('\nErrors:');
    for (const error of depResult.errors) {
      console.log(`  - ${error.message}`);
    }
  }

  console.log('\n=== Port Check ===');
  const portsToCheck = [13000, 13001, 13002];
  const portResults = await checkPorts(portsToCheck);

  for (const result of portResults) {
    if (result.inUse) {
      console.log(
        `  ✗ Port ${result.port}: In use (PID: ${result.pid}, Process: ${result.processName || 'unknown'})`
      );
    } else {
      console.log(`  ✓ Port ${result.port}: Available`);
    }
  }
}

/**
 * CLI: check-dependencies subcommand
 */
async function cliCheckDependencies() {
  const result = await checkDependencies();

  if (!result.success) {
    console.error('Dependency check failed:');
    for (const error of result.errors) {
      console.error(`  ✗ ${error.message}`);
      if (error.installUrl) {
        console.error(`    Install: ${error.installUrl}`);
      }
    }
    process.exit(1);
  }

  console.log('✓ All dependencies are available');
  process.exit(0);
}

/**
 * CLI: check-ports subcommand
 * Reads port array from stdin as JSON
 */
async function cliCheckPorts() {
  // Read stdin
  const chunks: Buffer[] = [];
  for await (const chunk of process.stdin) {
    chunks.push(chunk);
  }
  const input = Buffer.concat(chunks).toString('utf-8');

  let ports: number[];
  try {
    const data = JSON.parse(input);
    if (Array.isArray(data.ports)) {
      ports = data.ports;
    } else if (Array.isArray(data)) {
      ports = data;
    } else {
      throw new Error('Input must be an array or object with "ports" array');
    }
  } catch (error) {
    console.error('Failed to parse input JSON:', error instanceof Error ? error.message : String(error));
    process.exit(1);
  }

  const results = await checkPorts(ports);

  const portsInUse = results.filter((r) => r.inUse);

  if (portsInUse.length > 0) {
    console.warn('The following ports are in use:');
    for (const result of portsInUse) {
      console.warn(
        `  ✗ Port ${result.port}: In use (PID: ${result.pid}, Process: ${result.processName || 'unknown'})`
      );
    }
    process.exit(1);
  }

  console.log('✓ All ports are available');
  process.exit(0);
}

// Run CLI if this file is executed directly
if (import.meta.url === `file://${process.argv[1]}`) {
  const subcommand = process.argv[2];

  if (subcommand === 'check-dependencies') {
    cliCheckDependencies().catch((error) => {
      console.error('Unexpected error:', error);
      process.exit(1);
    });
  } else if (subcommand === 'check-ports') {
    cliCheckPorts().catch((error) => {
      console.error('Unexpected error:', error);
      process.exit(1);
    });
  } else if (subcommand) {
    console.error(`Unknown subcommand: ${subcommand}`);
    console.error('Available subcommands: check-dependencies, check-ports');
    process.exit(1);
  } else {
    // No subcommand - run test mode
    main().catch((error) => {
      console.error('Unexpected error:', error);
      process.exit(1);
    });
  }
}
