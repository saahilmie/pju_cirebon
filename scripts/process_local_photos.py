#!/usr/bin/env python3
"""
Process Local Photos for Coordinate Extraction

Reads photos from a local folder and extracts GPS coordinates from:
1. EXIF metadata (GPS tags)
2. OCR watermark text (e.g., -6.7586, 108.49135)
3. Filename patterns

Then updates the database with the extracted coordinates.

Usage:
    python process_local_photos.py
    python process_local_photos.py --folder "D:\KP\custom_folder"
    python process_local_photos.py --limit 100
"""

import os
import re
import sys
import subprocess
from pathlib import Path

# Auto-install dependencies
def install_deps():
    try:
        import psycopg2
        from PIL import Image
        import pytesseract
    except ImportError:
        print("📦 Installing dependencies...")
        subprocess.check_call([sys.executable, "-m", "pip", "install", "psycopg2-binary", "python-dotenv", "Pillow", "pytesseract"])
        print("✅ Dependencies installed.")

install_deps()

import psycopg2
from PIL import Image
from PIL.ExifTags import TAGS, GPSTAGS
from dotenv import load_dotenv

try:
    import pytesseract
    OCR_AVAILABLE = True
    # Common Tesseract path on Windows
    tess_path = r'C:\Program Files\Tesseract-OCR\tesseract.exe'
    if os.path.exists(tess_path):
        pytesseract.pytesseract.tesseract_cmd = tess_path
except ImportError:
    OCR_AVAILABLE = False

load_dotenv()

# Configuration
PHOTO_FOLDER = r"D:\KP\foto_dishub_download"

DB_CONFIG = {
    'dbname': os.getenv('DB_DATABASE', 'pju_cirebon'),
    'user': os.getenv('DB_USERNAME', 'postgres'),
    'password': os.getenv('DB_PASSWORD', 'Osaa123'),
    'host': os.getenv('DB_HOST', 'localhost'),
    'port': os.getenv('DB_PORT', '5432')
}


def get_gps_from_exif(image_path):
    """Extract GPS coordinates from EXIF metadata"""
    try:
        image = Image.open(image_path)
        exif = image._getexif()
        if not exif:
            return None, None
        
        gps_info = {}
        for tag, value in exif.items():
            decoded = TAGS.get(tag, tag)
            if decoded == "GPSInfo":
                for t in value:
                    sub_decoded = GPSTAGS.get(t, t)
                    gps_info[sub_decoded] = value[t]
        
        if "GPSLatitude" in gps_info and "GPSLongitude" in gps_info:
            def convert_to_degrees(value):
                d = float(value[0])
                m = float(value[1])
                s = float(value[2])
                return d + (m / 60.0) + (s / 3600.0)
            
            lat = convert_to_degrees(gps_info["GPSLatitude"])
            if gps_info.get("GPSLatitudeRef") == "S":
                lat = -lat
            
            lng = convert_to_degrees(gps_info["GPSLongitude"])
            if gps_info.get("GPSLongitudeRef") == "W":
                lng = -lng
            
            # Validate for Cirebon area
            if -8.5 <= lat <= -5.5 and 106 <= lng <= 110:
                return lat, lng
    except Exception:
        pass
    return None, None


def get_coords_from_ocr(image_path):
    """Extract coordinates from watermark text using OCR"""
    if not OCR_AVAILABLE:
        return None, None
    
    try:
        image = Image.open(image_path)
        width, height = image.size
        
        # Crop bottom 30% where watermark usually is
        bottom = image.crop((0, int(height * 0.7), width, height))
        
        # Use OCR
        text = pytesseract.image_to_string(bottom)
        
        # Pattern: -lat, lng
        pattern = r'(-?\d+\.\d+)[,\s]+(\d+\.\d+)'
        match = re.search(pattern, text)
        if match:
            lat, lng = float(match.group(1)), float(match.group(2))
            if -8.5 <= lat <= -5.5 and 106 <= lng <= 110:
                return lat, lng
    except Exception:
        pass
    return None, None


