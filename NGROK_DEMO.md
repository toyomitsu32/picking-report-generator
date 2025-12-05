# ngrok を使った無料デモ公開

デモ時だけ外部公開したい場合、ngrokが最適です。完全無料でクレジットカード不要！

## ngrok とは

ローカルで動いているアプリを、一時的に外部からアクセス可能にするサービス

```
あなたのPC（localhost:8000）
    ↓
ngrok トンネル
    ↓
https://xxxx-xx-xx-xx-xx.ngrok-free.app
    ↓
世界中からアクセス可能
```

## メリット

✅ 完全無料
✅ クレジットカード不要
✅ 設定が超簡単（1分）
✅ HTTPS対応
✅ デモ時だけ起動
✅ 本番環境不要

## デメリット

⚠️ PCを起動している間だけ有効
⚠️ URLが毎回変わる（無料プラン）
⚠️ 24時間常時公開には不向き

## インストール手順

### 1. ngrok をインストール

```bash
# Homebrewでインストール
brew install ngrok/ngrok/ngrok
```

### 2. アカウント作成（無料）

1. https://ngrok.com/ にアクセス
2. 「Sign up」をクリック
3. GitHubまたはGoogleアカウントでサインアップ

### 3. 認証トークンを設定

```bash
# ダッシュボードからトークンをコピー
ngrok config add-authtoken YOUR_AUTH_TOKEN
```

## 使い方

### ステップ1: ローカルサーバーを起動

```bash
# プロジェクトディレクトリで
php -S localhost:8000 -t public
```

### ステップ2: ngrok でトンネルを作成

別のターミナルで：

```bash
ngrok http 8000
```

### ステップ3: 公開URLを取得

ngrokが表示するURLをコピー：

```
Forwarding  https://xxxx-xx-xx-xx-xx.ngrok-free.app -> http://localhost:8000
```

このURLを共有すれば、誰でもアクセス可能！

## デモ時の手順

### デモ開始前（5分前）

```bash
# ターミナル1: PHPサーバー起動
cd /path/to/csvtoPDF
php -S localhost:8000 -t public

# ターミナル2: ngrok起動
ngrok http 8000
```

### デモ中

1. ngrokのURLを画面共有
2. CSVアップロードをデモ
3. PDF生成をデモ

### デモ終了後

```bash
# Ctrl+C で両方のプロセスを停止
```

## 便利な機能

### ngrok Web UI

ngrokを起動すると、ローカルで管理画面が使えます：

```
http://localhost:4040
```

ここで以下が確認できます：
- リクエスト履歴
- レスポンス内容
- エラーログ

### 固定URL（有料プラン $8/月）

毎回URLが変わるのが嫌な場合：

```bash
# 有料プランで固定ドメイン取得
ngrok http 8000 --domain=your-custom-domain.ngrok-free.app
```

## セキュリティ

### Basic認証を追加

```bash
# パスワード保護
ngrok http 8000 --basic-auth="username:password"
```

### IPアドレス制限（有料プラン）

```bash
# 特定IPのみ許可
ngrok http 8000 --cidr-allow="203.0.113.0/24"
```

## 代替案: localtunnel（完全無料）

ngrokの代替として、localtunnelも使えます：

```bash
# インストール
npm install -g localtunnel

# 起動
lt --port 8000
```

特徴：
- ✅ 完全無料
- ✅ アカウント不要
- ⚠️ 安定性がngrokより低い

## まとめ

### デモ用途なら ngrok が最適

| 用途 | 推奨方法 |
|------|---------|
| **デモ・プレゼン** | ngrok（無料） |
| **短期テスト** | ngrok（無料） |
| **常時公開** | Railway/Render（有料） |
| **本番環境** | Railway/Render（有料） |

### コスト比較

```
ngrok（デモ用）:     $0/月
Railway:            $6-7/月
Render:             $7/月
さくらサーバー:      ¥131/月（約$1）
```

## 実際の使用例

```bash
# 1. PHPサーバー起動
$ php -S localhost:8000 -t public
PHP 8.5.0 Development Server started

# 2. ngrok起動（別ターミナル）
$ ngrok http 8000

Session Status    online
Forwarding        https://a1b2-c3d4.ngrok-free.app -> http://localhost:8000

# 3. URLを共有してデモ開始！
```

デモが終わったら Ctrl+C で停止するだけ！
