#!/usr/bin/env tsx
/**
 * Log management module
 *
 * This module handles:
 * - Log stream creation for each service
 * - Log level detection (error, warn, info keywords)
 * - Unified log output with service prefixes and colors
 * - Color output control (--raw option support)
 */

import chalk from 'chalk';
import type { LogLevel, LogEntry } from './types.js';

/**
 * Color configuration for log levels
 */
const LOG_LEVEL_COLORS = {
  error: chalk.red,
  warn: chalk.yellow,
  info: chalk.blue,
  debug: chalk.gray,
} as const;

/**
 * Color configuration for service prefixes
 */
const SERVICE_COLORS = [
  chalk.cyan,
  chalk.magenta,
  chalk.green,
  chalk.yellow,
  chalk.blue,
  chalk.red,
] as const;

/**
 * Detect log level from message content
 *
 * @param message - Log message
 * @returns Detected log level
 */
export function detectLogLevel(message: string): LogLevel {
  const lowerMessage = message.toLowerCase();

  if (
    lowerMessage.includes('error') ||
    lowerMessage.includes('fail') ||
    lowerMessage.includes('exception')
  ) {
    return 'error';
  }

  if (lowerMessage.includes('warn') || lowerMessage.includes('warning')) {
    return 'warn';
  }

  if (lowerMessage.includes('debug') || lowerMessage.includes('trace')) {
    return 'debug';
  }

  return 'info';
}

/**
 * Format timestamp for log output
 *
 * @param date - Date object
 * @returns Formatted timestamp string
 */
export function formatTimestamp(date: Date): string {
  const hours = date.getHours().toString().padStart(2, '0');
  const minutes = date.getMinutes().toString().padStart(2, '0');
  const seconds = date.getSeconds().toString().padStart(2, '0');
  const milliseconds = date.getMilliseconds().toString().padStart(3, '0');

  return `${hours}:${minutes}:${seconds}.${milliseconds}`;
}

/**
 * Create service prefix with color
 *
 * @param serviceName - Name of the service
 * @param serviceIndex - Index for color selection
 * @param useColor - Whether to use color output
 * @returns Formatted service prefix
 */
export function createServicePrefix(
  serviceName: string,
  serviceIndex: number,
  useColor: boolean = true
): string {
  const maxLength = 15; // Maximum service name length
  const paddedName = serviceName.padEnd(maxLength, ' ');

  if (!useColor) {
    return `[${paddedName}]`;
  }

  const colorFn = SERVICE_COLORS[serviceIndex % SERVICE_COLORS.length];
  return colorFn(`[${paddedName}]`);
}

/**
 * Format log message with level, timestamp, and service prefix
 *
 * @param entry - Log entry
 * @param serviceIndex - Index for service color selection
 * @param options - Formatting options
 * @returns Formatted log message
 */
export function formatLogMessage(
  entry: LogEntry,
  serviceIndex: number,
  options: {
    showTimestamp?: boolean;
    showLevel?: boolean;
    useColor?: boolean;
  } = {}
): string {
  const {
    showTimestamp = true,
    showLevel = true,
    useColor = true,
  } = options;

  const parts: string[] = [];

  // Add timestamp
  if (showTimestamp) {
    const timestamp = formatTimestamp(entry.timestamp);
    parts.push(useColor ? chalk.gray(timestamp) : timestamp);
  }

  // Add service prefix
  const servicePrefix = createServicePrefix(entry.service, serviceIndex, useColor);
  parts.push(servicePrefix);

  // Add log level
  if (showLevel && entry.level !== 'info') {
    const levelText = entry.level.toUpperCase();
    const levelFormatted = useColor
      ? LOG_LEVEL_COLORS[entry.level](levelText)
      : levelText;
    parts.push(`[${levelFormatted}]`);
  }

  // Add message
  let message = entry.message;

  // Highlight error messages
  if (entry.level === 'error' && useColor) {
    message = chalk.red(message);
  }

  parts.push(message);

  return parts.join(' ');
}

