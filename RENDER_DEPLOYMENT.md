# Render デプロイメントガイド（完全無料）

Renderは完全無料でPHPアプリをデプロイできます。クレジットカード登録不要！

## 特徴

✅ 完全無料（クレジットカード不要）
✅ PHPネイティブ対応
✅ GitHubと自動連携
❌ 15分間アクセスがないとスリープ（初回アクセス時に起動に30秒程度）

## デプロイ手順

### ステップ1: Renderアカウント作成

1. https://render.com/ にアクセス
2. 「Get Started」をクリック
3. 「Sign in with GitHub」を選択
4. GitHubアカウントで認証

### ステップ2: 新しいWebサービスを作成

1. ダッシュボードで「New +」をクリック
2. 「Web Service」を選択
3. GitHubリポジトリ一覧から「picking-report-generator」を選択
4. 「Connect」をクリック

### ステップ3: サービス設定

以下の設定を入力：

#### 基本設定
- **Name**: `picking-report-generator`（任意の名前）
- **Region**: `Singapore`（日本に最も近い）
- **Branch**: `main`
- **Root Directory**: （空欄のまま）

#### ビルド設定
- **Runtime**: `PHP`
- **Build Command**: 
  ```bash
  composer install --no-dev --optimize-autoloader && mkdir -p storage/pdf storage/tmp logs
  ```
- **Start Command**:
  ```bash
  php -S 0.0.0.0:$PORT -t public
  ```

#### インスタンス設定
- **Instance Type**: `Free`（無料プラン）

### ステップ4: 環境変数を設定

「Environment」セクションで「Add Environment Variable」をクリックし、以下を追加：

```
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
UPLOAD_MAX_SIZE=10485760
PDF_OUTPUT_DIR=/opt/render/project/src/storage/pdf
TMP_DIR=/opt/render/project/src/storage/tmp
LOG_DIR=/opt/render/project/src/logs
```

### ステップ5: デプロイ開始

1. 「Create Web Service」をクリック
2. 自動的にビルドとデプロイが開始されます
3. ログをリアルタイムで確認できます
4. デプロイ完了まで約3-5分

### ステップ6: URLを確認

デプロイ完了後：
1. `your-app-name.onrender.com` のようなURLが生成されます
2. URLをクリックしてアクセス
3. CSVアップロード画面が表示されることを確認

## render.yaml を使った自動設定（推奨）

すでに `render.yaml` ファイルを作成済みなので、Renderが自動的に設定を読み込みます！

手動設定は不要で、リポジトリを選択するだけでOKです。

## 注意事項

### スリープについて
- 15分間アクセスがないとスリープ状態になります
- スリープ中の初回アクセス時、起動に30秒程度かかります
- その後は通常速度で動作します

### スリープを回避する方法（オプション）

無料の外部サービスでpingを送る：

1. **UptimeRobot** (https://uptimerobot.com/)
   - 無料で50サイトまで監視可能
   - 5分ごとにpingを送信
   - スリープを防げる

2. **Cron-job.org** (https://cron-job.org/)
   - 無料でcronジョブ実行
   - 定期的にURLにアクセス

設定方法：
```
1. UptimeRobotにサインアップ
2. 「Add New Monitor」をクリック
3. Monitor Type: HTTP(s)
4. URL: あなたのRender URL
5. Monitoring Interval: 5分
```

## トラブルシューティング

### ビルドエラーが出た場合

1. Renderダッシュボードの「Logs」タブを確認
2. エラーメッセージを確認
3. 必要に応じて設定を修正

### ファイルアップロードが失敗する場合

1. 環境変数が正しく設定されているか確認
2. ストレージディレクトリのパスを確認
3. Renderの無料プランの制限を確認

### 起動が遅い場合

これは正常です。無料プランではスリープ後の初回起動に時間がかかります。
- 初回: 30秒程度
- 2回目以降: 通常速度

## 料金プラン

### 無料プラン（Free）
- ✅ 完全無料
- ✅ 750時間/月の実行時間
- ✅ 512MB RAM
- ✅ 自動SSL証明書
- ❌ 15分でスリープ

### 有料プラン（$7/月〜）
- ✅ スリープなし
- ✅ より多くのRAM
- ✅ カスタムドメイン
- ✅ 優先サポート

## まとめ

Renderは：
- ✅ 完全無料（クレジットカード不要）
- ✅ PHPがそのまま動く
- ✅ 設定が簡単
- ✅ GitHubと自動連携
- ⚠️ スリープする（デモ用途なら許容範囲）

デモやMVPには最適です！
