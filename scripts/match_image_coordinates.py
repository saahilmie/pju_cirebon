#!/usr/bin/env python3
"""
PJU Image Coordinate Matcher

This script extracts GPS coordinates from image EXIF data or watermarks,
then matches them to the nearest PJU data point in the database.

Usage:
    python match_image_coordinates.py <image_folder> [--db-export <csv_file>]

Requirements:
    pip install Pillow exifread geopy

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
    
    return {'latitude': lat_deg, 'longitude': lon_deg}


def extract_coords_from_watermark(image_path):
    """
    Try to extract coordinates from watermark text in image.
    Pattern expected: -6.75861, 108.48389
    """
    # This would require OCR - for now we'll skip this
    # Could use pytesseract if needed
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
    """Load PJU data from CSV export or database"""
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
    
    print(f"\nProcessing {len(images)} images...")
    print("-" * 60)
    
    for img_path in images:
        try:
            # Try to get GPS from EXIF
            exif = get_exif_data(str(img_path))
            coords = get_gps_from_exif(exif)
            
            # If no EXIF GPS, try watermark (placeholder)
            if not coords:
                coords = extract_coords_from_watermark(str(img_path))
            
            if not coords:
                results['no_coords'].append({
                    'image': img_path.name,
                    'reason': 'No GPS coordinates found in EXIF'
                })
                continue
            
            lat, lon = coords['latitude'], coords['longitude']
            
            # Find nearest match
            match, distance = find_nearest_match(lat, lon, database, max_distance)
            
            if match:
                results['matched'].append({
                    'image': img_path.name,
                    'image_coords': f"{lat:.6f}, {lon:.6f}",
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
    print("HASIL ANALISIS KOORDINAT GAMBAR PJU")
    print("=" * 60)
    
    print(f"\n✅ BERHASIL DICOCOKKAN: {len(results['matched'])} gambar")
    print("-" * 40)
    for item in results['matched'][:10]:  # Show first 10
        print(f"  📷 {item['image']}")
        print(f"     Koordinat: {item['image_coords']}")
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
        print(f"     Koordinat: {item['image_coords']}")
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
        writer.writerow(['Image', 'Status', 'Image_Coords', 'Matched_IDPEL', 
                        'Matched_Nama', 'Matched_Coords', 'Distance_M', 'Notes'])
        
        for item in results['matched']:
            writer.writerow([
                item['image'], 'MATCHED', item['image_coords'],
                item['matched_idpel'], item['matched_nama'],
                item['matched_coords'], item['distance_m'], ''
            ])
        
        for item in results['no_coords']:
            writer.writerow([
                item['image'], 'NO_COORDS', '', '', '', '', '', item['reason']
            ])
        
        for item in results['no_match']:
            writer.writerow([
                item['image'], 'NO_MATCH', item['image_coords'],
                '', '', '', item['nearest_distance_m'], item['reason']
            ])
        
        for item in results['errors']:
            writer.writerow([
                item['image'], 'ERROR', '', '', '', '', '', item['error']
            ])
    
    print(f"\n📄 Laporan lengkap disimpan ke: {output_file}")


def main():
    import argparse
    
    parser = argparse.ArgumentParser(
        description='Match PJU images to nearest database coordinates'
    )
    parser.add_argument('folder', help='Folder containing images')
    parser.add_argument('--db-export', '-d', required=True,
                       help='CSV export from PJU database')
    parser.add_argument('--max-distance', '-m', type=int, default=100,
                       help='Maximum distance in meters for matching (default: 100)')
    parser.add_argument('--output', '-o', 
                       help='Output CSV file for detailed report')
    
    args = parser.parse_args()
    
    # Load database
    print(f"Loading database from: {args.db_export}")
    database = load_database(args.db_export)
    print(f"Loaded {len(database)} valid coordinate entries")
    
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
