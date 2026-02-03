#!/usr/bin/env python3
"""Quick script to count Google Drive links in CSV files"""

import os
import re
import csv

CSV_FILES = [
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (1).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (2).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (3).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (4).csv",
]

def extract_file_id(url):
    if not url or not isinstance(url, str):
        return None
    patterns = [r'/d/([a-zA-Z0-9_-]+)', r'id=([a-zA-Z0-9_-]+)']
    for pattern in patterns:
        match = re.search(pattern, url)
        if match:
            return match.group(1)
    return None

def count_links(csv_file):
    links = set()
    if not os.path.exists(csv_file):
        return 0, f"File not found"
    
    try:
        with open(csv_file, 'r', encoding='utf-8-sig', errors='ignore') as f:
            first_line = f.readline()
            f.seek(0)
            delimiter = ';' if ';' in first_line else ','
            reader = csv.DictReader(f, delimiter=delimiter)
            
            for row in reader:
                for value in row.values():
                    if value and 'drive.google.com' in str(value):
                        file_id = extract_file_id(value)
                        if file_id:
                            links.add(file_id)
        return len(links), None
    except Exception as e:
        return 0, str(e)

print("=" * 50)
print("Counting Google Drive links in CSV files...")
print("=" * 50)

total = 0
all_ids = set()

for csv_file in CSV_FILES:
    count, error = count_links(csv_file)
    name = os.path.basename(csv_file)
    if error:
        print(f"❌ {name}: {error}")
    else:
        print(f"✅ {name}: {count} unique links")
        total += count
        
        # Also collect all for total unique
        with open(csv_file, 'r', encoding='utf-8-sig', errors='ignore') as f:
            for line in f:
                ids = re.findall(r'/d/([a-zA-Z0-9_-]+)', line)
                all_ids.update(ids)

print("-" * 50)
print(f"Total unique links across all files: {len(all_ids)}")
print(f"Estimated download time: {len(all_ids) * 2 // 60} - {len(all_ids) * 5 // 60} minutes")
