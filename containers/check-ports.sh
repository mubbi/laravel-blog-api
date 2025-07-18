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

# Function to check if a port is available
check_port() {
    local port=$1
    local description=$2
    local optional=${3:-false}
    
    if nc -z localhost $port 2>/dev/null; then
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

# Function to suggest alternative ports
suggest_alternative_port() {
    local base_port=$1
    local description=$2
    local start_range=$((base_port + 1))
    local end_range=$((base_port + 100))
    
    echo -e "${BLUE}💡 Searching for alternative ports for $description...${NC}"
    
    for ((port=start_range; port<=end_range; port++)); do
        if ! nc -z localhost $port 2>/dev/null; then
            echo -e "${GREEN}   Suggested alternative: $port${NC}"
            return 0
        fi
    done
    
    echo -e "${YELLOW}   No alternatives found in range $start_range-$end_range${NC}"
    return 1
}

# Function to show port change instructions
show_port_change_instructions() {
    local port=$1
    local description=$2
    local suggested_port=$3
    
    echo -e "${BLUE}📝 To change port $port ($description) to $suggested_port:${NC}"
    
    case $port in
        8081)
            echo "   Update containers/docker-compose.yml: ports: \"$suggested_port:80\""
            ;;
        8001)
            echo "   Update containers/docker-compose.yml: ports: \"$suggested_port:8001\""
            ;;
        3306)
            echo "   Update containers/docker-compose.yml: ports: \"$suggested_port:3306\""
            ;;
        6379)
            echo "   Update containers/docker-compose.yml: ports: \"$suggested_port:6379\""
            ;;
        3307)
            echo "   Update containers/docker-compose.test.yml: ports: \"$suggested_port:3306\""
            ;;
        6380)
            echo "   Update containers/docker-compose.test.yml: ports: \"$suggested_port:6379\""
            ;;
        9000)
            echo "   Update containers/docker-compose.sonarqube.yml: ports: \"$suggested_port:9000\""
            ;;
        5432)
            echo "   Update containers/docker-compose.sonarqube.yml: ports: \"$suggested_port:5432\""
            ;;
    esac
    echo ""
}

# Main function
main() {
    echo -e "${BLUE}🔍 Checking port availability for Laravel Blog API Docker setup...${NC}"
    echo ""
    
    local unavailable_ports=()
    local failed_required=false
    
    # Check required ports
    echo -e "${BLUE}📋 Checking required ports:${NC}"
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
    echo -e "${BLUE}📋 Checking optional ports (SonarQube):${NC}"
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
        echo -e "${YELLOW}🔧 SOLUTIONS:${NC}"
        echo "1. Stop the services using these ports"
        echo "2. Use alternative ports (see suggestions below)"
        echo "3. Modify docker-compose files with new ports"
        echo ""
        
        echo -e "${BLUE}💡 PORT ALTERNATIVES:${NC}"
        for port_info in "${unavailable_ports[@]}"; do
            local port="${port_info%%:*}"
            local description="${port_info#*:}"
            
            # Find suggested alternative
            local start_range=$((port + 1))
            for ((alt_port=start_range; alt_port<=start_range+50; alt_port++)); do
                if ! nc -z localhost $alt_port 2>/dev/null; then
                    show_port_change_instructions "$port" "$description" "$alt_port"
                    break
                fi
            done
        done
        
        echo -e "${YELLOW}⚠️  After making changes, run this script again to verify.${NC}"
        return 1
    else
        echo -e "${GREEN}✅ ALL REQUIRED PORTS ARE AVAILABLE!${NC}"
        echo -e "${GREEN}🚀 Docker setup can proceed safely.${NC}"
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
