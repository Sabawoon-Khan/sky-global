#!/usr/bin/env python3
import re
import urllib.request
from pathlib import Path

UA = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
    "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)
base = "https://www.sunskyglobalsecurity.com"
dest_dir = Path("public/images/website/certificates")
dest_dir.mkdir(parents=True, exist_ok=True)

urls = [
    (
        "cert-1.jpeg",
        "/images/imageGallaries/1773120961.WhatsApp Image 2026-03-05 at 9.44.52 AM.jpeg",
    ),
    (
        "cert-2.jpeg",
        "/images/imageGallaries/1773120973.WhatsApp Image 2026-03-05 at 9.44.52 AM.jpeg",
    ),
    (
        "cert-3.jpeg",
        "/images/imageGallaries/1773120987.WhatsApp Image 2026-03-05 at 9.44.52 AM.jpeg",
    ),
]

opener = urllib.request.build_opener()
opener.addheaders = [("User-Agent", UA), ("Referer", base + "/")]

for name, path in urls:
    url = base + urllib.request.quote(path)
    # quote encodes spaces; but leading slash path needs careful quoting
    url = base + "/" + "/".join(
        urllib.request.quote(part) for part in path.lstrip("/").split("/")
    )
    dest = dest_dir / name
    try:
        with opener.open(url, timeout=40) as r:
            data = r.read()
        dest.write_bytes(data)
        png = dest_dir / name.replace(".jpeg", ".png")
        png.write_bytes(data)
        print(f"OK {dest} ({len(data)} bytes) -> also {png.name}")
    except Exception as e:
        print(f"FAIL {url}: {e}")

# Inspect /certificate page
try:
    with opener.open(base + "/certificate", timeout=40) as r:
        html = r.read().decode("utf-8", "ignore")
    imgs = sorted(
        set(
            re.findall(
                r"(?:https://www\.sunskyglobalsecurity\.com)?(/images/[^\"']+\.(?:jpg|jpeg|png|webp|gif))",
                html,
                re.I,
            )
        )
    )
    print("certificate page images:")
    for i in imgs:
        print(" ", i)
except Exception as e:
    print(f"FAIL /certificate: {e}")
