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

# Function to check if a port is available
check_port() {
    local port=$1
    local description=$2
    
    if nc -z localhost $port 2>/dev/null; then
        echo -e "${RED}❌ PORT $port ($description) is already in use${NC}"
        return 1
    else
        echo -e "${GREEN}✅ PORT $port ($description) is available${NC}"
    fi
    return 0
}

# Function to suggest alternative ports for SonarQube
suggest_sonarqube_alternatives() {
    local port=$1
    local description=$2
    
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
        if ! nc -z localhost $alt_port 2>/dev/null; then
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

# Main function
main() {
    echo -e "${BLUE}🔍 Checking SonarQube port availability...${NC}"
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

# Check if netcat is available
if ! command -v nc &> /dev/null; then
    echo -e "${RED}❌ Error: 'nc' (netcat) is required but not installed.${NC}"
    echo "Please install netcat:"
    echo "  macOS: brew install netcat"
    echo "  Ubuntu/Debian: sudo apt-get install netcat"
    echo "  CentOS/RHEL: sudo yum install nc"
    exit 1
fi

# Run the main function
main "$@"
