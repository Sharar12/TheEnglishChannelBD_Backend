import sys
import json
import fitz

pdf_path = sys.argv[1]
output_dir = sys.argv[2]
num_pages = int(sys.argv[3])

doc = fitz.open(pdf_path)
total_pdf_pages = len(doc)
total = min(num_pages, total_pdf_pages)
results = []

for i in range(total):
    page = doc[i]
    pix = page.get_pixmap(dpi=150)
    out = f"{output_dir}/page_{i + 1}.jpg"
    pix.save(out)
    results.append(f"book-previews/page_{i + 1}.jpg")

print(json.dumps({"total_pages": total_pdf_pages, "images": results}))
