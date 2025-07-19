#!/usr/bin/env bash

# =============================================================================
# Port Availability Checker for Laravel Blog API Docker Setup
# =============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Ports used by the application (port:description format)
REQUIRED_PORTS=(
    "8081:Laravel API (Main App)"
    "8001:Xdebug Port"
    "3306:MySQL Database"
    "6379:Redis Cache"
    "3307:MySQL Test Database"
    "6380:Redis Test Cache"
)

OPTIONAL_PORTS=(
    "9000:SonarQube Web Interface"
    "5432:PostgreSQL (SonarQube)"
)

# Function to detect operating system
detect_os() {
    if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" || "$OSTYPE" == "win32" ]]; then
        echo "windows"
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        echo "linux"
    else
        echo "unknown"
    fi
}

# Function to check if a port is available (platform-independent)
check_port() {
    local port=$1
    local description=$2
    local optional=${3:-false}
    local os=$(detect_os)
    local port_in_use=false
    case $os in
        "windows")
            # Only consider LISTENING state
            if netstat -an | grep -E ":$port[[:space:]]" | grep -iq "LISTEN"; then
                port_in_use=true
            fi
            ;;
        "macos")
            # Only consider LISTEN state
            if netstat -an | grep -E "\.$port[[:space:]]" | grep -iq "LISTEN"; then
                port_in_use=true
            fi
            ;;
        "linux")
            if command -v ss &> /dev/null; then
                if ss -tuln | awk '{print $4,$1}' | grep -E "[:.]$port[[:space:]]" | grep -iq "LISTEN"; then
                    port_in_use=true
                fi
            elif command -v netstat &> /dev/null; then
                if netstat -tuln | grep -E ":$port[[:space:]]" | grep -iq "LISTEN"; then
                    port_in_use=true
                fi
            elif command -v nc &> /dev/null; then
                if nc -z localhost $port 2>/dev/null; then
                    port_in_use=true
                fi
            else
                echo -e "${YELLOW}⚠️  Cannot check port $port - no suitable tools available${NC}"
                return 0
            fi
            ;;
        *)
            if command -v nc &> /dev/null; then
                if nc -z localhost $port 2>/dev/null; then
                    port_in_use=true
                fi
            else
                echo -e "${YELLOW}⚠️  Cannot check port $port - unsupported OS${NC}"
                return 0
            fi
            ;;
    esac

    if [ "$port_in_use" = true ]; then
        if [ "$optional" = true ]; then
            echo -e "${YELLOW}⚠️  OPTIONAL PORT $port ($description) is already in use${NC}"
        else
            echo -e "${RED}❌ PORT $port ($description) is already in use${NC}"
            return 1
        fi
    else
        if [ "$optional" = true ]; then
            echo -e "${GREEN}✅ OPTIONAL PORT $port ($description) is available${NC}"
        else
            echo -e "${GREEN}✅ PORT $port ($description) is available${NC}"
        fi
    fi
    return 0
}

# Check if required tools are available and provide helpful messages
check_requirements() {
    local os=$(detect_os)
    local tools_available=false

    case $os in
        "windows")
            if command -v netstat &> /dev/null; then
                tools_available=true
                echo -e "${GREEN}✅ Using netstat for port checking (Windows)${NC}"
            fi
            ;;
        "macos")
            if command -v netstat &> /dev/null; then
                tools_available=true
                echo -e "${GREEN}✅ Using netstat for port checking (macOS)${NC}"
            fi
            ;;
        "linux")
            if command -v ss &> /dev/null; then
                tools_available=true
                echo -e "${GREEN}✅ Using ss for port checking (Linux)${NC}"
            elif command -v netstat &> /dev/null; then
                tools_available=true
                echo -e "${GREEN}✅ Using netstat for port checking (Linux)${NC}"
            elif command -v nc &> /dev/null; then
                tools_available=true
                echo -e "${GREEN}✅ Using netcat for port checking (Linux)${NC}"
            fi
            ;;
        *)
            if command -v nc &> /dev/null; then
                tools_available=true
                echo -e "${GREEN}✅ Using netcat for port checking${NC}"
            fi
            ;;
    esac

    if [ "$tools_available" = false ]; then
        echo -e "${RED}❌ Error: No suitable tools available for port checking.${NC}"
        echo "Please install one of the following:"
        case $os in
            "linux")
                echo "  • ss: sudo apt-get install iproute2 (Ubuntu/Debian) or sudo yum install iproute (CentOS/RHEL)"
                echo "  • netstat: sudo apt-get install net-tools (Ubuntu/Debian) or sudo yum install net-tools (CentOS/RHEL)"
                echo "  • netcat: sudo apt-get install netcat (Ubuntu/Debian) or sudo yum install nc (CentOS/RHEL)"
                ;;
            "macos")
                echo "  • netcat: brew install netcat"
                ;;
            "windows")
                echo "  • netstat should be available by default on Windows"
                echo "  • If using WSL, install net-tools: sudo apt-get install net-tools"
                ;;
            *)
                echo "  • netcat: install via your package manager"
                ;;
        esac
        return 1
    fi

    return 0
}

# Main function
main() {
    echo -e "${BLUE}��� Checking port availability for Laravel Blog API Docker setup...${NC}"
    echo ""

    # Check if we have the required tools
    if ! check_requirements; then
        exit 1
    fi
    echo ""

    local unavailable_ports=()
    local failed_required=false

    # Check required ports
    echo -e "${BLUE}��� Checking required ports:${NC}"
    for port_info in "${REQUIRED_PORTS[@]}"; do
        local port="${port_info%%:*}"
        local description="${port_info#*:}"
        if ! check_port "$port" "$description"; then
            unavailable_ports+=("$port_info")
            failed_required=true
        fi
    done

    echo ""

    # Check optional ports (SonarQube)
    echo -e "${BLUE}��� Checking optional ports (SonarQube):${NC}"
    for port_info in "${OPTIONAL_PORTS[@]}"; do
        local port="${port_info%%:*}"
        local description="${port_info#*:}"
        check_port "$port" "$description" true
    done

    echo ""

    # Summary and recommendations
    if [ "$failed_required" = true ]; then
        echo -e "${RED}❌ SETUP CANNOT CONTINUE - Some required ports are unavailable!${NC}"
        echo ""
        echo -e "${YELLOW}��� SOLUTIONS:${NC}"
        echo "1. Stop the services using these ports"
        echo "2. Use alternative ports (see suggestions below)"
        echo "3. Modify docker-compose files with new ports"
        echo ""
        echo -e "${YELLOW}⚠️  After making changes, run this script again to verify.${NC}"
        return 1
    else
        echo -e "${GREEN}✅ ALL REQUIRED PORTS ARE AVAILABLE!${NC}"
        echo -e "${GREEN}��� Docker setup can proceed safely.${NC}"
        return 0
    fi
}

# Run the main function
main "$@"
