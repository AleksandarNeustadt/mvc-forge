#!/bin/bash
# Quick MailHog Start Script

echo "🚀 Starting MailHog..."

# Check if mailhog is already installed
if command -v mailhog &> /dev/null; then
    echo "✅ MailHog found in PATH"
    mailhog
    exit 0
fi

# Check if binary exists in /tmp
if [ -f "/tmp/MailHog_linux_amd64" ]; then
    echo "✅ MailHog binary found in /tmp"
    chmod +x /tmp/MailHog_linux_amd64
    /tmp/MailHog_linux_amd64
    exit 0
fi

# Download and run
echo "📥 Downloading MailHog..."
cd /tmp
wget -q https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_linux_amd64
chmod +x MailHog_linux_amd64

echo "✅ MailHog downloaded and starting..."
echo "🌐 Web UI: http://localhost:8025"
echo "📧 SMTP: localhost:1025"
echo ""
echo "Press Ctrl+C to stop MailHog"
echo ""

./MailHog_linux_amd64

