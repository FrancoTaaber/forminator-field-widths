#!/bin/bash
#
# Build script for Forminator Field Widths plugin
# Creates an optimized distribution ZIP
#
# Usage: ./build.sh
#

set -e

# Configuration
PLUGIN_NAME="forminator-field-widths"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_DIR="${PLUGIN_DIR}/build"
DIST_DIR="${BUILD_DIR}/${PLUGIN_NAME}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Start build
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Building ${PLUGIN_NAME}${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Get version from plugin file
VERSION=$(grep -m1 "^\s*\*\s*Version:" "${PLUGIN_DIR}/forminator-field-widths.php" | sed 's/.*Version:\s*//' | tr -d ' ')
log_info "Building version: ${VERSION}"

# Clean previous build
log_info "Cleaning previous build..."
rm -rf "${BUILD_DIR}"
mkdir -p "${DIST_DIR}"

# PHP syntax check
log_info "Running PHP syntax check..."
SYNTAX_ERRORS=0
while IFS= read -r file; do
    if ! php -l "$file" > /dev/null 2>&1; then
        log_error "Syntax error in: $file"
        SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
    fi
done < <(find "${PLUGIN_DIR}" -name "*.php" -not -path "*/vendor/*" -not -path "*/build/*" -not -path "*/tests/*")

if [ $SYNTAX_ERRORS -gt 0 ]; then
    log_error "Found $SYNTAX_ERRORS PHP syntax errors. Aborting build."
    exit 1
fi
log_success "PHP syntax check passed"

# Copy main plugin files
log_info "Copying plugin files..."
cp "${PLUGIN_DIR}/forminator-field-widths.php" "${DIST_DIR}/"
cp "${PLUGIN_DIR}/uninstall.php" "${DIST_DIR}/" 2>/dev/null || echo ""
cp "${PLUGIN_DIR}/README.md" "${DIST_DIR}/" 2>/dev/null || echo ""
cp "${PLUGIN_DIR}/readme.txt" "${DIST_DIR}/" 2>/dev/null || echo ""

# Copy directories
log_info "Copying directories..."
cp -r "${PLUGIN_DIR}/includes" "${DIST_DIR}/"
cp -r "${PLUGIN_DIR}/admin" "${DIST_DIR}/"
cp -r "${PLUGIN_DIR}/languages" "${DIST_DIR}/" 2>/dev/null || mkdir -p "${DIST_DIR}/languages"

# Create index.php files for security
log_info "Creating security index.php files..."
find "${DIST_DIR}" -type d -exec sh -c 'echo "<?php // Silence is golden." > "$1/index.php"' _ {} \;

# Remove any development files that might have been copied
log_info "Cleaning development files..."
find "${DIST_DIR}" -name "*.map" -delete
find "${DIST_DIR}" -name ".DS_Store" -delete
find "${DIST_DIR}" -name "*.log" -delete
find "${DIST_DIR}" -name ".gitkeep" -delete

# Create the ZIP file
log_info "Creating ZIP archive..."
cd "${BUILD_DIR}"
zip -r "${PLUGIN_NAME}.zip" "${PLUGIN_NAME}" \
    -x "*.DS_Store" \
    -x "*__MACOSX*" \
    -x "*.git*" \
    -x "*.map"

# Get file sizes
ZIP_SIZE=$(du -h "${PLUGIN_NAME}.zip" | cut -f1)
UNZIPPED_SIZE=$(du -sh "${PLUGIN_NAME}" | cut -f1)

# Count files
FILE_COUNT=$(find "${PLUGIN_NAME}" -type f | wc -l | tr -d ' ')
PHP_COUNT=$(find "${PLUGIN_NAME}" -name "*.php" | wc -l | tr -d ' ')
JS_COUNT=$(find "${PLUGIN_NAME}" -name "*.js" | wc -l | tr -d ' ')
CSS_COUNT=$(find "${PLUGIN_NAME}" -name "*.css" | wc -l | tr -d ' ')

# Build complete
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Build Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "  ${BLUE}Version:${NC}      ${VERSION}"
echo -e "  ${BLUE}ZIP File:${NC}     ${BUILD_DIR}/${PLUGIN_NAME}.zip"
echo -e "  ${BLUE}ZIP Size:${NC}     ${ZIP_SIZE}"
echo -e "  ${BLUE}Unzipped:${NC}     ${UNZIPPED_SIZE}"
echo ""
echo -e "  ${BLUE}Files:${NC}        ${FILE_COUNT} total"
echo -e "  ${BLUE}PHP:${NC}          ${PHP_COUNT} files"
echo -e "  ${BLUE}JS:${NC}           ${JS_COUNT} files"
echo -e "  ${BLUE}CSS:${NC}          ${CSS_COUNT} files"
echo ""
echo -e "${GREEN}The ZIP file is ready for distribution.${NC}"
echo ""

# Verify the ZIP
log_info "Verifying ZIP integrity..."
if unzip -t "${PLUGIN_NAME}.zip" > /dev/null 2>&1; then
    log_success "ZIP file integrity verified"
else
    log_error "ZIP file integrity check failed!"
    exit 1
fi
