# デプロイメントガイド

このドキュメントは、ピッキング帳票生成システムを各種環境にデプロイする手順を説明します。

## 前提条件

- PHP 8.0以上
- Composer
- Webサーバ（Apache 2.4以上 または Nginx 1.18以上）
- 256MB以上のメモリ

## 共通セットアップ手順

### 1. プロジェクトの取得

```bash
# Gitからクローン（または手動でファイルをコピー）
git clone <repository-url> picking-report-generator
cd picking-report-generator
```

### 2. 依存関係のインストール

```bash
composer install --no-dev --optimize-autoloader
```

開発環境の場合は`--no-dev`を省略してください。

### 3. 環境設定

```bash
cp .env.example .env
```

`.env`ファイルを編集：

```env
APP_ENV=production
APP_DEBUG=false
AUTH_USERNAME=admin
AUTH_PASSWORD_HASH=<生成したハッシュ>
```

パスワードハッシュの生成：

```bash
php -r "echo password_hash('your_secure_password', PASSWORD_BCRYPT) . PHP_EOL;"
```

### 4. ディレクトリパーミッション

```bash
chmod -R 775 logs storage
chown -R www-data:www-data logs storage  # Linuxの場合
```

## ローカル環境（XAMPP/MAMP）

### XAMPP（Windows/Mac/Linux）

1. XAMPPをインストール
2. プロジェクトを`htdocs`ディレクトリに配置
   - Windows: `C:\xampp\htdocs\picking-report-generator`
   - Mac: `/Applications/XAMPP/htdocs/picking-report-generator`
3. 共通セットアップ手順を実行
4. Apache設定を編集（オプション）：

```apache
<VirtualHost *:80>
    ServerName picking-report.local
    DocumentRoot "C:/xampp/htdocs/picking-report-generator/public"
    
    <Directory "C:/xampp/htdocs/picking-report-generator/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

5. hostsファイルを編集（オプション）：
   - Windows: `C:\Windows\System32\drivers\etc\hosts`
   - Mac/Linux: `/etc/hosts`
   
   追加：
   ```
   127.0.0.1 picking-report.local
   ```

6. Apacheを再起動
7. ブラウザで`http://localhost/picking-report-generator/public`または`http://picking-report.local`にアクセス

### MAMP（Mac）

1. MAMPをインストール
2. プロジェクトを`/Applications/MAMP/htdocs/`に配置
3. MAMP設定でDocumentRootを`picking-report-generator/public`に設定
4. 共通セットアップ手順を実行
5. MAMPを起動
6. ブラウザで`http://localhost:8888`にアクセス

## 社内サーバ（イントラネット）

### Ubuntu/Debian + Apache

1. 必要なパッケージをインストール：

```bash
sudo apt update
sudo apt install apache2 php8.0 php8.0-cli php8.0-mbstring php8.0-xml php8.0-zip composer
```

2. プロジェクトを配置：

```bash
sudo mkdir -p /var/www/picking-report
sudo cp -r . /var/www/picking-report/
cd /var/www/picking-report
```

3. 共通セットアップ手順を実行

4. Apache仮想ホスト設定：

```bash
sudo nano /etc/apache2/sites-available/picking-report.conf
```

内容：

```apache
<VirtualHost *:80>
    ServerName picking-report.company.local
    DocumentRoot /var/www/picking-report/public

    <Directory /var/www/picking-report/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/picking-report-error.log
    CustomLog ${APACHE_LOG_DIR}/picking-report-access.log combined
</VirtualHost>
```

5. サイトを有効化：

```bash
sudo a2enmod rewrite
sudo a2ensite picking-report.conf
sudo systemctl restart apache2
```

### Ubuntu/Debian + Nginx

1. 必要なパッケージをインストール：

```bash
sudo apt update
sudo apt install nginx php8.0-fpm php8.0-cli php8.0-mbstring php8.0-xml php8.0-zip composer
```

2. プロジェクトを配置（上記と同様）

3. Nginx設定：

