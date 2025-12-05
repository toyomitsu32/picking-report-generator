#!/bin/bash
# Setup script for Picking Report Generator

echo "==================================="
echo "Picking Report Generator - Setup"
echo "==================================="
echo ""

# Check PHP
echo "Checking PHP..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1)
    echo "✓ PHP found: $PHP_VERSION"
else
    echo "✗ PHP not found. Please install PHP 8.0 or higher."
    exit 1
fi

# Check Composer
echo "Checking Composer..."
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version)
    echo "✓ Composer found: $COMPOSER_VERSION"
else
    echo "✗ Composer not found. Please install Composer."
    exit 1
fi

# Install dependencies
echo ""
echo "Installing dependencies..."
composer install

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo ""
    echo "Creating .env file..."
    cp .env.example .env
    echo "✓ .env file created. Please edit it with your settings."
fi

# Set permissions
echo ""
echo "Setting directory permissions..."
chmod -R 775 logs storage
echo "✓ Permissions set"

# Verify structure
echo ""
echo "Verifying directory structure..."
REQUIRED_DIRS=("src" "tests" "config" "logs" "public" "storage/tmp" "storage/pdf")
for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "✓ $dir exists"
    else
        echo "✗ $dir missing"
    fi
done

echo ""
echo "==================================="
echo "Setup complete!"
echo "==================================="
echo ""
echo "Next steps:"
echo "1. Edit .env file with your configuration"
echo "2. Generate password hash: php -r \"echo password_hash('your_password', PASSWORD_BCRYPT);\""
echo "3. Configure your web server (Apache/Nginx)"
echo "4. Run tests: vendor/bin/phpunit"
echo ""
