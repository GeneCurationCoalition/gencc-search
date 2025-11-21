#!/bin/bash

# Check if input file is provided
if [ $# -eq 0 ]; then
    echo "Usage: $0 <input_file>"
    echo "Example: $0 logo_me.jpg"
    echo "Accepts any image format, outputs as PNG"
    exit 1
fi

INPUT="$1"
# Create output filename by adding .processed and converting to .png
BASENAME="${INPUT%.*}"
OUTPUT="${BASENAME}.processed.png"
EXTENSION="${INPUT##*.}"

echo "Processing: $INPUT -> $OUTPUT (converting to PNG)"

# Handle SVG files separately using rsvg-convert if available, otherwise try Inkscape
EXTENSION_LOWER=$(echo "$EXTENSION" | tr '[:upper:]' '[:lower:]')
if [[ "$EXTENSION_LOWER" == "svg" ]]; then
    echo "SVG detected, using specialized converter..."
    if command -v rsvg-convert &> /dev/null; then
        # Use rsvg-convert for SVG files
        rsvg-convert -w 720 -h 320 --keep-aspect-ratio "$INPUT" -o temp_scaled.png
    elif command -v inkscape &> /dev/null; then
        # Use Inkscape as fallback
        inkscape "$INPUT" --export-type=png --export-filename=temp_scaled.png -w 720 -h 320
    else
        echo "Error: SVG file detected but neither rsvg-convert nor inkscape is installed."
        echo "Install one of them:"
        echo "  brew install librsvg    # for rsvg-convert"
        echo "  brew install inkscape   # for inkscape"
        exit 1
    fi
else
    # Step 1: Scale the image to fit within 720x320 bounds (40px padding on all sides)
    convert "$INPUT" -resize "720x320" temp_scaled.png
fi

# Step 2: Add white background and center to exactly 800x400 (ensures 40px minimum padding)
convert temp_scaled.png -background white -gravity center -extent 800x400 "$OUTPUT"

# Clean up temporary file
rm temp_scaled.png

echo "Done! Output saved as: $OUTPUT"
