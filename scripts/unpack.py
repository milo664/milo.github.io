#!/usr/bin/env python3
"""
unpack.py - Unpack Word (.docx) files
Usage: python3 unpack.py input.docx output_dir
"""

import sys
import zipfile
import os

def unpack_docx(docx_path, output_dir):
    """
    Unpack a .docx file (which is a ZIP archive) to a directory
    """
    if not os.path.exists(docx_path):
        print(f"Error: File '{docx_path}' not found")
        sys.exit(1)
    
    if not zipfile.is_zipfile(docx_path):
        print(f"Error: '{docx_path}' is not a valid ZIP file")
        sys.exit(1)
    
    # Create output directory
    os.makedirs(output_dir, exist_ok=True)
    
    # Extract all files
    try:
        with zipfile.ZipFile(docx_path, 'r') as zip_ref:
            zip_ref.extractall(output_dir)
        print(f"Successfully unpacked to: {output_dir}")
        return True
    except Exception as e:
        print(f"Error unpacking file: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python3 unpack.py input.docx output_dir")
        sys.exit(1)
    
    docx_path = sys.argv[1]
    output_dir = sys.argv[2]
    
    unpack_docx(docx_path, output_dir)