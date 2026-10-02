#!/usr/bin/env bash
# Smoke test HTTP: login sebagai admin, buka /profile, cek markup kunci.
set -u
BASE='http://localhost:8000'
JAR=$(mktemp)
COOKIE="$JAR"

get_token() {
  grep -o 'name="_token" value="[^"]*"' "$1" | head -1 | sed 's/.*value="//;s/"//'
}

echo "=== 1. Ambil halaman login ==="
curl -s -c "$COOKIE" "$BASE/login" -o /tmp/login.html
TOKEN=$(get_token /tmp/login.html)
echo "token: ${TOKEN:0:12}..."

echo "=== 2. Login sebagai admin ==="
curl -s -b "$COOKIE" -c "$COOKIE" -X POST "$BASE/login" \
  -d "_token=$TOKEN" -d "email=admin@gmail.com" -d "password=password123" \
  -o /dev/null -w 'HTTP %{http_code} -> %{redirect_url}\n'

echo "=== 3. Buka /profile ==="
curl -s -b "$COOKIE" -c "$COOKIE" "$BASE/profile" -o /tmp/profile.html -w 'HTTP %{http_code}\n'

echo "=== 4. Cek markup kunci ==="
for needle in 'Profil Saya' 'Foto Profil' 'name="foto_profile"' 'name="jenis_kelamin"' 'images/notification.png'; do
  if grep -qF "$needle" /tmp/profile.html; then
    echo "  OK   : $needle"
  else
    echo "  HILANG: $needle"
  fi
done

# Form harus POST ke /profile (PUT lewat @method), bukan route name.
echo "=== 4b. Aksi form profile ==="
grep -o 'action="[^"]*"' /tmp/profile.html | grep -q 'profile' \
  && echo "  OK   : form action ke /profile" \
  || echo "  HILANG: form action ke /profile"

echo "=== 5. Cek icon lonceng benar-benar Termuat (HTTP asset) ==="
curl -s -o /dev/null -w 'notification.png -> HTTP %{http_code} type=%{content_type} size=%{size_download}\n' \
  "$BASE/images/notification.png"

echo "=== 6. Cek halaman Kelola User admin masih hidup ==="
curl -s -b "$COOKIE" "$BASE/admin/users" -o /tmp/users.html -w 'HTTP %{http_code}\n'
grep -qF 'Daftar Pengguna Sistem' /tmp/users.html && echo "  OK   : daftar user render" || echo "  HILANG: daftar user"

echo '=== 7. Halaman peminjam & petugas ==='
for u in /peminjam/katalog /petugas/peminjaman; do
  curl -s -b "$COOKIE" "$BASE$u" -o /dev/null -w "$u -> HTTP %{http_code}\n"
done

echo "=== 8. Tombol Kembali ke halaman terakhir ==="
# Kunjungi daftar user (dengan filter) dulu, baru /profile, supaya
# ada halaman sebelumnya. Refresh /profile dua kali untuk memastikan
# tidak loop ke diri sendiri.
curl -s -b "$COOKIE" -c "$COOKIE" "$BASE/admin/users?role=petugas" -o /dev/null
curl -s -b "$COOKIE" -c "$COOKIE" "$BASE/profile" -o /dev/null
curl -s -b "$COOKIE" -c "$COOKIE" "$BASE/profile" -o /dev/null
curl -s -b "$COOKIE" -c "$COOKIE" "$BASE/profile" -o /tmp/p.html

KEMBALI=$(grep -o 'href="[^"]*"[^>]*>[[:space:]]*<svg[^>]*>.\{0,400\}' /tmp/p.html \
  | grep -B0 'Kembali' -o | head -1)
# lebih aman: ambil blok anchor pertama yang berisi teks Kembali
python3 - <<'PY' /tmp/p.html
import re, sys
html = open(sys.argv[1], encoding='utf-8', errors='replace').read()
m = re.search(r'<a[^>]*href="([^"]*)"[^>]*>(?:(?!</a>).)*?Kembali', html, re.S)
print('  href Kembali :', m.group(1) if m else 'TIDAK ADA')
print('  self-loop?  :', 'YA (BAHAYA)' if m and m.group(1).rstrip('/').endswith('/profile') else 'TIDAK (AMAN)')
PY

rm -f "$JAR" /tmp/login.html /tmp/profile.html /tmp/users.html /tmp/p.html
