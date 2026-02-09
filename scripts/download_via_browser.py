#!/usr/bin/env python3
"""
Google Drive Bulk Downloader via Browser Automation

Downloads images from Google Drive links by automating Chrome browser.
Uses your existing Chrome login session to bypass permission issues.

Usage:
    python download_via_browser.py --limit 100
    python download_via_browser.py  (downloads all)

Requirements:
    pip install selenium webdriver-manager
"""

import os
import re
import csv
import sys
import time
import subprocess
from pathlib import Path

# Auto-install dependencies
def install_deps():
    try:
        import undetected_chromedriver
    except ImportError:
        print("📦 Installing Undetected ChromeDriver...")
        subprocess.check_call([sys.executable, "-m", "pip", "install", "undetected-chromedriver"])
        print("✅ Dependencies installed.")

install_deps()

import undetected_chromedriver as uc
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# Configuration
CSV_FILES = [
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (1).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (2).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (3).csv",
    r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (4).csv",
]

# Download folder - Chrome will save files here
DOWNLOAD_DIR = r"D:\KP\foto_dishub_download"

# Chrome user data directory (to use existing login session)
# Common path: C:\Users\<USERNAME>\AppData\Local\Google\Chrome\User Data
CHROME_USER_DATA = os.path.expandvars(r"%LOCALAPPDATA%\Google\Chrome\User Data")


def extract_file_id(url):
    """Extract Google Drive file ID from URL"""
    if not url or not isinstance(url, str):
        return None
    patterns = [
        r'/d/([a-zA-Z0-9_-]+)',
        r'id=([a-zA-Z0-9_-]+)',
        r'/file/d/([a-zA-Z0-9_-]+)',
    ]
    for p in patterns:
        m = re.search(p, url)
        if m:
            return m.group(1)
    return None


def collect_links(csv_files, limit=0):
    """Collect all Google Drive links from CSV files"""
    links = []
    for csv_file in csv_files:
        if not os.path.exists(csv_file):
            print(f"⚠️ File tidak ditemukan: {csv_file}")
            continue
        
        try:
            with open(csv_file, 'r', encoding='utf-8-sig', errors='ignore') as f:
                first_line = f.readline()
                f.seek(0)
                delimiter = ';' if ';' in first_line else ','
                reader = csv.DictReader(f, delimiter=delimiter)
                
                for row in reader:
                    link = row.get('LINK GAMBAR', '').strip()
                    if link and 'drive.google.com' in link:
                        file_id = extract_file_id(link)
                        if file_id:
                            row_no = row.get('NO WYP', row.get('No', str(len(links)+1)))
                            links.append({
                                'file_id': file_id,
                                'url': link,
                                'row_no': row_no,
                                'source': os.path.basename(csv_file)
                            })
                    
                    if limit > 0 and len(links) >= limit:
                        return links
        except Exception as e:
            print(f"❌ Error reading {csv_file}: {e}")
    
    return links


def setup_chrome_driver():
    """Setup Chrome driver with undetected-chromedriver"""
    options = uc.ChromeOptions()
    
    # Use a fresh temporary profile
    temp_profile = os.path.join(os.environ.get('TEMP', 'C:\\Temp'), 'chrome_uc_profile')
    os.makedirs(temp_profile, exist_ok=True)
    options.add_argument(f"--user-data-dir={temp_profile}")
    
    # Set download directory via prefs
    prefs = {
        "download.default_directory": DOWNLOAD_DIR,
        "download.prompt_for_download": False,
        "download.directory_upgrade": True,
        "safebrowsing.enabled": True
    }
    options.add_experimental_option("prefs", prefs)
    
    # Disable some notifications
    options.add_argument("--disable-notifications")
    options.add_argument("--disable-popup-blocking")
    
    # Create undetected Chrome driver
    driver = uc.Chrome(options=options)
    
    return driver


