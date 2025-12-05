# ピッキング帳票生成システム

受注データ（CSV形式）から社内用ピッキング帳票（PDF形式）を自動生成するWebアプリケーションです。

## 必要要件

- PHP 8.0以上
- Composer
- Apache 2.4 または Nginx 1.18以上
- 256MB以上のメモリ

## クイックスタート（デモ）

サンプルCSVからPDFを生成してシステムを試すことができます：

```bash
# 依存関係をインストール
composer install

# サンプルCSVからPDF生成
php bin/generate-report.php storage/tmp/sample.csv
```

生成されたPDFは`storage/pdf/`ディレクトリに保存されます。

## インストール

### 1. 依存関係のインストール

```bash
composer install
```

### 2. 環境設定

```bash
cp .env.example .env
```

`.env`ファイルを編集して、環境に応じた設定を行ってください。

### 3. ディレクトリパーミッション

```bash
chmod -R 775 logs storage
```

### 4. パスワードハッシュの生成

管理者パスワードを設定する場合：

```bash
php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
```

生成されたハッシュを`.env`の`AUTH_PASSWORD_HASH`に設定してください。

## デプロイメント

### ローカル環境（XAMPP/MAMP）

1. プロジェクトをhtdocsディレクトリに配置
2. `http://localhost/picking-report-generator/public`にアクセス

### Apache

`.htaccess`ファイルが`public`ディレクトリに含まれています。
DocumentRootを`public`ディレクトリに設定してください。

### Nginx

`config/nginx.conf.example`を参考にNginx設定を行ってください。

```bash
sudo cp config/nginx.conf.example /etc/nginx/sites-available/picking-report
sudo ln -s /etc/nginx/sites-available/picking-report /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 外部VPS

1. サーバにプロジェクトをアップロード
2. Composer依存関係をインストール
3. 環境設定ファイルを作成
4. WebサーバをDocumentRoot設定
5. ファイルパーミッションを設定

## テスト実行

### すべてのテスト

```bash
vendor/bin/phpunit
```

### ユニットテストのみ

```bash
vendor/bin/phpunit tests/Unit
```

### プロパティベーステストのみ

```bash
vendor/bin/phpunit tests/Property
```

## ディレクトリ構造

```
.
├── config/              # 設定ファイル
├── logs/                # ログファイル
├── public/              # Webルート
│   └── index.php        # エントリーポイント
├── src/                 # アプリケーションコード
├── storage/             # 一時ファイル・生成PDF
│   ├── pdf/
│   └── tmp/
├── tests/               # テストコード
│   ├── Unit/
│   ├── Property/
│   └── Integration/
├── vendor/              # Composer依存関係
├── .env                 # 環境設定（gitignore）
├── .env.example         # 環境設定サンプル
├── composer.json        # Composer設定
└── phpunit.xml          # PHPUnit設定
```

## セキュリティ

- パスワードは必ずbcryptでハッシュ化してください
- 本番環境では`APP_ENV=production`、`APP_DEBUG=false`に設定してください
- `storage`と`logs`ディレクトリはWebからアクセスできないようにしてください
- HTTPS接続を推奨します

## ライセンス

Proprietary - 社内使用のみ
