#!/bin/bash
# Fix upload directory permissions

# Create directories if they don't exist
mkdir -p storage/uploads/blog
mkdir -p storage/uploads

# Set permissions to be writable by web server
chmod -R 775 storage/uploads
chmod -R 775 storage/uploads/blog

# Set ownership to www-data (web server user)
# This allows PHP to write files
if id "www-data" &>/dev/null; then
    chown -R www-data:www-data storage/uploads
    echo "Ownership set to www-data:www-data"
else
    echo "Warning: www-data user not found. Setting ownership to current user."
    chown -R $(whoami):$(whoami) storage/uploads
    chmod -R 777 storage/uploads
fi

echo "Upload directory permissions fixed!"
echo "Directory: $(pwd)/storage/uploads/blog"
echo "Permissions:"
ls -la storage/uploads/ | grep blog
ls -la storage/uploads/blog/ | head -5