```bash
sudo cp config/nginx.conf.example /etc/nginx/sites-available/picking-report
sudo nano /etc/nginx/sites-available/picking-report
```

設定を環境に合わせて編集（パス、ドメイン名など）

4. サイトを有効化：

```bash
sudo ln -s /etc/nginx/sites-available/picking-report /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.0-fpm
```

### Windows Server + IIS

1. IISとPHPをインストール
2. プロジェクトを`C:\inetpub\wwwroot\picking-report`に配置
3. 共通セットアップ手順を実行
4. IISマネージャーで新しいサイトを作成：
   - サイト名: Picking Report
   - 物理パス: `C:\inetpub\wwwroot\picking-report\public`
   - ポート: 80
5. URL Rewriteモジュールをインストール
6. `web.config`を作成（必要に応じて）

## 外部VPS（クラウド）

### AWS EC2

1. EC2インスタンスを起動（Ubuntu 20.04 LTS推奨）
2. セキュリティグループでHTTP(80)、HTTPS(443)を許可
3. SSHで接続：

```bash
ssh -i your-key.pem ubuntu@your-ec2-ip
```

4. 上記「社内サーバ」の手順に従ってセットアップ
5. Elastic IPを割り当て（オプション）
6. Route 53でDNS設定（オプション）

### さくらVPS / ConoHa VPS

1. VPSを契約してOSをインストール（Ubuntu/CentOS推奨）
2. SSHで接続
3. 上記「社内サーバ」の手順に従ってセットアップ
4. ファイアウォール設定：

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### SSL/TLS証明書の設定（Let's Encrypt）

本番環境ではHTTPSを推奨します：

```bash
sudo apt install certbot python3-certbot-apache  # Apache
# または
sudo apt install certbot python3-certbot-nginx   # Nginx

sudo certbot --apache -d your-domain.com  # Apache
# または
sudo certbot --nginx -d your-domain.com   # Nginx
```

## 環境別設定のポイント

### ローカル環境

```env
APP_ENV=local
APP_DEBUG=true
SESSION_SECURE=false
LOG_LEVEL=debug
```

### 社内サーバ

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=false  # HTTPの場合
LOG_LEVEL=warning
```

### 外部VPS

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=true   # HTTPSの場合
LOG_LEVEL=error
```

## トラブルシューティング

### パーミッションエラー

```bash
sudo chown -R www-data:www-data /var/www/picking-report
sudo chmod -R 775 /var/www/picking-report/logs
sudo chmod -R 775 /var/www/picking-report/storage
```

### Composerが見つからない

```bash
# Composerをグローバルインストール
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
```

### メモリ不足エラー

`.env`ファイルで：

```env
PDF_MEMORY_LIMIT=512M
```

または`php.ini`で：

```ini
memory_limit = 512M
max_execution_time = 120
```

### ログファイルが書き込めない

```bash
sudo chmod -R 775 logs
sudo chown -R www-data:www-data logs
```

## セキュリティチェックリスト

- [ ] `.env`ファイルがGitにコミットされていないことを確認
- [ ] 強力なパスワードを設定し、bcryptでハッシュ化
- [ ] 本番環境で`APP_DEBUG=false`に設定
- [ ] HTTPS接続を使用（外部VPSの場合）
- [ ] ファイアウォールを設定
- [ ] 定期的なバックアップを設定
- [ ] ログファイルのローテーションを設定
- [ ] 不要なポートを閉じる

## メンテナンス

### ログのクリーンアップ

```bash
# 7日以上前のログを削除
find logs -name "*.log" -mtime +7 -delete
```

### 一時ファイルのクリーンアップ

```bash
# 24時間以上前の一時ファイルを削除
find storage/tmp -type f -mtime +1 -delete
find storage/pdf -type f -mtime +1 -delete
```

### アップデート

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
sudo systemctl restart apache2  # または nginx
```

## サポート

問題が発生した場合は、ログファイル（`logs/app.log`）を確認してください。
