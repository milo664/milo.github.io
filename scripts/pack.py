#!/usr/bin/env python3
"""
pack.py - Pack directory back into Word (.docx) file
Usage: python3 pack.py input_dir output.docx
"""

import sys
import zipfile
import os

def pack_docx(input_dir, output_path):
    """
    Pack a directory back into a .docx file (ZIP archive)
    """
    if not os.path.exists(input_dir):
        print(f"Error: Directory '{input_dir}' not found")
        sys.exit(1)
    
    if not os.path.isdir(input_dir):
        print(f"Error: '{input_dir}' is not a directory")
        sys.exit(1)
    
    try:
        with zipfile.ZipFile(output_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
            # Walk through directory
            for root, dirs, files in os.walk(input_dir):
                for file in files:
                    file_path = os.path.join(root, file)
                    # Calculate archive name (relative path)
                    arcname = os.path.relpath(file_path, input_dir)
                    zipf.write(file_path, arcname)
        
        print(f"Successfully packed to: {output_path}")
        return True
    except Exception as e:
        print(f"Error packing file: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python3 pack.py input_dir output.docx")
        sys.exit(1)
    
    input_dir = sys.argv[1]
    output_path = sys.argv[2]
    
    pack_docx(input_dir, output_path)