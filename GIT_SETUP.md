# GitHubへのコミット手順

## ステップ1: Gitの初期化（初回のみ）

```bash
# Gitリポジトリを初期化
git init

# ユーザー情報を設定（初回のみ）
git config user.name "あなたの名前"
git config user.email "your.email@example.com"
```

## ステップ2: .gitignoreの確認

既に`.gitignore`ファイルがあるので、不要なファイルは除外されます。

```bash
# .gitignoreの内容を確認
cat .gitignore
```

## ステップ3: ファイルをステージング

```bash
# すべての変更をステージング
git add .

# または、特定のファイルのみ
git add src/ tests/ public/ config/ bin/
git add composer.json .env.example README.md
git add DEMO_SCRIPT.md LAYOUT_ADJUSTMENT_GUIDE.md
```

## ステップ4: コミット

```bash
# 変更をコミット
git commit -m "feat: ピッキング帳票生成システムMVP完成

- CSVパーサー実装（改行・カンマ分割対応）
- データ変換エンジン実装
- 計算エンジン実装
- PDF生成エンジン実装（日本語対応）
- Webインターフェース実装
- CLIコマンド実装
- エラーハンドリング実装
- デモスクリプト作成
- レイアウト調整ガイド作成"
```

## ステップ5: GitHubリポジトリの作成

### 5.1 GitHubでリポジトリを作成

1. https://github.com にアクセス
2. 右上の「+」→「New repository」をクリック
3. リポジトリ名を入力（例: `picking-report-generator`）
4. 説明を入力（例: `CSV to PDF Picking Report Generator`）
5. Privateを選択（社内用のため）
6. 「Create repository」をクリック

### 5.2 リモートリポジトリを追加

GitHubで表示されるコマンドをコピーして実行：

```bash
# リモートリポジトリを追加
git remote add origin https://github.com/あなたのユーザー名/picking-report-generator.git

# または SSH の場合
git remote add origin git@github.com:あなたのユーザー名/picking-report-generator.git
```

## ステップ6: プッシュ

```bash
# メインブランチにプッシュ
git branch -M main
git push -u origin main
```

## ステップ7: 以降の変更をコミット

```bash
# 変更を確認
git status

# 変更をステージング
git add .

# コミット
git commit -m "fix: PDF文字化け修正とボタン状態リセット対応"

# プッシュ
git push
```

---

## よく使うGitコマンド

### 状態確認
```bash
# 変更されたファイルを確認
git status

# 変更内容を確認
git diff

# コミット履歴を確認
git log --oneline
```

### ブランチ操作
```bash
# 新しいブランチを作成
git checkout -b feature/new-feature

# ブランチを切り替え
git checkout main

# ブランチ一覧
git branch
```

### 変更の取り消し
```bash
# ステージングを取り消し
git reset HEAD ファイル名

# 変更を破棄
git checkout -- ファイル名

# 直前のコミットを修正
git commit --amend
```

---

## コミットメッセージの書き方

### プレフィックス
- `feat:` 新機能
- `fix:` バグ修正
- `docs:` ドキュメント
- `style:` フォーマット
- `refactor:` リファクタリング
- `test:` テスト追加
- `chore:` その他

### 例
```bash
git commit -m "feat: QRコード生成機能を追加"
git commit -m "fix: CSV解析時の改行処理を修正"
git commit -m "docs: READMEにインストール手順を追加"
```

---

## トラブルシューティング

### 問題1: 認証エラー

**HTTPSの場合:**
```bash
# Personal Access Tokenを使用
# GitHubで Settings > Developer settings > Personal access tokens から生成
# プッシュ時にユーザー名とトークンを入力
```

**SSHの場合:**
```bash
# SSH鍵を生成
ssh-keygen -t ed25519 -C "your.email@example.com"

# 公開鍵をGitHubに登録
cat ~/.ssh/id_ed25519.pub
# GitHubの Settings > SSH and GPG keys に追加
```

### 問題2: 大きなファイルがある

```bash
# 特定のファイルを除外
echo "storage/pdf/*.pdf" >> .gitignore
git rm --cached storage/pdf/*.pdf
git commit -m "chore: 生成されたPDFをgitignoreに追加"
```

### 問題3: コミット履歴をきれいにしたい

```bash
# 直前のコミットメッセージを修正
git commit --amend -m "新しいメッセージ"

# 複数のコミットをまとめる
git rebase -i HEAD~3
```

---

## 推奨: .gitattributes の作成

改行コードの統一のため：

```bash
cat > .gitattributes << 'EOF'
# Auto detect text files and perform LF normalization
* text=auto

# PHP files
*.php text eol=lf

# Markdown files
*.md text eol=lf

# Shell scripts
*.sh text eol=lf

# Windows batch files
*.bat text eol=crlf
*.cmd text eol=crlf
EOF

git add .gitattributes
git commit -m "chore: .gitattributesを追加"
```

---

## GitHub Actions（CI/CD）の設定（オプション）

自動テストを実行する場合：

```bash
mkdir -p .github/workflows
cat > .github/workflows/php.yml << 'EOF'
name: PHP Tests

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0'
        extensions: mbstring, xml, zip
        
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
      
    - name: Run tests
      run: vendor/bin/phpunit
EOF

git add .github/workflows/php.yml
git commit -m "ci: GitHub Actionsでテスト自動実行を追加"
git push
```

---

## まとめ

### 初回セットアップ
1. `git init`
2. `git add .`
3. `git commit -m "feat: 初回コミット"`
4. GitHubでリポジトリ作成
5. `git remote add origin <URL>`
6. `git push -u origin main`

### 日常的な作業
1. `git status` - 変更確認
2. `git add .` - ステージング
3. `git commit -m "メッセージ"` - コミット
4. `git push` - プッシュ

これで完了です！🎉
