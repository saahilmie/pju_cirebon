#!/usr/bin/env python3
"""
PJU Image Coordinate Matcher (Enhanced with OCR)

This script extracts GPS coordinates from:
1. Image EXIF metadata (preferred, most accurate)
2. Watermark text using OCR (fallback)

Then matches them to the nearest PJU data point in the database.

Usage:
    python match_image_coordinates.py <image_folder> --db-export <csv_file>
    python match_image_coordinates.py --check-exif <image_folder>  # Check which images have EXIF GPS

Requirements:
    pip install Pillow pytesseract

    For OCR, also install Tesseract:
    - Windows: Download from https://github.com/UB-Mannheim/tesseract/wiki
    - Linux: sudo apt install tesseract-ocr
    - Mac: brew install tesseract

Author: Auto-generated for PJU Cirebon project
"""

import os
import sys
import csv
import re
import json
from pathlib import Path
from datetime import datetime
from math import radians, sin, cos, sqrt, atan2

try:
    from PIL import Image
    from PIL.ExifTags import TAGS, GPSTAGS
except ImportError:
    print("Please install Pillow: pip install Pillow")
    sys.exit(1)

# Try to import pytesseract for OCR
try:
    import pytesseract
    OCR_AVAILABLE = True
except ImportError:
    OCR_AVAILABLE = False
    print("Warning: pytesseract not installed. OCR watermark reading disabled.")
    print("Install with: pip install pytesseract")


def get_exif_data(image_path):
    """Extract EXIF data from image"""
    try:
        image = Image.open(image_path)
        exif_data = {}
        
        if hasattr(image, '_getexif') and image._getexif():
            for tag_id, value in image._getexif().items():
                tag = TAGS.get(tag_id, tag_id)
                exif_data[tag] = value
        
        return exif_data
    except Exception as e:
        return {}


def has_gps_exif(image_path):
    """Check if image has GPS data in EXIF"""
    exif = get_exif_data(image_path)
    if 'GPSInfo' in exif:
        gps_info = exif['GPSInfo']
        # Check if it actually has latitude and longitude
        has_lat = any(GPSTAGS.get(k) == 'GPSLatitude' for k in gps_info.keys())
        has_lon = any(GPSTAGS.get(k) == 'GPSLongitude' for k in gps_info.keys())
        return has_lat and has_lon
    return False


def get_gps_from_exif(exif_data):
    """Extract GPS coordinates from EXIF data"""
    if 'GPSInfo' not in exif_data:
        return None
    
    gps_info = exif_data['GPSInfo']
    gps_data = {}
    
    for key in gps_info.keys():
        decode = GPSTAGS.get(key, key)
        gps_data[decode] = gps_info[key]
    
    if 'GPSLatitude' not in gps_data or 'GPSLongitude' not in gps_data:
        return None
    
    try:
        lat = gps_data['GPSLatitude']
        lat_ref = gps_data.get('GPSLatitudeRef', 'N')
        lon = gps_data['GPSLongitude']
        lon_ref = gps_data.get('GPSLongitudeRef', 'E')
        
        # Convert to decimal degrees
        lat_deg = float(lat[0]) + float(lat[1])/60 + float(lat[2])/3600
        lon_deg = float(lon[0]) + float(lon[1])/60 + float(lon[2])/3600
        
        if lat_ref == 'S':
            lat_deg = -lat_deg
        if lon_ref == 'W':
            lon_deg = -lon_deg
        
        return {'latitude': lat_deg, 'longitude': lon_deg, 'source': 'EXIF'}
    except Exception as e:
        return None


