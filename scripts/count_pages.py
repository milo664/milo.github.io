#!/usr/bin/env python3
"""
COUNT_PAGES.PY
Đếm số trang chính xác trong file Word
"""

import sys
import json
import zipfile
from xml.etree import ElementTree as ET

def count_pages_from_properties(docx_path):
    """
    Đếm số trang từ document properties (app.xml)
    Đây là cách chính xác nhất vì Word tự lưu thông tin này
    """
    try:
        with zipfile.ZipFile(docx_path, 'r') as zip_ref:
            # Đọc app.xml chứa thông tin về số trang
            app_xml = zip_ref.read('docProps/app.xml')
            
            # Parse XML
            root = ET.fromstring(app_xml)
            
            # Namespace cho extended properties
            ns = {'ep': 'http://schemas.openxmlformats.org/officeDocument/2006/extended-properties'}
            
            # Tìm tag Pages
            pages = root.find('.//ep:Pages', ns)
            
            if pages is not None and pages.text:
                return int(pages.text)
                
    except Exception as e:
        pass
    
    return None

def count_pages_from_sections(docx_path):
    """
    Ước tính số trang dựa trên page size và word count
    (phương pháp dự phòng nếu không có thông tin trong properties)
    """
    try:
        with zipfile.ZipFile(docx_path, 'r') as zip_ref:
            # Đọc document.xml
            doc_xml = zip_ref.read('word/document.xml')
            root = ET.fromstring(doc_xml)
            
            # Đếm số paragraph
            ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
            paragraphs = root.findall('.//w:p', ns)
            
            # Đếm page breaks
            page_breaks = root.findall('.//w:br[@w:type="page"]', ns)
            
            # Ước tính: mỗi 30-40 paragraphs ~ 1 trang
            # Cộng thêm số page breaks thủ công
            estimated_pages = len(page_breaks) + max(1, len(paragraphs) // 35)
            
            return estimated_pages
            
    except Exception as e:
        pass
    
    return None

def main():
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No file path provided'}))
        sys.exit(1)
    
    docx_path = sys.argv[1]
    
    # Thử đếm từ properties trước (chính xác nhất)
    pages = count_pages_from_properties(docx_path)
    
    # Nếu không được thì ước tính
    if pages is None:
        pages = count_pages_from_sections(docx_path)
    
    # Nếu vẫn không được thì trả về null
    if pages is None:
        print(json.dumps({'pages': None, 'message': 'Không xác định được số trang'}))
    else:
        print(json.dumps({'pages': pages}))
    
    sys.exit(0)

if __name__ == '__main__':
    main()