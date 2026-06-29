# SOP Instalasi dan Setup SINTA-SaaS (Ubuntu Server)

Dokumen ini berisi Standard Operating Procedure (SOP) panduan lengkap dari titik nol untuk melakukan instalasi dan setup SINTA-SaaS di Ubuntu Server yang 100% baru dan bersih. Cara ini adalah yang terbaik untuk menghindari semua error bawaan dari eksperimen sebelumnya.

**PENTING**: Pastikan Anda login sebagai **`root`** di terminal VPS Anda. Silakan jalankan langkah-langkah di bawah ini secara berurutan.

## Langkah 1: Update & Install Semua Kebutuhan Server

Copy dan paste semua baris ini sekaligus:

```bash
apt update && apt upgrade -y
apt install -y nginx mariadb-server mariadb-client git unzip curl software-properties-common

# Tambahkan repository PHP 8.2 (Jika Ubuntu Anda versi 22.04 ke bawah)
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP 8.2 dan Ekstensinya
apt install -y php8.2-fpm php8.2-mysql php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip php8.2-gd
```

## Langkah 2: Setup Database MariaDB

Jalankan perintah ini satu per satu:

```bash
# Masuk ke MariaDB
mysql -u root
```

**(DI DALAM MARIADB)** Copy paste 4 baris ini:

```sql
CREATE DATABASE dapodik_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'admin_dapodik'@'localhost' IDENTIFIED BY 'Admin-sma-sinta';
GRANT ALL PRIVILEGES ON dapodik_db.* TO 'admin_dapodik'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Langkah 3: Ambil Source Code dari GitHub

Karena kita akan menggunakan `/var/www/dapodik` sebagai pusatnya:

```bash
# Pastikan foldernya kosong sebelum clone
rm -rf /var/www/dapodik
git clone https://github.com/MA-habibullah/SINTA-SaaS.git /var/www/dapodik

# Berikan hak akses ke Nginx (www-data)
chown -R www-data:www-data /var/www/dapodik
chmod -R 775 /var/www/dapodik
chmod -R 777 /var/www/dapodik/storage
```

## Langkah 4: Hubungkan Aplikasi ke Database

Kita perlu menimpa `Database.php` bawaan GitHub dengan kredensial server Anda.

```bash
cat << 'EOF' > /var/www/dapodik/app/Config/Database.php
<?php
namespace App\Config;
use PDO;
use PDOException;

class Database {
    private static ?PDO $connection = null;
    private string $host = 'localhost';
    private string $dbName = 'dapodik_db';
    private string $username = 'admin_dapodik';
    private string $password = 'Admin-sma-sinta';
    private string $charset = 'utf8mb4';

    public static function getConnection(): PDO {
        if (self::$connection === null) {
            $instance = new self();
            self::$connection = $instance->connect();
        }
        return self::$connection;
    }

    private function connect(): PDO {
        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false,
        ];
        try {
            return new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed.");
        }
    }
}
EOF

# Simpan backup agar deploy.sh otomatis memakainya nanti
cp /var/www/dapodik/app/Config/Database.php /root/Database.php.backup
```

## Langkah 5: Setup Konfigurasi Nginx (Paling Penting!)

Kita akan buat Nginx mengarahkan URL `/SINTA-SaaS` langsung ke `/var/www/dapodik` agar tidak terjadi error 404 (assets tidak ketemu).

```bash
# Hapus konfigurasi lama
rm -f /etc/nginx/sites-enabled/default
rm -f /etc/nginx/sites-available/sinta

# Buat konfigurasi baru
cat << 'EOF' > /etc/nginx/sites-available/sinta
server {
    listen 80;
    server_name sinta.sman11sby.sch.id; # GANTI dengan domain/IP Anda jika perlu

    root /var/www/dapodik;
    index index.php index.html;

    # Agar URL utama langsung membuka aplikasi
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Menangani masalah alias folder SINTA-SaaS (seperti di XAMPP)
    location /SINTA-SaaS/ {
        alias /var/www/dapodik/;
        try_files $uri $uri/ @sinta_saas_php;
    }

    location @sinta_saas_php {
        rewrite ^/SINTA-SaaS/(.*)$ /index.php?/$1 last;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Aktifkan konfigurasi dan restart Nginx
ln -s /etc/nginx/sites-available/sinta /etc/nginx/sites-enabled/
systemctl restart nginx
```

## Langkah 6: Install Database & Seed Data (Final)

Jalankan migrasi database agar struktur tabel terbentuk dan data awal (seeder) masuk.

```bash
cd /var/www/dapodik
php migrate.php fresh
```

Selesai! Sekarang Anda bisa langsung membuka web **SINTA-SaaS** di browser.