def extract_coords_from_watermark(image_path):
    """
    Extract coordinates from watermark text using OCR.
    
    Handles various formats:
    - -6.75861, 108.48389
    - -6.7545, 108.51323
    - Coordinates with addresses mixed in
    - Double/overlapping watermarks (picks the most valid one)
    """
    if not OCR_AVAILABLE:
        return None
    
    try:
        image = Image.open(image_path)
        
        # Convert to RGB if necessary
        if image.mode != 'RGB':
            image = image.convert('RGB')
        
        # Get image dimensions
        width, height = image.size
        
        # Crop bottom portion where watermark usually is (bottom 25%)
        crop_height = int(height * 0.25)
        bottom_crop = image.crop((0, height - crop_height, width, height))
        
        # Also try full image in case watermark is elsewhere
        crops_to_try = [bottom_crop, image]
        
        all_coords = []
        
        for crop in crops_to_try:
            # Use OCR to extract text
            try:
                text = pytesseract.image_to_string(crop)
            except Exception:
                continue
            
            # Find all coordinate patterns
            # Pattern: negative decimal (latitude in Indonesia), positive decimal (longitude)
            # Format: -6.75861, 108.48389 or similar
            
            # Pattern 1: Standard format with comma
            pattern1 = r'(-?\d+\.\d+)\s*[,;]\s*(\d+\.\d+)'
            matches1 = re.findall(pattern1, text)
            
            # Pattern 2: Coordinates on separate lines or with other separators
            pattern2 = r'(-\d+\.\d+)'  # Latitude (negative for Indonesia)
            pattern3 = r'(\d{3}\.\d+)'  # Longitude (starts with 10x for Indonesia)
            
            lats = re.findall(pattern2, text)
            lons = re.findall(pattern3, text)
            
            # Collect all potential coordinate pairs
            for lat, lon in matches1:
                all_coords.append((float(lat), float(lon)))
            
            # Try to pair unpaired lat/lon
            for lat in lats:
                for lon in lons:
                    coord = (float(lat), float(lon))
                    if coord not in all_coords:
                        all_coords.append(coord)
        
        # Filter valid coordinates for Cirebon/West Java area
        # Latitude: -8 to -5, Longitude: 106 to 110
        valid_coords = []
        for lat, lon in all_coords:
            if -8.5 <= lat <= -5.5 and 106 <= lon <= 110:
                valid_coords.append((lat, lon))
        
        if not valid_coords:
            return None
        
        # If multiple valid coords found (double watermark), pick the first one
        # They should be similar anyway if it's duplicate watermark
        lat, lon = valid_coords[0]
        
        # If we have multiple different coords, note it
        source = 'OCR'
        if len(valid_coords) > 1:
            # Check if they're actually different
            unique_coords = list(set(valid_coords))
            if len(unique_coords) > 1:
                source = f'OCR (found {len(unique_coords)} coords, using first)'
        
        return {
            'latitude': lat,
            'longitude': lon,
            'source': source
        }
    
    except Exception as e:
        return None


def haversine_distance(lat1, lon1, lat2, lon2):
    """Calculate the great circle distance between two points in meters"""
    R = 6371000  # Earth's radius in meters
    
    lat1, lon1, lat2, lon2 = map(radians, [lat1, lon1, lat2, lon2])
    
    dlat = lat2 - lat1
    dlon = lon2 - lon1
    
    a = sin(dlat/2)**2 + cos(lat1) * cos(lat2) * sin(dlon/2)**2
    c = 2 * atan2(sqrt(a), sqrt(1-a))
    
    return R * c


def load_database(csv_file=None):
    """Load PJU data from CSV export"""
    data = []
    
    if csv_file and os.path.exists(csv_file):
        with open(csv_file, 'r', encoding='utf-8-sig') as f:
            reader = csv.DictReader(f)
            for row in reader:
                try:
                    lat = float(row.get('koordinat_x', 0) or 0)
                    lon = float(row.get('koordinat_y', 0) or 0)
                    
                    # Valid coordinate range for Cirebon area
                    if -8.5 <= lat <= -5.5 and 106 <= lon <= 110:
                        data.append({
                            'id': row.get('id', ''),
                            'idpel': row.get('idpel', ''),
                            'nama': row.get('nama', ''),
                            'koordinat_x': lat,
                            'koordinat_y': lon,
                            'nama_kabupaten': row.get('nama_kabupaten', ''),
                            'nama_kecamatan': row.get('nama_kecamatan', ''),
                            'nama_kelurahan': row.get('nama_kelurahan', ''),
                        })
                except (ValueError, TypeError):
                    continue
    
    return data


def find_nearest_match(lat, lon, database, max_distance=100):
    """
    Find the nearest database entry to the given coordinates.
    max_distance is in meters.
    """
    if not database:
        return None, None
    
    nearest = None
    min_distance = float('inf')
    
    for entry in database:
        db_lat = entry['koordinat_x']
        db_lon = entry['koordinat_y']
        
        distance = haversine_distance(lat, lon, db_lat, db_lon)
        
        if distance < min_distance:
            min_distance = distance
            nearest = entry
    
    if min_distance <= max_distance:
        return nearest, min_distance
    
    return None, min_distance


