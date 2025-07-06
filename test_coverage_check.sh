#!/bin/bash

# Simulate the coverage check logic
TEST_OUTPUT="                                                                 Total: 85.3 %"

# Extract coverage percentage from the output
COVERAGE=$(echo "$TEST_OUTPUT" | grep -o "Total: [0-9]*\.[0-9]*" | grep -o "[0-9]*\.[0-9]*")

if [ -z "$COVERAGE" ]; then
  echo "Could not determine coverage percentage. Aborting the push."
  exit 1
fi

# Convert coverage to integer for comparison (remove decimal point)
# Example: 85.3 becomes 421, 80.0 becomes 800
COVERAGE_INT=$(echo "$COVERAGE" | sed 's/\.//')
MIN_COVERAGE_INT=800  # 80.0 with decimal removed

# Add trailing zero if needed (e.g., 85.3 -> 421, but 42 -> 420)
COVERAGE_LEN=${#COVERAGE_INT}
if [ $COVERAGE_LEN -eq 2 ]; then
  COVERAGE_INT="${COVERAGE_INT}0"
fi

echo "Coverage: ${COVERAGE}%"
echo "Required: 80.0%"
echo "Coverage INT: $COVERAGE_INT"
echo "Min Coverage INT: $MIN_COVERAGE_INT"

if [ "$COVERAGE_INT" -lt "$MIN_COVERAGE_INT" ]; then
  echo "ERROR: Coverage ${COVERAGE}% is below the required 80% threshold. Aborting the push."
  exit 1
else
  echo "Coverage check passed!"
fi
