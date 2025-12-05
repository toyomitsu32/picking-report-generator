# Googleスプレッドシート版フォーマット調整ガイド

## 概要

このガイドでは、Googleスプレッドシート版の帳票サンプルを入手した後、PDFレイアウトを完全に一致させるための具体的な手順を説明します。

---

## ステップ1: サンプル分析（30分）

### 1.1 サンプルの入手

必要なもの：
- Googleスプレッドシート版の帳票（PDFまたはスクリーンショット）
- 可能であれば、Googleスプレッドシートの元ファイル

### 1.2 レイアウト要素の測定

以下の項目を測定・記録します：

#### ページ設定
- [ ] 用紙サイズ（A4縦 = 210mm × 297mm）
- [ ] 余白（上下左右）
- [ ] ヘッダー/フッターの高さ

#### フォント設定
- [ ] タイトルのフォント（種類、サイズ、太字/通常）
- [ ] ヘッダー情報のフォント
- [ ] テーブルヘッダーのフォント
- [ ] テーブル本文のフォント
- [ ] フッターのフォント

#### 色設定
- [ ] ヘッダー背景色（RGB値）
- [ ] テーブルヘッダー背景色
- [ ] 交互行の背景色（ある場合）
- [ ] 罫線の色
- [ ] テキストの色

#### 罫線設定
- [ ] 外枠の太さ（pt）
- [ ] 内側の罫線の太さ
- [ ] 罫線のスタイル（実線/点線/破線）

#### 間隔設定
- [ ] セクション間の余白
- [ ] テーブルの行の高さ
- [ ] セル内のパディング

---

## ステップ2: 設定ファイルの作成（15分）

測定した値を設定ファイルに記録します。

### 2.1 レイアウト設定ファイルの作成

`config/layout_config.php` を作成：

```php
<?php
/**
 * Googleスプレッドシート版フォーマット設定
 */

return [
    // ページ設定
    'page' => [
        'format' => 'A4',
        'orientation' => 'P', // Portrait (縦)
        'margin_left' => 15,   // mm
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20,
        'margin_header' => 10,
        'margin_footer' => 10,
    ],
    
    // フォント設定
    'fonts' => [
        'title' => [
            'family' => 'DejaVu Sans',
            'size' => 16,
            'weight' => 'bold',
            'color' => '#333333',
        ],
        'header_label' => [
            'family' => 'DejaVu Sans',
            'size' => 10,
            'weight' => 'bold',
            'color' => '#000000',
        ],
        'header_value' => [
            'family' => 'DejaVu Sans',
            'size' => 10,
            'weight' => 'normal',
            'color' => '#000000',
        ],
        'item_header' => [
            'family' => 'DejaVu Sans',
            'size' => 11,
            'weight' => 'bold',
            'color' => '#000000',
        ],
        'table_header' => [
            'family' => 'DejaVu Sans',
            'size' => 9,
            'weight' => 'bold',
            'color' => '#000000',
        ],
        'table_body' => [
            'family' => 'DejaVu Sans',
            'size' => 9,
            'weight' => 'normal',
            'color' => '#000000',
        ],
    ],
    
    // 色設定
    'colors' => [
        'header_bg' => '#f0f0f0',
        'table_header_bg' => '#e0e0e0',
        'table_alt_row_bg' => '#f9f9f9',
        'border' => '#333333',
        'item_header_bg' => '#f0f0f0',
    ],
    
    // 罫線設定
    'borders' => [
        'outer_width' => 2,      // pt
        'inner_width' => 1,
        'style' => 'solid',
    ],
    
    // 間隔設定
    'spacing' => [
        'section_gap' => 20,     // px
        'table_row_height' => 25,
        'cell_padding' => 6,
        'item_block_margin' => 25,
    ],
    
    // テーブル列幅（%）
    'table_columns' => [
        'part_code' => 15,
        'part_name' => 30,
        'quantity' => 10,
        'width' => 15,
        'height' => 15,
        'notes' => 15,
    ],
];
```

---

## ステップ3: PdfGeneratorの修正（30分）