def check_exif_in_folder(folder_path):
    """Check which images have EXIF GPS data"""
    image_extensions = {'.jpg', '.jpeg', '.png', '.tiff', '.bmp'}
    
    folder = Path(folder_path)
    if not folder.exists():
        print(f"Error: Folder {folder_path} does not exist")
        return
    
    images = [f for f in folder.iterdir() if f.suffix.lower() in image_extensions]
    
    print(f"\n📷 Checking {len(images)} images for EXIF GPS data...")
    print("=" * 60)
    
    with_gps = []
    without_gps = []
    
    for img_path in images:
        if has_gps_exif(str(img_path)):
            with_gps.append(img_path.name)
        else:
            without_gps.append(img_path.name)
    
    print(f"\n✅ IMAGES WITH GPS EXIF: {len(with_gps)}")
    print("-" * 40)
    for name in with_gps[:10]:
        print(f"  📍 {name}")
    if len(with_gps) > 10:
        print(f"  ... and {len(with_gps) - 10} more")
    
    print(f"\n❌ IMAGES WITHOUT GPS EXIF (will try OCR): {len(without_gps)}")
    print("-" * 40)
    for name in without_gps[:10]:
        print(f"  📷 {name}")
    if len(without_gps) > 10:
        print(f"  ... and {len(without_gps) - 10} more")
    
    print("\n" + "=" * 60)
    print(f"Summary: {len(with_gps)}/{len(images)} images have GPS EXIF data")
    if without_gps:
        print(f"For the {len(without_gps)} images without EXIF, OCR will be attempted.")
    
    return with_gps, without_gps


def process_images(folder_path, database, max_distance=100):
    """Process all images in folder and match to database"""
    results = {
        'matched': [],
        'no_coords': [],
        'no_match': [],
        'errors': []
    }
    
    image_extensions = {'.jpg', '.jpeg', '.png', '.tiff', '.bmp'}
    
    folder = Path(folder_path)
    if not folder.exists():
        print(f"Error: Folder {folder_path} does not exist")
        return results
    
    images = [f for f in folder.iterdir() if f.suffix.lower() in image_extensions]
    
    print(f"\n🔍 Processing {len(images)} images...")
    print("-" * 60)
    
    for i, img_path in enumerate(images):
        if (i + 1) % 10 == 0:
            print(f"  Progress: {i + 1}/{len(images)}")
        
        try:
            coords = None
            
            # Method 1: Try EXIF GPS (most reliable)
            exif = get_exif_data(str(img_path))
            coords = get_gps_from_exif(exif)
            
            # Method 2: Try OCR on watermark
            if not coords:
                coords = extract_coords_from_watermark(str(img_path))
            
            if not coords:
                results['no_coords'].append({
                    'image': img_path.name,
                    'reason': 'No coordinates found in EXIF or watermark'
                })
                continue
            
            lat, lon = coords['latitude'], coords['longitude']
            source = coords.get('source', 'unknown')
            
            # Find nearest match
            match, distance = find_nearest_match(lat, lon, database, max_distance)
            
            if match:
                results['matched'].append({
                    'image': img_path.name,
                    'image_coords': f"{lat:.6f}, {lon:.6f}",
                    'coord_source': source,
                    'distance_m': round(distance, 2),
                    'matched_idpel': match['idpel'],
                    'matched_nama': match['nama'],
                    'matched_coords': f"{match['koordinat_x']:.6f}, {match['koordinat_y']:.6f}",
                    'matched_kabupaten': match['nama_kabupaten']
                })
            else:
                results['no_match'].append({
                    'image': img_path.name,
                    'image_coords': f"{lat:.6f}, {lon:.6f}",
                    'coord_source': source,
                    'nearest_distance_m': round(distance, 2),
                    'reason': f'Nearest point is {distance:.0f}m away (max: {max_distance}m)'
                })
        
        except Exception as e:
            results['errors'].append({
                'image': img_path.name,
                'error': str(e)
            })
    
    return results


