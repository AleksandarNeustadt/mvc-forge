#!/bin/bash
# Script to move all folders from core/classes/ to core/

cd "$(dirname "$0")"

echo "📦 Moving folders from core/classes/ to core/..."

# Move all folders from core/classes/ to core/
if [ -d "core/classes" ]; then
    for folder in core/classes/*; do
        if [ -d "$folder" ]; then
            folder_name=$(basename "$folder")
            echo "Moving $folder_name..."
            mv "$folder" "core/$folder_name"
        fi
    done
    
    echo "✅ All folders moved successfully!"
    echo "🗑️  Removing empty core/classes directory..."
    rmdir core/classes 2>/dev/null || echo "⚠️  core/classes directory not empty or already removed"
    echo "✅ Done!"
else
    echo "❌ core/classes directory not found!"
    exit 1
fi