### 3.1 設定ファイルの読み込み

`src/Generators/PdfGenerator.php` を修正：

```php
class PdfGenerator
{
    private array $layoutConfig;
    
    public function __construct(
        string $outputDir = './storage/pdf',
        ?array $layoutConfig = null
    ) {
        $this->outputDir = $outputDir;
        
        // レイアウト設定を読み込み
        $this->layoutConfig = $layoutConfig ?? require BASE_PATH . '/config/layout_config.php';
        
        // 出力ディレクトリの確認
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0775, true);
        }
    }
}
```

### 3.2 mPDF初期化の修正

```php
private function createMpdf(): Mpdf
{
    $pageConfig = $this->layoutConfig['page'];
    
    return new Mpdf([
        'mode' => 'utf-8',
        'format' => $pageConfig['format'],
        'orientation' => $pageConfig['orientation'],
        'margin_left' => $pageConfig['margin_left'],
        'margin_right' => $pageConfig['margin_right'],
        'margin_top' => $pageConfig['margin_top'],
        'margin_bottom' => $pageConfig['margin_bottom'],
        'margin_header' => $pageConfig['margin_header'],
        'margin_footer' => $pageConfig['margin_footer'],
    ]);
}
```

### 3.3 CSSスタイルの動的生成

```php
private function getStyles(): string
{
    $fonts = $this->layoutConfig['fonts'];
    $colors = $this->layoutConfig['colors'];
    $borders = $this->layoutConfig['borders'];
    $spacing = $this->layoutConfig['spacing'];
    $columns = $this->layoutConfig['table_columns'];
    
    return '<style>
        body {
            font-family: "' . $fonts['table_body']['family'] . '", sans-serif;
            font-size: ' . $fonts['table_body']['size'] . 'pt;
            line-height: 1.4;
        }
        .header {
            margin-bottom: ' . $spacing['section_gap'] . 'px;
            border-bottom: ' . $borders['outer_width'] . 'px solid ' . $colors['border'] . ';
            padding-bottom: 10px;
        }
        .header-title {
            font-size: ' . $fonts['title']['size'] . 'pt;
            font-weight: ' . $fonts['title']['weight'] . ';
            color: ' . $fonts['title']['color'] . ';
            margin-bottom: 10px;
        }
        .header-label {
            font-weight: ' . $fonts['header_label']['weight'] . ';
            font-size: ' . $fonts['header_label']['size'] . 'pt;
            width: 120px;
            padding: 3px 0;
        }
        .header-value {
            font-size: ' . $fonts['header_value']['size'] . 'pt;
            padding: 3px 0;
        }
        .item-block {
            margin-bottom: ' . $spacing['item_block_margin'] . 'px;
            page-break-inside: avoid;
        }
        .item-header {
            background-color: ' . $colors['item_header_bg'] . ';
            padding: 8px;
            font-weight: ' . $fonts['item_header']['weight'] . ';
            font-size: ' . $fonts['item_header']['size'] . 'pt;
            border: ' . $borders['outer_width'] . 'px solid ' . $colors['border'] . ';
            margin-bottom: 5px;
        }
        .parts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .parts-table th {
            background-color: ' . $colors['table_header_bg'] . ';
            border: ' . $borders['inner_width'] . 'px solid ' . $colors['border'] . ';
            padding: ' . $spacing['cell_padding'] . 'px;
            text-align: left;
            font-weight: ' . $fonts['table_header']['weight'] . ';
            font-size: ' . $fonts['table_header']['size'] . 'pt;
            height: ' . $spacing['table_row_height'] . 'px;
        }
        .parts-table td {
            border: ' . $borders['inner_width'] . 'px solid ' . $colors['border'] . ';
            padding: ' . $spacing['cell_padding'] . 'px;
            font-size: ' . $fonts['table_body']['size'] . 'pt;
            height: ' . $spacing['table_row_height'] . 'px;
        }
        .parts-table tr:nth-child(even) {
            background-color: ' . $colors['table_alt_row_bg'] . ';
        }
        .col-part-code { width: ' . $columns['part_code'] . '%; }
        .col-part-name { width: ' . $columns['part_name'] . '%; }
        .col-quantity { width: ' . $columns['quantity'] . '%; }
        .col-width { width: ' . $columns['width'] . '%; }
        .col-height { width: ' . $columns['height'] . '%; }
        .col-notes { width: ' . $columns['notes'] . '%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>';
}
```

