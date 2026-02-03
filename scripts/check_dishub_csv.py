import csv

csv_file = r"D:\KP\Data PLN\Dishub Kab.CRB\TOTAL DATABASE CIREBON (1).csv"

print("Looking for specific columns...")

with open(csv_file, 'r', encoding='utf-8-sig') as f:
    reader = csv.reader(f, delimiter=';')
    header = next(reader)
    
    print(f"Total columns: {len(header)}")
    print("\nAll columns:")
    for i, col in enumerate(header):
        print(f"  {i}: '{col}'")
    
    # Look for important columns by pattern
    print("\n\nImportant columns found:")
    important = ['IDPEL', 'KDAM', 'STATUS', 'LAMPU', 'GAMBAR', 'LINK', 'DAYA']
    for i, col in enumerate(header):
        for pattern in important:
            if pattern in col.upper():
                print(f"  [{i}] {col}")
                break