def download_file(driver, file_id, row_no, wait_time=10):
    """Download a single file from Google Drive"""
    # Direct download URL
    download_url = f"https://drive.google.com/uc?export=download&id={file_id}"
    
    try:
        driver.get(download_url)
        time.sleep(2)  # Wait for page to load
        
        # Check for "Download anyway" button for large files
        try:
            download_btn = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Download anyway')]"))
            )
            download_btn.click()
            time.sleep(2)
        except:
            pass  # No button needed, download should start automatically
        
        # Check for virus scan warning button
        try:
            virus_btn = WebDriverWait(driver, 3).until(
                EC.element_to_be_clickable((By.ID, "uc-download-link"))
            )
            virus_btn.click()
            time.sleep(2)
        except:
            pass
        
        # Wait for download to complete
        time.sleep(wait_time)
        return True
        
    except Exception as e:
        print(f"      ❌ Error: {str(e)[:50]}")
        return False


def main():
    import argparse
    parser = argparse.ArgumentParser(description='Download Google Drive files via browser automation')
    parser.add_argument('--limit', '-l', type=int, default=0, help='Limit number of downloads (0 = unlimited)')
    parser.add_argument('--start', '-s', type=int, default=0, help='Start from this index (for resuming)')
    args = parser.parse_args()
    
    print("=" * 60)
    print("🚀 Google Drive Browser Downloader")
    print("=" * 60)
    
    # Create download directory
    os.makedirs(DOWNLOAD_DIR, exist_ok=True)
    print(f"📁 Download folder: {DOWNLOAD_DIR}")
    
    # Collect links
    print("\n📂 Mengumpulkan link dari CSV...")
    links = collect_links(CSV_FILES, limit=args.limit if args.limit > 0 else 0)
    print(f"   Ditemukan {len(links)} link Google Drive")
    
    if not links:
        print("❌ Tidak ada link ditemukan!")
        return
    
    # Apply start offset
    if args.start > 0:
        links = links[args.start:]
        print(f"   Mulai dari index {args.start}")
    
    # Apply limit
    if args.limit > 0:
        links = links[:args.limit]
        print(f"   Limit: {args.limit} file")
    
    print(f"\n⚠️ PENTING: Tutup Chrome yang sedang terbuka terlebih dahulu!")
    print("   Script akan membuka Chrome baru dengan session login Anda.")
    input("\nTekan ENTER untuk mulai download...")
    
    # Setup driver
    print("\n🌐 Membuka Chrome...")
    try:
        driver = setup_chrome_driver()
    except Exception as e:
        print(f"❌ Gagal membuka Chrome: {e}")
        print("\n💡 Tips:")
        print("   1. Pastikan Chrome sudah ditutup sepenuhnya")
        print("   2. Coba jalankan ulang script ini")
        return
    
    # Let user log in first
    print("\n🔐 Silakan LOGIN ke akun Google yang punya akses ke file-file tersebut...")
    driver.get("https://accounts.google.com/")
    print("   1. Login dengan akun Google PRIBADI (bukan mahasiswa)")
    print("   2. Setelah berhasil login, kembali ke terminal ini")
    input("\n   Tekan ENTER setelah login selesai...")
    
    # Download files
    print(f"\n⬇️ Mulai download {len(links)} file...")
    print("-" * 60)
    
    downloaded = 0
    failed = 0
    
    try:
        for i, link in enumerate(links):
            print(f"   [{i+1}/{len(links)}] Row {link['row_no']}...", end=" ")
            
            success = download_file(driver, link['file_id'], link['row_no'])
            
            if success:
                downloaded += 1
                print("✅")
            else:
                failed += 1
                print("❌")
            
            # Progress report every 10 files
            if (i + 1) % 10 == 0:
                print(f"\n   📊 Progress: {downloaded} berhasil, {failed} gagal\n")
    
    except KeyboardInterrupt:
        print("\n\n⏹️ Download dihentikan oleh user.")
    
    finally:
        driver.quit()
    
    # Summary
    print("\n" + "=" * 60)
    print("📊 RINGKASAN DOWNLOAD")
    print("=" * 60)
    print(f"   ✅ Berhasil: {downloaded}")
    print(f"   ❌ Gagal: {failed}")
    print(f"   📁 Folder: {DOWNLOAD_DIR}")
    print("\n💡 Selanjutnya, jalankan script koordinat untuk membaca foto-foto ini:")
    print("   python scripts/process_local_photos.py")


if __name__ == "__main__":
    main()
