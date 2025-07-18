#!/usr/bin/env bash

# =============================================================================
# SonarQube Port Availability Checker
# =============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# SonarQube ports (port:description format)
SONARQUBE_PORTS=(
    "9000:SonarQube Web Interface"
    "5432:PostgreSQL (SonarQube Database)"
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
    local os=$(detect_os)
    local port_in_use=false

    case $os in
        "windows")
            # Use netstat on Windows (available by default)
            if netstat -an | grep -q ":$port "; then
                port_in_use=true
            fi
            ;;
        "macos")
            # Use netstat on macOS (available by default)
            if netstat -an | grep -q "\\.$port "; then
                port_in_use=true
            fi
            ;;
        "linux")
            # Try multiple methods on Linux
            if command -v ss &> /dev/null; then
                # Use ss (modern replacement for netstat)
                if ss -tuln | grep -q ":$port "; then
                    port_in_use=true
                fi
            elif command -v netstat &> /dev/null; then
                # Fall back to netstat
                if netstat -tuln | grep -q ":$port "; then
                    port_in_use=true
                fi
            elif command -v nc &> /dev/null; then
                # Fall back to netcat
                if nc -z localhost $port 2>/dev/null; then
                    port_in_use=true
                fi
            else
                echo -e "${YELLOW}⚠️  Cannot check port $port - no suitable tools available${NC}"
                return 0
            fi
            ;;
        *)
            # Unknown OS - try netcat if available
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
        echo -e "${RED}❌ PORT $port ($description) is already in use${NC}"
        return 1
    else
        echo -e "${GREEN}✅ PORT $port ($description) is available${NC}"
    fi
    return 0
}

# Function to suggest alternative ports for SonarQube (platform-independent)
suggest_sonarqube_alternatives() {
    local port=$1
    local description=$2
    local os=$(detect_os)

    echo -e "${BLUE}💡 Searching for alternative ports for $description...${NC}"

    case $port in
        9000)
            # Suggest common alternative ports for web interfaces
            local alternatives=(9001 9002 9003 9080 9090 8080 8090)
            ;;
        5432)
            # Suggest common alternative ports for PostgreSQL
            local alternatives=(5433 5434 5435 15432 25432)
            ;;
    esac

    for alt_port in "${alternatives[@]}"; do
        local port_in_use=false

        case $os in
            "windows")
                if netstat -an | grep -q ":$alt_port "; then
                    port_in_use=true
                fi
                ;;
            "macos")
                if netstat -an | grep -q "\\.$alt_port "; then
                    port_in_use=true
                fi
                ;;
            "linux")
                if command -v ss &> /dev/null; then
                    if ss -tuln | grep -q ":$alt_port "; then
                        port_in_use=true
                    fi
                elif command -v netstat &> /dev/null; then
                    if netstat -tuln | grep -q ":$alt_port "; then
                        port_in_use=true
                    fi
                elif command -v nc &> /dev/null; then
                    if nc -z localhost $alt_port 2>/dev/null; then
                        port_in_use=true
                    fi
                fi
                ;;
            *)
                if command -v nc &> /dev/null; then
                    if nc -z localhost $alt_port 2>/dev/null; then
                        port_in_use=true
                    fi
                fi
                ;;
        esac

        if [ "$port_in_use" = false ]; then
            echo -e "${GREEN}   Suggested alternative: $alt_port${NC}"
            show_sonarqube_port_change_instructions "$port" "$description" "$alt_port"
            return 0
        fi
    done

    echo -e "${YELLOW}   No common alternatives available. Try manually selecting a port.${NC}"
    return 1
}

# Function to show SonarQube port change instructions
show_sonarqube_port_change_instructions() {
    local port=$1
    local description=$2
    local suggested_port=$3

    echo -e "${BLUE}📝 To change SonarQube port $port ($description) to $suggested_port:${NC}"

    case $port in
        9000)
            echo "   Update containers/docker-compose.sonarqube.yml:"
            echo "   ports: \"$suggested_port:9000\""
            echo "   Also update SONAR_WEB_PORT environment variable if needed"
            ;;
        5432)
            echo "   Update containers/docker-compose.sonarqube.yml:"
            echo "   ports: \"$suggested_port:5432\""
            ;;
    esac
    echo ""
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
    echo -e "${BLUE}🔍 Checking SonarQube port availability...${NC}"
    echo ""

    # Check if we have the required tools
    if ! check_requirements; then
        exit 1
    fi
    echo ""

    local unavailable_ports=()
    local failed=false

    # Check SonarQube ports
    for port_info in "${SONARQUBE_PORTS[@]}"; do
        local port="${port_info%%:*}"
        local description="${port_info#*:}"
        if ! check_port "$port" "$description"; then
            unavailable_ports+=("$port_info")
            failed=true
        fi
    done

    echo ""

    # Summary and recommendations
    if [ "$failed" = true ]; then
        echo -e "${YELLOW}⚠️  Some SonarQube ports are unavailable!${NC}"
        echo ""
        echo -e "${YELLOW}🔧 SOLUTIONS:${NC}"
        echo "1. Stop the services using these ports"
        echo "2. Use alternative ports (see suggestions below)"
        echo "3. Skip SonarQube setup (it's optional)"
        echo ""

        echo -e "${BLUE}💡 PORT ALTERNATIVES:${NC}"
        for port_info in "${unavailable_ports[@]}"; do
            local port="${port_info%%:*}"
            local description="${port_info#*:}"
            suggest_sonarqube_alternatives "$port" "$description"
        done

        echo -e "${YELLOW}💡 NOTE: SonarQube is optional. You can continue without it.${NC}"
        return 1
    else
        echo -e "${GREEN}✅ ALL SONARQUBE PORTS ARE AVAILABLE!${NC}"
        echo -e "${GREEN}🚀 SonarQube setup can proceed safely.${NC}"
        return 0
    fi
}

# Run the main function
main "$@"