### 3.4 テーブルヘッダーの修正

```php
public function renderPartsTable(array $parts): string
{
    $html = '<table class="parts-table">';
    
    // Table header with column classes
    $html .= '<thead><tr>';
    $html .= '<th class="col-part-code">パーツコード</th>';
    $html .= '<th class="col-part-name">パーツ名</th>';
    $html .= '<th class="col-quantity text-center">数量</th>';
    $html .= '<th class="col-width text-right">幅</th>';
    $html .= '<th class="col-height text-right">高さ</th>';
    $html .= '<th class="col-notes">備考</th>';
    $html .= '</tr></thead>';
    
    // ... rest of the table
}
```

---

## ステップ4: 視覚的な比較とテスト（30分）

### 4.1 比較用PDFの生成

```bash
# テストPDFを生成
php bin/generate-report.php storage/tmp/sample.csv
```

### 4.2 視覚的な比較

以下の方法で比較：

1. **並べて表示**
   - Googleスプレッドシート版PDF
   - 生成したPDF
   - 2つを並べて目視確認

2. **重ね合わせ確認**
   - PDFビューアで透明度を調整して重ね合わせ
   - ズレがないか確認

3. **印刷して確認**
   - 実際に印刷して比較
   - 現場作業者に確認してもらう

### 4.3 チェックリスト

```markdown
## レイアウト確認チェックリスト

### ページ全体
- [ ] 用紙サイズが一致
- [ ] 余白が一致
- [ ] 全体的な配置が一致

### ヘッダー部分
- [ ] タイトルの位置とサイズ
- [ ] 受注情報の配置
- [ ] フォントサイズと太さ
- [ ] 罫線の太さと位置

### アイテムブロック
- [ ] ブロックの背景色
- [ ] 罫線の太さ
- [ ] フォントサイズ
- [ ] 余白とパディング

### パーツテーブル
- [ ] 列幅の比率
- [ ] ヘッダーの背景色
- [ ] 罫線の太さ
- [ ] 行の高さ
- [ ] セル内のパディング
- [ ] 交互行の背景色
- [ ] テキストの配置（左/中央/右）

### フッター部分
- [ ] 合計値の表示位置
- [ ] フォントサイズと太さ
- [ ] 罫線の位置
```

---

## ステップ5: 微調整（1-2時間）

### 5.1 よくある調整ポイント

#### フォントサイズの微調整

Googleスプレッドシートのフォントサイズは、PDFでは若干異なって見えることがあります：

```php
// 0.5pt単位で調整可能
'fonts' => [
    'table_body' => [
        'size' => 9.5,  // 9ptだと小さい、10ptだと大きい場合
    ],
],
```

#### 行の高さ調整

```php
'spacing' => [
    'table_row_height' => 28,  // 25だと詰まって見える場合
],
```

#### 列幅の微調整

```php
'table_columns' => [
    'part_code' => 14,   // 15%から14%に調整
    'part_name' => 31,   // 30%から31%に調整（合計100%を維持）
    // ...
],
```

#### 色の微調整

Googleスプレッドシートの色をRGB値で正確に取得：

```php
'colors' => [
    'header_bg' => '#f3f3f3',  // より正確な色
    'table_header_bg' => '#d9d9d9',
],
```

### 5.2 日本語フォントの対応

Googleスプレッドシートで日本語フォントを使用している場合：

```php
// mPDF設定に日本語フォントを追加
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'ipagothic',  // 日本語フォント
    // または
    'default_font' => 'notosansjp',
]);
```

フォント設定ファイルに追加：

```php
'fonts' => [
    'title' => [
        'family' => 'ipagothic',  // 日本語ゴシック体
        'size' => 16,
    ],
],
```