/**
 * Output unified log with formatting
 *
 * @param entry - Log entry
 * @param serviceIndex - Index for service color selection
 * @param options - Output options
 */
export function outputUnifiedLog(
  entry: LogEntry,
  serviceIndex: number,
  options: {
    raw?: boolean;
    quiet?: boolean;
  } = {}
): void {
  const { raw = false, quiet = false } = options;

  // Skip non-error logs in quiet mode
  if (quiet && entry.level !== 'error') {
    return;
  }

  const formattedMessage = formatLogMessage(entry, serviceIndex, {
    useColor: !raw,
  });

  console.log(formattedMessage);
}

/**
 * Create log stream for a service
 *
 * @param serviceName - Name of the service
 * @param serviceIndex - Index for color selection
 * @returns Log stream function
 */
export function createLogStream(
  serviceName: string,
  serviceIndex: number
): (message: string) => void {
  return (message: string) => {
    const entry: LogEntry = {
      timestamp: new Date(),
      level: detectLogLevel(message),
      service: serviceName,
      message: message.trim(),
    };

    outputUnifiedLog(entry, serviceIndex);
  };
}

/**
 * Parse log output options from command line arguments
 *
 * @param args - Command line arguments
 * @returns Parsed options
 */
export function parseLogOptions(args: string[]): {
  raw: boolean;
  quiet: boolean;
  separate: boolean;
} {
  return {
    raw: args.includes('--raw') || args.includes('--no-color'),
    quiet: args.includes('--logs=quiet'),
    separate: args.includes('--logs=separate'),
  };
}

/**
 * Main function for testing
 */
async function main() {
  console.log('=== Log Manager Test ===\n');

  // Test log level detection
  console.log('--- Log Level Detection ---');
  const testMessages = [
    'Application started successfully',
    'Warning: Configuration file not found',
    'Error: Failed to connect to database',
    'Debug: Processing request #1234',
  ];

  for (const message of testMessages) {
    const level = detectLogLevel(message);
    console.log(`Message: "${message}"`);
    console.log(`Detected level: ${level}\n`);
  }

  // Test log formatting
  console.log('--- Log Formatting ---');
  const services = ['laravel-api', 'admin-app', 'user-app', 'pgsql', 'redis'];

  for (let i = 0; i < services.length; i++) {
    const serviceName = services[i];
    const entry: LogEntry = {
      timestamp: new Date(),
      level: 'info',
      service: serviceName,
      message: `Service ${serviceName} is running`,
    };

    outputUnifiedLog(entry, i);
  }

  // Test error log highlighting
  console.log('\n--- Error Log Highlighting ---');
  const errorEntry: LogEntry = {
    timestamp: new Date(),
    level: 'error',
    service: 'laravel-api',
    message: 'Failed to connect to database: Connection timeout',
  };

  outputUnifiedLog(errorEntry, 0);

  // Test raw output
  console.log('\n--- Raw Output (No Color) ---');
  const rawEntry: LogEntry = {
    timestamp: new Date(),
    level: 'info',
    service: 'admin-app',
    message: 'Application started',
  };

  outputUnifiedLog(rawEntry, 1, { raw: true });

  // Test quiet mode
  console.log('\n--- Quiet Mode (Errors Only) ---');
  const entries: LogEntry[] = [
    {
      timestamp: new Date(),
      level: 'info',
      service: 'user-app',
      message: 'Normal operation',
    },
    {
      timestamp: new Date(),
      level: 'error',
      service: 'user-app',
      message: 'Critical error occurred',
    },
  ];

  for (const entry of entries) {
    outputUnifiedLog(entry, 2, { quiet: true });
  }
}

// Run main if this file is executed directly
if (import.meta.url === `file://${process.argv[1]}`) {
  main().catch((error) => {
    console.error('Unexpected error:', error);
    process.exit(1);
  });
}
