#!/usr/bin/env python3
"""Configuration files validation script"""
import json
import sys
from pathlib import Path

def validate_services(services_file):
    """Validate services.json"""
    print("=== Service names uniqueness check ===")
    with open(services_file) as f:
        data = json.load(f)
        services = data['services']
        names = list(services.keys())

        if len(names) == len(set(names)):
            print(f"✓ All {len(names)} service names are unique")
            print(f"  Services: {', '.join(names)}")
        else:
            print("✗ Duplicate service names found")
            return False

        # Check dependencies
        print("\n=== Dependency validation ===")
        for name, service in services.items():
            if 'dependencies' in service:
                for dep in service['dependencies']:
                    if dep not in services:
                        print(f"✗ Service '{name}' depends on unknown service '{dep}'")
                        return False
        print("✓ All dependencies are valid")

    return True

def validate_profiles(profiles_file, services_file):
    """Validate profiles.json"""
    print("\n=== Profile names validation ===")
    with open(profiles_file) as f:
        profiles_data = json.load(f)
        profiles = profiles_data['profiles']
        names = list(profiles.keys())
        print(f"✓ Profile names: {', '.join(names)}")

    # Load services for validation
    with open(services_file) as f:
        services = json.load(f)['services']
        service_names = set(services.keys())

    print("\n=== Profile service validation ===")
    for name, profile in profiles.items():
        for service in profile['services']:
            if service not in service_names:
                print(f"✗ Profile '{name}' references unknown service '{service}'")
                return False
    print("✓ All profile services are valid")

    return True

def validate_ports(ports_file):
    """Validate ports.json"""
    print("\n=== Port range validation ===")
    with open(ports_file) as f:
        ports_data = json.load(f)
        port_range = ports_data['portRange']
        ports = ports_data['ports']
        check_ports = ports_data['checkPorts']

        print(f"✓ Port range: {port_range['start']}-{port_range['end']}")
        print(f"✓ Defined ports: {len(ports)} ports")
        print(f"✓ Check ports: {', '.join(map(str, check_ports))}")

        # Validate port numbers
        for port_str in ports.keys():
            port = int(port_str)
            if port < 1 or port > 65535:
                print(f"✗ Invalid port number: {port}")
                return False
        print("✓ All port numbers are valid")

    return True

def main():
    """Main validation function"""
    base_dir = Path(__file__).parent / 'config'
    services_file = base_dir / 'services.json'
    profiles_file = base_dir / 'profiles.json'
    ports_file = base_dir / 'ports.json'

    success = True
    success = validate_services(services_file) and success
    success = validate_profiles(profiles_file, services_file) and success
    success = validate_ports(ports_file) and success

    if success:
        print("\n✓ All configuration files are valid")
        return 0
    else:
        print("\n✗ Configuration validation failed")
        return 1

if __name__ == '__main__':
    sys.exit(main())
