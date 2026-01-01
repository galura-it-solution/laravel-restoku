# VHOST NGINX

Panduan singkat untuk membuat virtual host Nginx untuk proyek `restoku-api`
(Laravel).

## Prasyarat

- Nginx terpasang dan berjalan.
- PHP-FPM terpasang (contoh: PHP 8.2).
- Direktori proyek: `/Users/admin/Desktop/galura/bukuku/restoku-api`

## Contoh konfigurasi server block

Simpan sebagai `/etc/nginx/sites-available/restoku-api` (atau lokasi setara di
OS Anda), lalu sesuaikan:

```nginx
server {
    listen 80;
    server_name restoku.galura.id;

    root /Users/admin/Desktop/galura/bukuku/restoku-api/public;
    index index.php index.html;

    access_log /var/log/nginx/restoku-api.access.log;
    error_log  /var/log/nginx/restoku-api.error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
```

Catatan:
- Ubah `server_name` sesuai domain lokal Anda.
- Ubah `fastcgi_pass` sesuai socket/port PHP-FPM di mesin Anda.

## Aktifkan konfigurasi

```bash
sudo ln -s /etc/nginx/sites-available/restoku-api /etc/nginx/sites-enabled/restoku-api
sudo nginx -t
sudo systemctl reload nginx
```

Jika Anda memakai macOS (Homebrew), direktori dan perintah bisa berbeda:

```bash
ln -s /opt/homebrew/etc/nginx/servers/restoku-api.conf /opt/homebrew/etc/nginx/servers/restoku-api.conf
nginx -t
brew services reload nginx
```

## Hosts file (opsional)

Tambahkan ke `/etc/hosts`:

```
127.0.0.1 restoku.galura.id
```

## Cek hasil

Buka `http://restoku.galura.id` di browser.
