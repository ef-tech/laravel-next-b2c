#!/usr/bin/env tsx
/**
 * Development server configuration management module
 *
 * This module handles:
 * - Configuration file loading (services.json, profiles.json, ports.json)
 * - Service selection logic (profile resolution, individual service selection)
 * - Dependency resolution (automatic dependency addition, circular dependency detection)
 */

import { readFile } from 'node:fs/promises';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import type {
  Config,
  ServicesConfig,
  ProfilesConfig,
  PortsConfig,
  ServiceDefinition,
  ProfileDefinition,
  Result,
  ConfigLoadError,
  ServiceSelectionError,
} from './types.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

/**
 * Load configuration from JSON files
 *
 * @param configDir - Configuration directory path (default: ./config)
 * @returns Configuration object or error
 */
export async function loadConfig(
  configDir: string = resolve(__dirname, 'config')
): Promise<Result<Config, ConfigLoadError>> {
  try {
    const servicesPath = resolve(configDir, 'services.json');
    const profilesPath = resolve(configDir, 'profiles.json');
    const portsPath = resolve(configDir, 'ports.json');

    const [servicesData, profilesData, portsData] = await Promise.all([
      readFile(servicesPath, 'utf-8'),
      readFile(profilesPath, 'utf-8'),
      readFile(portsPath, 'utf-8'),
    ]);

    let services: ServicesConfig;
    let profiles: ProfilesConfig;
    let ports: PortsConfig;

    try {
      services = JSON.parse(servicesData) as ServicesConfig;
    } catch (error) {
      return {
        success: false,
        error: new ConfigLoadError(
          `Failed to parse services.json: ${error instanceof Error ? error.message : String(error)}`,
          servicesPath,
          error
        ),
      };
    }

    try {
      profiles = JSON.parse(profilesData) as ProfilesConfig;
    } catch (error) {
      return {
        success: false,
        error: new ConfigLoadError(
          `Failed to parse profiles.json: ${error instanceof Error ? error.message : String(error)}`,
          profilesPath,
          error
        ),
      };
    }

    try {
      ports = JSON.parse(portsData) as PortsConfig;
    } catch (error) {
      return {
        success: false,
        error: new ConfigLoadError(
          `Failed to parse ports.json: ${error instanceof Error ? error.message : String(error)}`,
          portsPath,
          error
        ),
      };
    }

    return {
      success: true,
      value: { services, profiles, ports },
    };
  } catch (error) {
    return {
      success: false,
      error: new ConfigLoadError(
        `Failed to load configuration: ${error instanceof Error ? error.message : String(error)}`,
        configDir,
        error
      ),
    };
  }
}

/**
 * Select services based on profile name or individual service names
 *
 * @param config - Configuration object
 * @param profileName - Profile name (e.g., 'full', 'api-only') or null
 * @param serviceNames - Individual service names or null
 * @returns Selected service definitions or error
 */
export function selectServices(
  config: Config,
  profileName: string | null,
  serviceNames: string[] | null
): Result<Record<string, ServiceDefinition>, ServiceSelectionError> {
  // If profileName is provided, resolve profile
  if (profileName) {
    const profile = config.profiles.profiles[profileName];
    if (!profile) {
      const availableProfiles = Object.keys(config.profiles.profiles).join(', ');
      return {
        success: false,
        error: new ServiceSelectionError(
          `Profile '${profileName}' not found. Available profiles: ${availableProfiles}`
        ),
      };
    }

    const selected: Record<string, ServiceDefinition> = {};
    for (const serviceName of profile.services) {
      const service = config.services.services[serviceName];
      if (!service) {
        return {
          success: false,
          error: new ServiceSelectionError(
            `Service '${serviceName}' referenced in profile '${profileName}' not found`,
            [serviceName]
          ),
        };
      }
      selected[serviceName] = service;
    }

    return { success: true, value: selected };
  }

  // If serviceNames are provided, select individual services
  if (serviceNames && serviceNames.length > 0) {
    const selected: Record<string, ServiceDefinition> = {};
    const invalidServices: string[] = [];

    for (const serviceName of serviceNames) {
      const service = config.services.services[serviceName];
      if (!service) {
        invalidServices.push(serviceName);
      } else {
        selected[serviceName] = service;
      }
    }

    if (invalidServices.length > 0) {
      const availableServices = Object.keys(config.services.services).join(', ');
      return {
        success: false,
        error: new ServiceSelectionError(
          `Invalid service names: ${invalidServices.join(', ')}. Available services: ${availableServices}`,
          invalidServices
        ),
      };
    }

    return { success: true, value: selected };
  }

  // No profile or service names provided - use default profile
  const defaultProfile = Object.entries(config.profiles.profiles).find(
    ([_, profile]) => profile.default
  );

  if (!defaultProfile) {
    return {
      success: false,
      error: new ServiceSelectionError(
        'No profile or service names provided, and no default profile found'
      ),
    };
  }

  return selectServices(config, defaultProfile[0], null);
}

