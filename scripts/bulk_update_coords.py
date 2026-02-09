import os
import re
import csv
import sys
import subprocess
import time
from pathlib import Path
from datetime import datetime

# Auto-install dependencies if missing
def install_deps():
    try:
        import psycopg2
        from dotenv import load_dotenv
    except ImportError:
        print("📦 Installing required Python dependencies...")
        subprocess.check_call([sys.executable, "-m", "pip", "install", "psycopg2-binary", "python-dotenv", "gdown", "Pillow", "pytesseract"])
        print("✅ Dependencies installed.")

install_deps()
import psycopg2
from dotenv import load_dotenv
import gdown

try:
    import pytesseract
    OCR_AVAILABLE = True
except ImportError:
    OCR_AVAILABLE = False

if OCR_AVAILABLE:
    # Common Tesseract path on Windows
    tess_path = r'C:\Program Files\Tesseract-OCR\tesseract.exe'
    if os.path.exists(tess_path):
        pytesseract.pytesseract.tesseract_cmd = tess_path

# Load database credentials
load_dotenv()

DB_CONFIG = {
    'dbname': os.getenv('DB_DATABASE', 'pju_cirebon'),
    'user': os.getenv('DB_USERNAME', 'postgres'),
    'password': os.getenv('DB_PASSWORD', 'Osaa123'),
    'host': os.getenv('DB_HOST', 'localhost'),
    'port': os.getenv('DB_PORT', '5432')
}

CSV_FILES = [
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (1).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (2).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (3).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (4).csv",
]

TEMP_DIR = r"D:\KP\temp_coords"
os.makedirs(TEMP_DIR, exist_ok=True)

def extract_file_id(url):
    if not url or not isinstance(url, str): return None
    patterns = [r'/d/([a-zA-Z0-9_-]+)', r'id=([a-zA-Z0-9_-]+)', r'/file/d/([a-zA-Z0-9_-]+)']
    for p in patterns:
        m = re.search(p, url)
        if m: return m.group(1)
    return None

try:
    from PIL import Image
    from PIL.ExifTags import TAGS, GPSTAGS
except ImportError:
    pass

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
                
            return lat, lng
    except Exception:
        pass
    return None, None

def get_coords_from_ocr(image_path):
    """Extract coordinates from watermark text using OCR"""
    if not OCR_AVAILABLE:
        return None, None
    
    try:
        from PIL import Image
        image = Image.open(image_path)
        # Crop bottom 25% where watermark usually is
        width, height = image.size
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

def extract_coords_from_name(name):
    """Extract -lat, lng from filename (keeping as fallback)"""
    pattern = r'(-?\d+\.\d+)[,\s]+(\d+\.\d+)'
    m = re.search(pattern, name)
    if m:
        lat, lng = float(m.group(1)), float(m.group(2))
        if -8.5 <= lat <= -5.5 and 106 <= lng <= 110:
            return lat, lng
    return None, None

def process():
    import argparse
    parser = argparse.ArgumentParser(description='Update PJU coordinates from Google Drive photos')
    parser.add_argument('--limit', '-l', type=int, default=0, help='Limit number of records to process (0 = unlimited)')
    args = parser.parse_args()
    
    conn = psycopg2.connect(**DB_CONFIG)
    cur = conn.cursor()
    
    total_checked = 0
    updated = 0
    limit = args.limit
    
    print("🚀 Kejar Tayang: Mencari koordinat GPS di dalam Foto...")
    print("   Script ini akan download foto sementara, baca GPS-nya, lalu update DB.")
    if limit > 0:
        print(f"   ⚠️ LIMIT: Hanya memproses {limit} data pertama.")
    
    for csv_file in CSV_FILES:
        if limit > 0 and total_checked >= limit:
            break
            
        print(f"\n📂 Memproses file: {os.path.basename(csv_file)}...")
        if not os.path.exists(csv_file): continue
        
        with open(csv_file, 'r', encoding='utf-8-sig', errors='ignore') as f:
            first_line = f.readline()
            f.seek(0)
            delimiter = ';' if ';' in first_line else ','
            reader = csv.DictReader(f, delimiter=delimiter)
            
            for row in reader:
                idpel = row.get('IDPEL APP', '').strip()
                link = row.get('LINK GAMBAR', '').strip()
                
                # Skip if no link
                if not link or 'drive.google.com' not in link:
                    continue
                
                file_id = extract_file_id(link)
                if not file_id: continue
                
                # Use IDPEL if available, otherwise use row identifier
                row_no = row.get('NO WYP', row.get('No', str(total_checked)))
                identifier = idpel if idpel and re.match(r'^\d{9,15}$', idpel) else f"Row_{row_no}"
                
                # Check if this IDPEL already has coordinates in DB (only for valid IDPELs)
                if idpel and re.match(r'^\d{9,15}$', idpel):
                    cur.execute("SELECT koordinat_x FROM pju_data WHERE idpel = %s AND koordinat_x IS NOT NULL LIMIT 1", (idpel,))
                    if cur.fetchone():
                        continue

                print(f"   🔍 [{total_checked+1}] Checking: {identifier}...")
                
                # Add delay to avoid rate limiting
                time.sleep(2)
                
                try:
                    # Download to temp to get the original filename
                    url = f"https://drive.google.com/uc?id={file_id}"
                    # gdown will download and return the filename
                    local_file = gdown.download(url, quiet=True, fuzzy=True)
                    
                    if local_file and os.path.exists(local_file):
                        # Method 1: Cek Metadata EXIF (GPS Internal)
                        lat, lng = get_gps_from_exif(local_file)
                        
                        # Method 2: Cek OCR Watermark (Teks di dalam gambar)
                        if not lat:
                            lat, lng = get_coords_from_ocr(local_file)
                        
                        # Method 3: Fallback ke Nama File
                        if not lat:
                            lat, lng = extract_coords_from_name(local_file)
                        
                        if lat and lng:
                            print(f"      📍 Berhasil! Ketemu koordinat: {lat}, {lng}")
                            cur.execute(
                                "UPDATE pju_data SET koordinat_x = %s, koordinat_y = %s WHERE idpel = %s AND koordinat_x IS NULL",
                                (lat, lng, idpel)
                            )
                            updated += cur.rowcount
                            conn.commit()
                        else:
                            print(f"      ❌ Koordinat tidak ditemukan di dalam foto ini.")
                        
                        # Cleanup
                        os.remove(local_file)
                except Exception as e:
                    print(f"      ❌ Error: {e}")
                    
                total_checked += 1
                if total_checked % 10 == 0:
                    print(f"Progress: {total_checked} dicek, {updated} koordinat terupdate.")
                
                if limit > 0 and total_checked >= limit:
                    print(f"\n⏹️ Limit tercapai ({limit} data).")
                    break

    cur.close()
    conn.close()
    print(f"\n✅ Finished! Updated {updated} records with coordinates.")

if __name__ == "__main__":
    process()
