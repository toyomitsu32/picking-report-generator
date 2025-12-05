# Railway デプロイメントガイド

このガイドでは、GitHubリポジトリからRailwayを使ってPHPアプリケーションをデプロイする手順を説明します。

## 前提条件

- GitHubアカウント
- コードがGitHubにプッシュ済み（完了済み✅）
- Railwayアカウント（無料で作成可能）

## ステップ1: Railway アカウント作成

1. https://railway.app/ にアクセス
2. 「Start a New Project」をクリック
3. GitHubアカウントでサインアップ

## ステップ2: プロジェクトのデプロイ設定

### 2.1 必要なファイルを追加

プロジェクトに以下のファイルを追加する必要があります：

#### `railway.json` (Railway設定ファイル)

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php -S 0.0.0.0:$PORT -t public",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

#### `nixpacks.toml` (ビルド設定)

```toml
[phases.setup]
nixPkgs = ["php82", "php82Packages.composer"]

[phases.install]
cmds = ["composer install --no-dev --optimize-autoloader"]

[phases.build]
cmds = ["mkdir -p storage/pdf storage/tmp logs"]

[start]
cmd = "php -S 0.0.0.0:$PORT -t public"
```

#### `.htaccess` の更新

`public/.htaccess` を以下のように更新：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# セキュリティヘッダー
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# PHPの設定
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value memory_limit 256M
php_value max_execution_time 300
```

## ステップ3: GitHubにプッシュ

```bash
# 新しいファイルを追加
git add railway.json nixpacks.toml public/.htaccess

# コミット
git commit -m "Add Railway deployment configuration"

# プッシュ
git push origin main
```

## ステップ4: Railwayでデプロイ

1. Railway ダッシュボードで「New Project」をクリック
2. 「Deploy from GitHub repo」を選択
3. `picking-report-generator` リポジトリを選択
4. 自動的にビルドとデプロイが開始されます

## ステップ5: 環境変数の設定

1. Railway プロジェクトの「Variables」タブを開く
2. 以下の環境変数を追加：

```
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
UPLOAD_MAX_SIZE=10485760
PDF_OUTPUT_DIR=/app/storage/pdf
TMP_DIR=/app/storage/tmp
LOG_DIR=/app/logs
```

## ステップ6: ドメインの設定

1. Railway プロジェクトの「Settings」タブを開く
2. 「Domains」セクションで「Generate Domain」をクリック
3. `your-app.up.railway.app` のようなURLが生成されます

カスタムドメインを使用する場合：
1. 「Add Custom Domain」をクリック
2. ドメイン名を入力
3. DNSレコードを設定（Railway が指示を表示）

## ステップ7: デプロイの確認

1. 生成されたURLにアクセス
2. CSVアップロード画面が表示されることを確認
3. サンプルCSVをアップロードしてPDF生成をテスト

## トラブルシューティング

### ビルドエラーが発生する場合

Railway のログを確認：
1. プロジェクトの「Deployments」タブを開く
2. 最新のデプロイをクリック
3. 「View Logs」でエラーメッセージを確認

### ファイルアップロードが失敗する場合

1. ストレージディレクトリの権限を確認
2. 環境変数が正しく設定されているか確認
3. Railway の無料プランの制限を確認（500MB ストレージ）

### PDF生成が失敗する場合

1. メモリ制限を確認（Railway 無料プランは512MB）
2. ログファイルでエラーメッセージを確認
3. 必要に応じて有料プランにアップグレード

## Railway の制限事項

### 無料プラン
- 500時間/月の実行時間
- 512MB RAM
- 1GB ストレージ
- 100GB 転送量/月

### 有料プラン（$5/月〜）
- 無制限の実行時間
- より多くのRAMとストレージ
- カスタムドメイン対応

## 代替案: Render でのデプロイ

Renderも同様に簡単にデプロイできます：

1. https://render.com/ にアクセス
2. GitHubアカウントでサインアップ
3. 「New Web Service」を選択
4. リポジトリを選択
5. 以下の設定を入力：
   - **Environment**: PHP
   - **Build Command**: `composer install --no-dev`
   - **Start Command**: `php -S 0.0.0.0:$PORT -t public`

## まとめ

1. ✅ `railway.json` と `nixpacks.toml` を作成
2. ✅ GitHubにプッシュ
3. ✅ Railwayでプロジェクトを作成
4. ✅ 環境変数を設定
5. ✅ デプロイを確認

これで、GitHubにプッシュするたびに自動的にデプロイされます！