def extract_coords_from_filename(filename):
    """Extract coordinates from filename"""
    pattern = r'(-?\d+\.\d+)[,\s_]+(\d+\.\d+)'
    match = re.search(pattern, filename)
    if match:
        lat, lng = float(match.group(1)), float(match.group(2))
        if -8.5 <= lat <= -5.5 and 106 <= lng <= 110:
            return lat, lng
    return None, None


def process_photos(folder, limit=0):
    """Process all photos in folder and extract coordinates"""
    results = {
        'exif': [],
        'ocr': [],
        'filename': [],
        'no_coords': []
    }
    
    image_extensions = {'.jpg', '.jpeg', '.png', '.tiff', '.bmp'}
    
    folder_path = Path(folder)
    if not folder_path.exists():
        print(f"❌ Folder tidak ditemukan: {folder}")
        return results
    
    images = [f for f in folder_path.iterdir() if f.suffix.lower() in image_extensions]
    
    if limit > 0:
        images = images[:limit]
    
    print(f"\n🔍 Memproses {len(images)} foto...")
    print("-" * 50)
    
    for i, img_path in enumerate(images):
        if (i + 1) % 10 == 0:
            print(f"   Progress: {i+1}/{len(images)}")
        
        lat, lng = None, None
        source = None
        
        # Method 1: EXIF
        lat, lng = get_gps_from_exif(str(img_path))
        if lat:
            source = 'EXIF'
        
        # Method 2: OCR
        if not lat:
            lat, lng = get_coords_from_ocr(str(img_path))
            if lat:
                source = 'OCR'
        
        # Method 3: Filename
        if not lat:
            lat, lng = extract_coords_from_filename(img_path.name)
            if lat:
                source = 'FILENAME'
        
        if lat and lng:
            results[source.lower()].append({
                'filename': img_path.name,
                'lat': lat,
                'lng': lng
            })
        else:
            results['no_coords'].append(img_path.name)
    
    return results


def main():
    import argparse
    parser = argparse.ArgumentParser(description='Process local photos for coordinate extraction')
    parser.add_argument('--folder', '-f', default=PHOTO_FOLDER, help='Folder containing photos')
    parser.add_argument('--limit', '-l', type=int, default=0, help='Limit number of photos to process')
    args = parser.parse_args()
    
    print("=" * 60)
    print("📷 Local Photo Coordinate Extractor")
    print("=" * 60)
    print(f"📁 Folder: {args.folder}")
    
    if not OCR_AVAILABLE:
        print("⚠️ OCR tidak tersedia. Install Tesseract untuk membaca watermark.")
    
    # Process photos
    results = process_photos(args.folder, args.limit)
    
    # Summary
    total_found = len(results['exif']) + len(results['ocr']) + len(results['filename'])
    
    print("\n" + "=" * 60)
    print("📊 HASIL EKSTRAKSI KOORDINAT")
    print("=" * 60)
    print(f"   📍 EXIF GPS: {len(results['exif'])} foto")
    print(f"   📝 OCR Watermark: {len(results['ocr'])} foto")
    print(f"   📄 Filename: {len(results['filename'])} foto")
    print(f"   ❌ Tidak ada koordinat: {len(results['no_coords'])} foto")
    print(f"\n   ✅ Total berhasil: {total_found} foto")
    
    # Show samples
    if results['exif']:
        print("\n📍 Sample EXIF:")
        for item in results['exif'][:3]:
            print(f"      {item['filename']}: {item['lat']}, {item['lng']}")
    
    if results['ocr']:
        print("\n📝 Sample OCR:")
        for item in results['ocr'][:3]:
            print(f"      {item['filename']}: {item['lat']}, {item['lng']}")
    
    # Save results to CSV
    output_file = os.path.join(args.folder, 'koordinat_hasil.csv')
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write("filename,latitude,longitude,source\n")
        for source, items in [('EXIF', results['exif']), ('OCR', results['ocr']), ('FILENAME', results['filename'])]:
            for item in items:
                f.write(f"{item['filename']},{item['lat']},{item['lng']},{source}\n")
    
    print(f"\n📄 Hasil disimpan ke: {output_file}")


if __name__ == "__main__":
    main()
