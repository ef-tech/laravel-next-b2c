/**
 * Type definitions for development server scripts
 */

/**
 * Service execution mode
 */
export type ServiceMode = 'docker' | 'native' | 'docker|native';

/**
 * Service type categorization
 */
export type ServiceType = 'api' | 'frontend' | 'infra';

/**
 * Docker configuration for a service
 */
export interface DockerConfig {
  /** Docker Compose service name */
  service: string;
  /** Docker Compose profile name */
  profile: string;
}

/**
 * Native execution configuration for a service
 */
export interface NativeConfig {
  /** Command to execute */
  command: string;
  /** Environment variables */
  env?: Record<string, string>;
}

/**
 * Service definition
 */
export interface ServiceDefinition {
  /** Display name */
  name: string;
  /** Service type */
  type: ServiceType;
  /** Execution mode */
  mode: ServiceMode;
  /** Main port */
  port: number;
  /** Health check URL or command */
  healthCheck: string | null;
  /** Docker configuration */
  docker?: DockerConfig;
  /** Native execution configuration */
  native?: NativeConfig;
  /** Service dependencies */
  dependencies?: string[];
  /** Additional ports (e.g., dashboard, console) */
  dashboardPort?: number;
  consolePort?: number;
}

/**
 * Services configuration
 */
export interface ServicesConfig {
  $schema?: string;
  title?: string;
  description?: string;
  services: Record<string, ServiceDefinition>;
}

/**
 * Profile definition
 */
export interface ProfileDefinition {
  /** Display name */
  name: string;
  /** Description */
  description: string;
  /** List of services to start */
  services: string[];
  /** Docker Compose profiles to use */
  dockerProfiles: string[];
  /** Whether this is the default profile */
  default?: boolean;
  /** List of services to explicitly exclude */
  excludeServices?: string[];
  /** List of assumptions for this profile */
  assumptions?: string[];
}

/**
 * Profiles configuration
 */
export interface ProfilesConfig {
  $schema?: string;
  title?: string;
  description?: string;
  profiles: Record<string, ProfileDefinition>;
}

/**
 * Port definition
 */
export interface PortDefinition {
  /** Service name */
  service: string;
  /** Protocol (HTTP, TCP, SMTP, etc.) */
  protocol: string;
  /** Description */
  description: string;
  /** Environment variable name */
  env: string;
  /** Additional note */
  note?: string;
}

/**
 * Ports configuration
 */
export interface PortsConfig {
  $schema?: string;
  title?: string;
  description?: string;
  portRange: {
    start: number;
    end: number;
  };
  ports: Record<string, PortDefinition>;
  /** Ports to check for conflicts */
  checkPorts: number[];
  /** Error messages for port conflicts */
  errorMessages: Record<string, string>;
}

/**
 * Full configuration combining all config files
 */
export interface Config {
  services: ServicesConfig;
  profiles: ProfilesConfig;
  ports: PortsConfig;
}

/**
 * Result type for operations that can fail
 */
export type Result<T, E = Error> =
  | { success: true; value: T }
  | { success: false; error: E };

/**
 * Configuration loading error
 */
export class ConfigLoadError extends Error {
  constructor(
    message: string,
    public readonly filePath: string,
    public readonly cause?: unknown
  ) {
    super(message);
    this.name = 'ConfigLoadError';
  }
}

/**
 * Service selection error
 */
export class ServiceSelectionError extends Error {
  constructor(
    message: string,
    public readonly invalidServices?: string[]
  ) {
    super(message);
    this.name = 'ServiceSelectionError';
  }
}

/**
 * Dependency check error
 */
export class DependencyCheckError extends Error {
  constructor(
    message: string,
    public readonly tool: string,
    public readonly requiredVersion?: string,
    public readonly actualVersion?: string
  ) {
    super(message);
    this.name = 'DependencyCheckError';
  }
}

/**
 * Health check error
 */
export class HealthCheckError extends Error {
  constructor(
    message: string,
    public readonly service: string,
    public readonly timeout?: number
  ) {
    super(message);
    this.name = 'HealthCheckError';
  }
}

/**
 * OS type
 */
export type OSType = 'macos' | 'linux' | 'windows-wsl' | 'unknown';

/**
 * Dependency tool information
 */
export interface DependencyTool {
  name: string;
  command: string;
  versionCommand: string;
  requiredVersion: string;
  installUrl?: string;
}

/**
 * Port check result
 */
export interface PortCheckResult {
  port: number;
  inUse: boolean;
  pid?: number;
  processName?: string;
}

/**
 * Log level
 */
export type LogLevel = 'error' | 'warn' | 'info' | 'debug';

/**
 * Log entry
 */
export interface LogEntry {
  timestamp: Date;
  level: LogLevel;
  service: string;
  message: string;
}
