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

echo "Processing: $INPUT -> $OUTPUT (converting to PNG)"

# Step 1: Scale the image to fit within 720x320 bounds (40px padding on all sides)
convert "$INPUT" -resize "720x320" temp_scaled.png

# Step 2: Add white background and center to exactly 800x400 (ensures 40px minimum padding)
convert temp_scaled.png -background white -gravity center -extent 800x400 "$OUTPUT"

# Clean up temporary file
rm temp_scaled.png

echo "Done! Output saved as: $OUTPUT"