---

## ステップ6: 設定の外部化（30分）

### 6.1 複数レイアウトへの対応

将来的に複数のレイアウトに対応する場合：

```
config/
  ├── layout_config.php          # デフォルト
  ├── layout_googlesheet_v1.php  # Googleスプレッドシート版v1
  ├── layout_googlesheet_v2.php  # Googleスプレッドシート版v2
  └── layout_custom.php          # カスタム版
```

### 6.2 レイアウト選択機能

```php
// ReportControllerで選択
public function processReport(
    string $csvFilePath, 
    array $config = [],
    string $layoutName = 'default'
): array {
    // レイアウト設定を読み込み
    $layoutConfig = $this->loadLayoutConfig($layoutName);
    
    // PdfGeneratorに渡す
    $pdfGenerator = new PdfGenerator(
        $this->outputDir,
        $layoutConfig
    );
    
    // ...
}

private function loadLayoutConfig(string $name): array
{
    $configFile = BASE_PATH . "/config/layout_{$name}.php";
    
    if (!file_exists($configFile)) {
        $configFile = BASE_PATH . "/config/layout_config.php";
    }
    
    return require $configFile;
}
```

---

## ステップ7: ドキュメント化（15分）

### 7.1 調整履歴の記録

`docs/LAYOUT_ADJUSTMENTS.md` を作成：

```markdown
# レイアウト調整履歴

## 2024-12-05: 初期実装
- 基本的なレイアウトを実装
- A4縦サイズ、基本的なテーブル構造

## 2024-12-10: Googleスプレッドシート版対応
- サンプルPDFを入手
- 以下の項目を調整：
  - ヘッダー背景色: #f0f0f0 → #f3f3f3
  - テーブル行高さ: 25px → 28px
  - フォントサイズ: 10pt → 9.5pt
  - 列幅比率を微調整

## 測定値の記録
- タイトルフォント: 16pt, Bold
- ヘッダーラベル: 10pt, Bold
- テーブルヘッダー: 9pt, Bold
- テーブル本文: 9.5pt, Normal
```

---

## トラブルシューティング

### 問題1: フォントが正しく表示されない

**原因**: 日本語フォントが埋め込まれていない

**解決策**:
```bash
# 日本語フォントをインストール
composer require mpdf/mpdf-ja
```

```php
// mPDF設定
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'default_font' => 'ipagothic',
]);
```

### 問題2: 罫線が太すぎる/細すぎる

**原因**: ブラウザとPDFビューアで表示が異なる

**解決策**:
- 実際に印刷して確認
- 複数のPDFビューアで確認
- 0.5pt単位で微調整

### 問題3: 色が微妙に違う

**原因**: RGB値の取得が不正確

**解決策**:
- Googleスプレッドシートから直接RGB値を取得
- カラーピッカーツールを使用
- 印刷して色を確認

### 問題4: レイアウトが崩れる

**原因**: 長いテキストや大量のデータ

**解決策**:
```php
// テキストの折り返し設定
'table_body' => [
    'word-wrap' => 'break-word',
    'overflow-wrap' => 'break-word',
],

// ページブレークの制御
.item-block {
    page-break-inside: avoid;
}
```

---

## まとめ

### 所要時間の目安

- ステップ1（分析）: 30分
- ステップ2（設定作成）: 15分
- ステップ3（コード修正）: 30分
- ステップ4（比較テスト）: 30分
- ステップ5（微調整）: 1-2時間
- ステップ6（外部化）: 30分
- ステップ7（ドキュメント）: 15分

**合計: 約3-4時間**

### 成果物

1. `config/layout_config.php` - レイアウト設定ファイル
2. 修正された `PdfGenerator.php`
3. `docs/LAYOUT_ADJUSTMENTS.md` - 調整履歴
4. 比較用のPDFサンプル

### 次のステップ

1. 現場作業者にレビューしてもらう
2. フィードバックを反映
3. 複数のサンプルデータでテスト
4. 本番環境にデプロイ
