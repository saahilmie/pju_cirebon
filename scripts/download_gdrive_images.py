#!/usr/bin/env python3
"""
Google Drive Bulk Image Downloader for PJU Dishub Data

Downloads images from Google Drive links found in CSV files.
Reports failed downloads to a separate file.

Usage:
    python download_gdrive_images.py

Output:
    - Images saved to D:\KP\gambar_dishub
    - Failed downloads logged to failed_downloads.csv
"""

import os
import re
import csv
import sys
from pathlib import Path
from datetime import datetime

try:
    import gdown
except ImportError:
    print("Please install gdown: pip install gdown")
    sys.exit(1)

# Configuration
CSV_FILES = [
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (1).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (2).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (3).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (4).csv",
]

OUTPUT_DIR = r"D:\KP\gambar_dishub"
FAILED_LOG = r"D:\KP\pju-cirebon\failed_downloads.csv"


def extract_file_id(url):
    """Extract Google Drive file ID from URL"""
    if not url or not isinstance(url, str):
        return None
    
    # Pattern: /d/{file_id}/ or id={file_id}
    patterns = [
        r'/d/([a-zA-Z0-9_-]+)',
        r'id=([a-zA-Z0-9_-]+)',
        r'/file/d/([a-zA-Z0-9_-]+)',
    ]
    
    for pattern in patterns:
        match = re.search(pattern, url)
        if match:
            return match.group(1)
    
    return None


def find_gdrive_links(csv_file):
    """Find all Google Drive links in a CSV file"""
    links = []
    
    if not os.path.exists(csv_file):
        print(f"❌ File not found: {csv_file}")
        return links
    
    try:
        with open(csv_file, 'r', encoding='utf-8-sig', errors='ignore') as f:
            # Read first line to detect delimiter
            first_line = f.readline()
            f.seek(0)
            delimiter = ';' if ';' in first_line else ','
            
            reader = csv.DictReader(f, delimiter=delimiter)
            
            for row_num, row in enumerate(reader, start=2):
                # Find all columns that might contain Google Drive links
                for col_name, value in row.items():
                    if value and 'drive.google.com' in str(value):
                        file_id = extract_file_id(value)
                        if file_id:
                            links.append({
                                'file_id': file_id,
                                'url': value,
                                'source_file': csv_file,
                                'row': row_num,
                                'column': col_name,
                                'row_no': row.get('No', row.get('NO WYP', str(row_num))),
                            })
    
    except Exception as e:
        print(f"❌ Error reading {csv_file}: {e}")
    
    return links


def download_image(file_id, output_path, quiet=True):
    """Download image from Google Drive"""
    url = f"https://drive.google.com/uc?id={file_id}"
    
    try:
        result = gdown.download(url, output_path, quiet=quiet, fuzzy=True)
        if result and os.path.exists(output_path):
            return True, None
        else:
            return False, "Download returned None or file not created"
    except Exception as e:
        return False, str(e)


def main():
    import argparse
    
    parser = argparse.ArgumentParser(description='Download images from Google Drive')
    parser.add_argument('--limit', '-l', type=int, default=0,
                       help='Limit number of downloads (0 = unlimited)')
    args = parser.parse_args()
    
    print("=" * 60)
    print("🚀 Google Drive Bulk Image Downloader")
    print("=" * 60)
    
    if args.limit > 0:
        print(f"⚠️ Limited to {args.limit} downloads")
    
    # Create output directory
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    # Collect all links from all CSV files
    print("\n📂 Scanning CSV files for Google Drive links...")
    all_links = []
    
    for csv_file in CSV_FILES:
        print(f"   📄 {os.path.basename(csv_file)}", end="")
        links = find_gdrive_links(csv_file)
        all_links.extend(links)
        print(f" → {len(links)} links found")
    
    # Remove duplicates by file_id
    unique_links = {}
    for link in all_links:
        if link['file_id'] not in unique_links:
            unique_links[link['file_id']] = link
    
    print(f"\n📊 Total: {len(all_links)} links, {len(unique_links)} unique")
    
    if not unique_links:
        print("❌ No Google Drive links found!")
        return
    
    # Download images
    print(f"\n⬇️ Downloading to: {OUTPUT_DIR}")
    print("-" * 60)
    
    downloaded = 0
    skipped = 0
    failed = []
    
    for i, (file_id, link) in enumerate(unique_links.items(), 1):
        # Create filename: {row_no}_{file_id}.jpg
        row_no = str(link['row_no']).replace('/', '_').replace('\\', '_')
        filename = f"{row_no}_{file_id[:10]}.jpg"
        output_path = os.path.join(OUTPUT_DIR, filename)
        
        # Skip if already downloaded
        if os.path.exists(output_path):
            skipped += 1
            if i % 100 == 0 or i == len(unique_links):
                print(f"   Progress: {i}/{len(unique_links)} (Downloaded: {downloaded}, Skipped: {skipped}, Failed: {len(failed)})")
            continue
        
        # Download
        success, error = download_image(file_id, output_path)
        
        if success:
            downloaded += 1
        else:
            failed.append({
                'file_id': file_id,
                'url': link['url'],
                'source_file': os.path.basename(link['source_file']),
                'row': link['row'],
                'column': link['column'],
                'error': error or 'Unknown error',
            })
        
        # Progress update every 10 files or at the end
        if i % 10 == 0 or i == len(unique_links) or (args.limit > 0 and downloaded >= args.limit):
            print(f"   Progress: {i}/{len(unique_links)} (Downloaded: {downloaded}, Skipped: {skipped}, Failed: {len(failed)})")
        
        # Check limit
        if args.limit > 0 and downloaded >= args.limit:
            print(f"\n⏹️ Reached download limit of {args.limit}")
            break
    
    # Save failed downloads
    if failed:
        with open(FAILED_LOG, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=['file_id', 'url', 'source_file', 'row', 'column', 'error'])
            writer.writeheader()
            writer.writerows(failed)
        print(f"\n⚠️ Failed downloads logged to: {FAILED_LOG}")
    
    # Summary
    print("\n" + "=" * 60)
    print("📊 DOWNLOAD SUMMARY")
    print("=" * 60)
    print(f"   ✅ Downloaded: {downloaded}")
    print(f"   ⏭️ Skipped (already exists): {skipped}")
    print(f"   ❌ Failed: {len(failed)}")
    print(f"\n   📁 Images saved to: {OUTPUT_DIR}")
    
    if failed:
        print(f"   📄 Failed log: {FAILED_LOG}")
        print(f"\n   Sample failed links:")
        for f_link in failed[:5]:
            print(f"      - Row {f_link['row']} in {f_link['source_file']}: {f_link['error']}")


if __name__ == '__main__':
    main()