def print_report(results):
    """Print summary report"""
    print("\n" + "=" * 60)
    print("📊 HASIL ANALISIS KOORDINAT GAMBAR PJU")
    print("=" * 60)
    
    print(f"\n✅ BERHASIL DICOCOKKAN: {len(results['matched'])} gambar")
    print("-" * 40)
    for item in results['matched'][:10]:
        print(f"  📷 {item['image']}")
        print(f"     Koordinat: {item['image_coords']} ({item['coord_source']})")
        print(f"     Match: {item['matched_idpel']} ({item['matched_nama'] or '-'})")
        print(f"     Jarak: {item['distance_m']}m")
        print()
    if len(results['matched']) > 10:
        print(f"  ... dan {len(results['matched']) - 10} gambar lainnya")
    
    print(f"\n⚠️ TIDAK ADA KOORDINAT: {len(results['no_coords'])} gambar")
    print("-" * 40)
    for item in results['no_coords'][:5]:
        print(f"  📷 {item['image']}: {item['reason']}")
    if len(results['no_coords']) > 5:
        print(f"  ... dan {len(results['no_coords']) - 5} gambar lainnya")
    
    print(f"\n❌ TIDAK ADA DATA TERDEKAT: {len(results['no_match'])} gambar")
    print("-" * 40)
    for item in results['no_match'][:5]:
        print(f"  📷 {item['image']}")
        print(f"     Koordinat: {item['image_coords']} ({item['coord_source']})")
        print(f"     {item['reason']}")
    if len(results['no_match']) > 5:
        print(f"  ... dan {len(results['no_match']) - 5} gambar lainnya")
    
    if results['errors']:
        print(f"\n🔴 ERROR: {len(results['errors'])} gambar")
        for item in results['errors'][:3]:
            print(f"  📷 {item['image']}: {item['error']}")
    
    print("\n" + "=" * 60)


def save_report(results, output_file):
    """Save detailed report to CSV"""
    with open(output_file, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['Image', 'Status', 'Coord_Source', 'Image_Coords', 
                        'Matched_IDPEL', 'Matched_Nama', 'Matched_Coords', 
                        'Distance_M', 'Kabupaten', 'Notes'])
        
        for item in results['matched']:
            writer.writerow([
                item['image'], 'MATCHED', item['coord_source'],
                item['image_coords'], item['matched_idpel'], 
                item['matched_nama'], item['matched_coords'], 
                item['distance_m'], item['matched_kabupaten'], ''
            ])
        
        for item in results['no_coords']:
            writer.writerow([
                item['image'], 'NO_COORDS', '', '', '', '', '', '', '',
                item['reason']
            ])
        
        for item in results['no_match']:
            writer.writerow([
                item['image'], 'NO_MATCH', item['coord_source'],
                item['image_coords'], '', '', '', 
                item['nearest_distance_m'], '', item['reason']
            ])
        
        for item in results['errors']:
            writer.writerow([
                item['image'], 'ERROR', '', '', '', '', '', '', '',
                item['error']
            ])
    
    print(f"\n📄 Laporan lengkap disimpan ke: {output_file}")


def main():
    import argparse
    
    parser = argparse.ArgumentParser(
        description='Match PJU images to nearest database coordinates'
    )
    parser.add_argument('folder', help='Folder containing images')
    parser.add_argument('--db-export', '-d',
                       help='CSV export from PJU database')
    parser.add_argument('--max-distance', '-m', type=int, default=100,
                       help='Maximum distance in meters for matching (default: 100)')
    parser.add_argument('--output', '-o', 
                       help='Output CSV file for detailed report')
    parser.add_argument('--check-exif', action='store_true',
                       help='Just check which images have EXIF GPS data')
    
    args = parser.parse_args()
    
    # Check EXIF mode
    if args.check_exif:
        check_exif_in_folder(args.folder)
        return
    
    # Normal processing mode requires database
    if not args.db_export:
        print("Error: --db-export is required for matching mode")
        print("Use --check-exif to just check for EXIF GPS data")
        sys.exit(1)
    
    # Load database
    print(f"📂 Loading database from: {args.db_export}")
    database = load_database(args.db_export)
    print(f"   Loaded {len(database)} valid coordinate entries")
    
    if not database:
        print("Error: No valid coordinates found in database export")
        sys.exit(1)
    
    # Process images
    results = process_images(args.folder, database, args.max_distance)
    
    # Print report
    print_report(results)
    
    # Save detailed report
    if args.output:
        save_report(results, args.output)
    else:
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        output_file = f'pju_image_match_report_{timestamp}.csv'
        save_report(results, output_file)


if __name__ == '__main__':
    main()