/**
 * Resolve service dependencies recursively
 *
 * @param services - Selected services
 * @param allServices - All available services
 * @param resolved - Already resolved services (for circular dependency detection)
 * @returns Services with dependencies or error
 */
export function resolveDependencies(
  services: Record<string, ServiceDefinition>,
  allServices: Record<string, ServiceDefinition>,
  resolved: Set<string> = new Set()
): Result<Record<string, ServiceDefinition>, ServiceSelectionError> {
  const result: Record<string, ServiceDefinition> = { ...services };

  // Detect circular dependencies
  const visiting = new Set<string>();

  function visit(serviceName: string, path: string[] = []): boolean {
    if (visiting.has(serviceName)) {
      // Circular dependency detected
      return false;
    }

    if (resolved.has(serviceName)) {
      return true;
    }

    visiting.add(serviceName);
    path.push(serviceName);

    const service = allServices[serviceName];
    if (!service) {
      visiting.delete(serviceName);
      return false;
    }

    // Add service to result
    result[serviceName] = service;

    // Resolve dependencies
    if (service.dependencies) {
      for (const dep of service.dependencies) {
        if (!visit(dep, [...path])) {
          return false;
        }
      }
    }

    visiting.delete(serviceName);
    resolved.add(serviceName);
    return true;
  }

  for (const serviceName of Object.keys(services)) {
    if (!visit(serviceName)) {
      const cycle = Array.from(visiting).join(' -> ');
      return {
        success: false,
        error: new ServiceSelectionError(
          `Circular dependency detected: ${cycle}`,
          Array.from(visiting)
        ),
      };
    }
  }

  return { success: true, value: result };
}

/**
 * Get Docker Compose profile flags for selected services
 *
 * @param services - Selected services
 * @returns Array of profile names
 */
export function getDockerProfiles(
  services: Record<string, ServiceDefinition>
): string[] {
  const profiles = new Set<string>();

  for (const service of Object.values(services)) {
    if (service.docker?.profile) {
      profiles.add(service.docker.profile);
    }
  }

  return Array.from(profiles);
}

/**
 * Main function for testing
 */
async function main() {
  // Load configuration
  const configResult = await loadConfig();
  if (!configResult.success) {
    console.error('Error loading configuration:', configResult.error.message);
    process.exit(1);
  }

  const config = configResult.value;
  console.log('✓ Configuration loaded successfully');
  console.log(`  Services: ${Object.keys(config.services.services).length}`);
  console.log(`  Profiles: ${Object.keys(config.profiles.profiles).length}`);
  console.log(`  Ports: ${Object.keys(config.ports.ports).length}`);

  // Test service selection with default profile
  console.log('\n--- Testing default profile selection ---');
  const servicesResult = selectServices(config, null, null);
  if (!servicesResult.success) {
    console.error('Error selecting services:', servicesResult.error.message);
    process.exit(1);
  }

  console.log('✓ Services selected:', Object.keys(servicesResult.value).join(', '));

  // Test dependency resolution
  console.log('\n--- Testing dependency resolution ---');
  const resolvedResult = resolveDependencies(
    servicesResult.value,
    config.services.services
  );
  if (!resolvedResult.success) {
    console.error('Error resolving dependencies:', resolvedResult.error.message);
    process.exit(1);
  }

  console.log('✓ Dependencies resolved');
  console.log(`  Total services: ${Object.keys(resolvedResult.value).length}`);
  console.log(`  Services: ${Object.keys(resolvedResult.value).join(', ')}`);

  // Test Docker profiles
  console.log('\n--- Testing Docker profile generation ---');
  const profiles = getDockerProfiles(resolvedResult.value);
  console.log(`✓ Docker profiles: ${profiles.join(', ')}`);
}

// Run main if this file is executed directly
if (import.meta.url === `file://${process.argv[1]}`) {
  main().catch((error) => {
    console.error('Unexpected error:', error);
    process.exit(1);
  });
}
