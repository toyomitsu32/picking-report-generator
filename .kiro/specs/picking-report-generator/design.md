# 設計書

## 概要

ピッキング帳票生成システムは、受注データ（CSV形式）から社内用ピッキング帳票（PDF形式）を自動生成するWebアプリケーションです。PHPをベースとし、mPDFライブラリを使用してGoogleスプレッドシート版フォーマットを忠実に再現したPDFを出力します。

システムは以下の3つの主要コンポーネントで構成されます：
1. **Webインターフェース層**: ユーザーがCSVをアップロードし、PDFをダウンロードするUI
2. **データ処理層**: CSVの解析、データ変換、計算処理を行うビジネスロジック
3. **PDF生成層**: 処理されたデータを基にPDFを生成する出力エンジン

## アーキテクチャ

### システム構成

```
┌─────────────────────────────────────────────────────────┐
│                    Webブラウザ                           │
│              (ユーザーインターフェース)                   │
└────────────────────┬────────────────────────────────────┘
                     │ HTTP/HTTPS
                     ↓
┌─────────────────────────────────────────────────────────┐
│                  Webサーバ (Apache/Nginx)                │
└────────────────────┬────────────────────────────────────┘
                     │
                     ↓
┌─────────────────────────────────────────────────────────┐
│                   PHPアプリケーション                     │
│  ┌──────────────────────────────────────────────────┐  │
│  │  認証モジュール (AuthController)                  │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │  アップロードコントローラ (UploadController)       │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │  CSVパーサー (CsvParser)                          │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │  データ変換エンジン (DataTransformer)             │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │  計算エンジン (CalculationEngine)                 │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │  PDF生成エンジン (PdfGenerator)                   │  │
│  │  - mPDFライブラリ                                 │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                     │
                     ↓
┌─────────────────────────────────────────────────────────┐
│              ファイルシステム (一時保存)                  │
│  - アップロードされたCSV                                 │
│  - 生成されたPDF                                         │
└─────────────────────────────────────────────────────────┘
```

### 技術スタック

- **言語**: PHP 8.0以上
- **Webサーバ**: Apache 2.4 または Nginx 1.18以上
- **PDF生成ライブラリ**: mPDF 8.x（複雑なレイアウト再現に最適）
- **フレームワーク**: 軽量フレームワーク（Slim Framework 4.x）またはバニラPHP
- **セッション管理**: PHPネイティブセッション
- **環境設定**: .envファイルによる環境変数管理

### デプロイメント環境

システムは以下の3つの環境で稼働可能：
1. **ローカル環境**: XAMPP/MAMP等のローカル開発環境
2. **社内サーバ**: イントラネット内のLinux/Windowsサーバ
3. **外部VPS**: AWS EC2、さくらVPS、ConoHa等のクラウドサーバ

## コンポーネントとインターフェース

### 1. 認証モジュール (AuthController)

**責務**: ユーザー認証とセッション管理

**公開メソッド**:
```php
class AuthController {
    public function showLoginForm(): void
    public function login(string $username, string $password): bool
    public function logout(): void
    public function isAuthenticated(): bool
}
```

**インターフェース**:
- 入力: ユーザー名、パスワード
- 出力: 認証成功/失敗のブール値、セッションID

### 2. アップロードコントローラ (UploadController)

**責務**: CSVファイルのアップロード処理とバリデーション

**公開メソッド**:
```php
class UploadController {
    public function handleUpload(array $file): UploadResult
    public function validateFile(array $file): ValidationResult
    public function saveTemporaryFile(array $file): string
}
```

**インターフェース**:
- 入力: $_FILES配列
- 出力: 一時ファイルパス、バリデーション結果

### 3. CSVパーサー (CsvParser)

**責務**: CSVファイルの読み込みと構造化データへの変換

**公開メソッド**:
```php
class CsvParser {
    public function parse(string $filePath): ParsedData
    public function splitCellByNewline(string $cell): array
    public function splitCellByComma(string $cell): array
    public function extractByKeyword(string $cell, string $keyword): array
    public function parseItems(array $rows): array
    public function parseParts(array $itemData): array
}
```

**インターフェース**:
- 入力: CSVファイルパス
- 出力: 構造化された受注データオブジェクト

### 4. データ変換エンジン (DataTransformer)

**責務**: データの条件分岐処理、デフォルト値設定、表示制御

**公開メソッド**:
```php
class DataTransformer {
    public function applyConditionalDisplay(array $data, array $rules): array
    public function applyDefaultValues(array $data, array $defaults): array
    public function formatNumericValue(float $value, string $format): string
    public function addUnit(float $value, string $unit): string
}
```

**インターフェース**:
- 入力: パース済みデータ、変換ルール
- 出力: 変換済みデータ

### 5. 計算エンジン (CalculationEngine)

**責務**: 元データに存在しない計算値の算出

**公開メソッド**:
```php
class CalculationEngine {
    public function calculateTotalParts(array $items): int
    public function calculateTotalQuantity(array $items): float
    public function convertSize(float $value, string $fromUnit, string $toUnit): float
    public function applyConditionalCalculation(array $data, string $condition, callable $formula): float
}
```

**インターフェース**:
- 入力: 変換済みデータ、計算式定義
- 出力: 計算結果を含むデータ

### 6. PDF生成エンジン (PdfGenerator)

**責務**: mPDFを使用したPDF生成とレイアウト制御

**公開メソッド**:
```php
class PdfGenerator {
    public function generate(array $data, string $templatePath): string
    public function renderHeader(array $headerData): string
    public function renderItemBlock(array $item): string
    public function renderPartsTable(array $parts): string
    public function savePdf(string $content, string $outputPath): bool
}
```

**インターフェース**:
- 入力: 計算済みデータ、テンプレート定義
- 出力: PDFファイルパス

## データモデル

### OrderData（受注データ）

```php
class OrderData {
    public string $orderNumber;        // 受注番号
    public string $orderDate;          // 受注日
    public string $customerName;       // 顧客名
    public string $deliveryDate;       // 納期
    public array $items;               // アイテム配列
    public array $metadata;            // その他メタデータ
}
```

### Item（アイテム）

```php
class Item {
    public string $itemCode;           // アイテムコード
    public string $itemName;           // アイテム名
    public int $quantity;              // 数量
    public array $parts;               // パーツ配列
    public array $attributes;          // 属性（サイズ、色など）
}
```

### Part（パーツ）

```php
class Part {
    public string $partCode;           // パーツコード
    public string $partName;           // パーツ名
    public int $quantity;              // 数量
    public ?float $width;              // 幅（cm）
    public ?float $height;             // 高さ（cm）
    public array $specifications;      // 仕様詳細
}
```

### ValidationResult（バリデーション結果）

```php
class ValidationResult {
    public bool $isValid;              // バリデーション成功/失敗
    public array $errors;              // エラーメッセージ配列
}
```

### ParsedData（パース済みデータ）

```php
class ParsedData {
    public OrderData $order;           // 受注データ
    public array $rawData;             // 元のCSVデータ（デバッグ用）
}
```


## 正確性プロパティ

*プロパティとは、システムのすべての有効な実行において真であるべき特性または動作です。本質的には、システムが何をすべきかについての形式的な記述です。プロパティは、人間が読める仕様と機械で検証可能な正確性保証との橋渡しとなります。*

### プロパティリフレクション

事前分析を確認した結果、以下の冗長性を特定しました：

- **プロパティ2（CSV処理開始）とプロパティ3（PDF生成完了）**: これらは処理パイプライン全体の一部であり、「有効なCSV入力に対してPDFが生成される」という単一の包括的なプロパティに統合できます
- **プロパティ6（改行分割）とプロパティ7（カンマ分割）**: これらは両方ともセル内区切り文字の処理であり、「セル内区切り文字による要素抽出」という単一のプロパティに統合できます
- **プロパティ14（肯定キーワード表示）とプロパティ15（否定キーワード非表示）**: これらは条件分岐の表裏であり、「キーワードベースの表示制御」という単一のプロパティに統合できます

以下のプロパティは、上記の統合後の最終的なプロパティセットです。

### プロパティ1: CSVからPDFへの完全な変換

*任意の*有効なCSVファイルに対して、アップロードして処理すると、ダウンロード可能なPDFファイルが生成される
**検証対象: 要件 1.2, 1.3, 1.4**

### プロパティ2: 非CSV形式の拒否

*任意の*非CSV形式のファイル（例：.txt、.xlsx、.pdf）に対して、アップロードするとシステムはエラーメッセージを表示してファイルを拒否する
**検証対象: 要件 1.5**

### プロパティ3: セル内区切り文字による要素抽出

*任意の*CSVセルに改行またはカンマが含まれる場合、システムはそれらの区切り文字で分割された各要素を個別に抽出する
**検証対象: 要件 2.1, 2.2**

### プロパティ4: キーワードベースのデータ分類

*任意の*CSVセルに特定キーワード（「アイテム：」「パーツ：」など）が含まれる場合、システムはキーワードを基準にデータを正しく分類して抽出する
**検証対象: 要件 2.3**

### プロパティ5: 複数アイテムの個別認識

*任意の*受注データに含まれる複数のアイテムに対して、システムは各アイテムを個別のブロックとして認識し、パース結果のアイテム数が元データのアイテム数と一致する
**検証対象: 要件 2.4**

### プロパティ6: 複数パーツの個別認識

*任意の*アイテムに含まれる複数のパーツに対して、システムは各パーツを個別の要素として認識し、パース結果のパーツ数が元データのパーツ数と一致する
**検証対象: 要件 2.5**

### プロパティ7: PDF用紙サイズの一貫性

*任意の*受注データに対して、生成されるPDFはA4縦サイズ（210mm × 297mm）で出力される
**検証対象: 要件 3.1**

### プロパティ8: キーワードベースの表示制御

*任意の*CSVデータに肯定キーワード（「あり」「○」）が含まれる場合は対応項目が表示され、否定キーワード（「なし」「×」）が含まれる場合は対応項目が非表示になる
**検証対象: 要件 4.1, 4.2**

### プロパティ9: 条件分岐の優先順位適用

*任意の*データに複数の条件分岐ルールが適用される場合、システムは定義された優先順位に従って表示制御を実行し、最も優先度の高いルールの結果が反映される
**検証対象: 要件 4.4**

### プロパティ10: 数値への単位付与

*任意の*数値データ（例：36.0）に対して、システムは指定された単位（例：「タテ36cm」）を付与して表示し、元の数値が保持される
**検証対象: 要件 5.1**

### プロパティ11: 小数点丸め処理

*任意の*小数値に対して、指定された桁数で丸め処理を実行すると、結果は指定桁数の小数点以下を持つ
**検証対象: 要件 5.2**

### プロパティ12: フィールド別数値変換ルール適用

*任意の*複数フィールドを持つデータに対して、各フィールドに対応する数値変換ルールが正確に適用され、異なるフィールドのルールが混在しない
**検証対象: 要件 5.4**

### プロパティ13: パーツ数合計の正確性

*任意の*アイテムセットに対して、システムが計算するパーツ数の合計は、各アイテムのパーツ数を手動で合計した値と一致する
**検証対象: 要件 6.1**

### プロパティ14: 総数量計算の正確性

*任意の*アイテムセットに対して、システムが計算する総数量は、各アイテムの数量フィールドを手動で合計した値と一致する
**検証対象: 要件 6.2**

### プロパティ15: サイズ換算の正確性

*任意の*サイズ値に対して、システムが適用する換算式（例：cm→m、個→箱）の結果は、数学的に正しい換算値と一致する
**検証対象: 要件 6.3**

### プロパティ16: 条件付き計算の正確性

*任意の*データに対して、条件付き計算（例：特定条件下でのみ加算）を実行する場合、条件を満たすデータのみが計算に含まれ、条件を満たさないデータは除外される
**検証対象: 要件 6.4**

### プロパティ17: 異常値のエラー検出

*任意の*計算結果が数値範囲外または異常値（例：負の数量、極端に大きな値）である場合、システムはエラーメッセージを表示して処理を中断する
**検証対象: 要件 6.5**

### プロパティ18: 有効な認証情報でのログイン成功

*任意の*有効なユーザー名とパスワードの組み合わせに対して、ログインを試みるとシステムはユーザーをWebポータルにリダイレクトする
**検証対象: 要件 8.2**

### プロパティ19: 無効な認証情報でのログイン拒否

*任意の*無効なユーザー名またはパスワードに対して、ログインを試みるとシステムはエラーメッセージを表示してログインを拒否する
**検証対象: 要件 8.3**

### プロパティ20: ログアウト時のセッション終了

*任意の*認証済みセッションに対して、ログアウトを実行するとシステムはセッションを終了し、その後の認証が必要なページへのアクセスはログイン画面にリダイレクトされる
**検証対象: 要件 8.4**

### プロパティ21: 不正なCSV形式のエラー報告

*任意の*不正な形式のCSVファイル（例：必須フィールド欠落、不正な文字エンコーディング）に対して、システムは具体的なエラー内容（行番号、エラー種別）を含むエラーメッセージを表示する
**検証対象: 要件 10.1**

### プロパティ22: PDF生成エラーの適切な処理

*任意の*PDF生成中にエラーが発生するデータに対して、システムはエラーメッセージを表示して処理を中断し、不完全なPDFを出力しない
**検証対象: 要件 10.2**

### プロパティ23: システムエラーのログ記録

*任意の*システムエラーが発生した場合、システムはユーザーフレンドリーなエラーメッセージを表示すると同時に、詳細なエラー情報（スタックトレース、タイムスタンプ）をログファイルに記録する
**検証対象: 要件 10.4**

## エラーハンドリング

### エラー分類

システムは以下の3つのレベルでエラーを分類します：

1. **ユーザーエラー**: ユーザーの操作ミスや不正な入力
   - 例: 非CSV形式のファイルアップロード、空のCSVファイル、認証失敗
   - 対応: ユーザーフレンドリーなエラーメッセージを表示し、修正方法を提示

2. **データエラー**: CSVデータの形式や内容の問題
   - 例: 必須フィールド欠落、不正な文字エンコーディング、異常な数値
   - 対応: 具体的なエラー箇所（行番号、フィールド名）を示し、処理を中断

3. **システムエラー**: アプリケーション内部の予期しないエラー
   - 例: PDF生成ライブラリのエラー、ファイルシステムエラー、メモリ不足
   - 対応: 一般的なエラーメッセージを表示し、詳細をログに記録

### エラーハンドリング戦略

```php
try {
    // メイン処理
    $csvData = $csvParser->parse($filePath);
    $transformedData = $dataTransformer->transform($csvData);
    $calculatedData = $calculationEngine->calculate($transformedData);
    $pdfPath = $pdfGenerator->generate($calculatedData);
    
} catch (ValidationException $e) {
    // ユーザーエラー・データエラー
    $logger->warning('Validation error', ['error' => $e->getMessage()]);
    return new ErrorResponse($e->getMessage(), 400);
    
} catch (PdfGenerationException $e) {
    // PDF生成エラー
    $logger->error('PDF generation failed', ['error' => $e->getMessage()]);
    return new ErrorResponse('PDFの生成に失敗しました。データを確認してください。', 500);
    
} catch (Exception $e) {
    // システムエラー
    $logger->critical('Unexpected error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    return new ErrorResponse('システムエラーが発生しました。管理者に連絡してください。', 500);
}
```

### ログ記録

- **ログレベル**: DEBUG, INFO, WARNING, ERROR, CRITICAL
- **ログ出力先**: ファイル（logs/app.log）
- **ログローテーション**: 日次、最大7日分保持
- **ログ内容**: タイムスタンプ、ログレベル、メッセージ、コンテキスト情報

## テスト戦略

### 二重テストアプローチ

本システムでは、ユニットテストとプロパティベーステスト（PBT）の両方を実施します。これらは相補的であり、両方を含める必要があります：

- **ユニットテスト**: 特定の例、エッジケース、エラー条件を検証
- **プロパティベーステスト**: すべての入力に対して成り立つべき普遍的なプロパティを検証

両者を組み合わせることで包括的なカバレッジを実現します。ユニットテストは具体的なバグを捕捉し、プロパティテストは一般的な正確性を検証します。

### ユニットテスト

**対象**:
- 各コンポーネントの公開メソッド
- エッジケース（空のCSV、巨大なファイル、特殊文字）
- エラー条件（不正な入力、ファイルシステムエラー）
- 統合ポイント（コンポーネント間の連携）

**ツール**: PHPUnit 9.x以上

**例**:
```php
public function testCsvParserHandlesEmptyFile(): void
{
    $parser = new CsvParser();
    $this->expectException(ValidationException::class);
    $parser->parse('empty.csv');
}

public function testDataTransformerAppliesDefaultValue(): void
{
    $transformer = new DataTransformer();
    $data = ['field1' => ''];
    $result = $transformer->applyDefaultValues($data, ['field1' => '-']);
    $this->assertEquals('-', $result['field1']);
}
```

### プロパティベーステスト

**ライブラリ**: Eris（PHP用プロパティベーステストライブラリ）

**設定**: 各プロパティベーステストは最低100回の反復を実行

**タグ付け**: 各プロパティベーステストには、設計書の正確性プロパティを参照するコメントを付与
- フォーマット: `// Feature: picking-report-generator, Property {番号}: {プロパティテキスト}`

**例**:
```php
// Feature: picking-report-generator, Property 3: セル内区切り文字による要素抽出
public function testCellSplittingProperty(): void
{
    $this->forAll(
        Generator\string(),
        Generator\elements(["\n", ","])
    )->then(function ($content, $delimiter) {
        $cell = implode($delimiter, array_fill(0, 3, $content));
        $parser = new CsvParser();
        $result = $parser->splitCellByDelimiter($cell, $delimiter);
        
        // プロパティ: 分割結果の要素数は区切り文字の数+1と一致する
        $this->assertCount(3, $result);
    });
}

// Feature: picking-report-generator, Property 13: パーツ数合計の正確性
public function testTotalPartsCalculationProperty(): void
{
    $this->forAll(
        Generator\seq(Generator\associative([
            'parts' => Generator\seq(Generator\associative([
                'quantity' => Generator\nat()
            ]))
        ]))
    )->then(function ($items) {
        $calculator = new CalculationEngine();
        $calculatedTotal = $calculator->calculateTotalParts($items);
        
        // プロパティ: 計算された合計は手動合計と一致する
        $manualTotal = array_sum(array_map(fn($item) => count($item['parts']), $items));
        $this->assertEquals($manualTotal, $calculatedTotal);
    });
}
```

### 統合テスト

**対象**: エンドツーエンドのワークフロー
- CSVアップロード → パース → 変換 → 計算 → PDF生成 → ダウンロード

**アプローチ**: 実際のCSVサンプルファイルを使用し、生成されたPDFの存在と基本的な属性（ファイルサイズ、ページ数）を検証

### テスト実行

```bash
# ユニットテストのみ実行
vendor/bin/phpunit tests/Unit

# プロパティベーステストのみ実行
vendor/bin/phpunit tests/Property

# すべてのテスト実行
vendor/bin/phpunit
```

## セキュリティ考慮事項

### 認証とセッション管理

- **パスワードハッシュ化**: bcryptアルゴリズム（PHPのpassword_hash関数）
- **セッション管理**: PHPネイティブセッション、HTTPOnlyフラグ有効化
- **セッションタイムアウト**: 30分の非アクティブ後に自動ログアウト

### ファイルアップロードセキュリティ

- **ファイルタイプ検証**: MIMEタイプとファイル拡張子の両方をチェック
- **ファイルサイズ制限**: 最大10MB
- **ファイル名サニタイゼーション**: 特殊文字の除去、ランダムなファイル名の生成
- **アップロードディレクトリ**: Webルート外に配置、直接アクセス不可

### インジェクション対策

- **CSVインジェクション**: セル内の数式（=、+、-、@で始まる）をエスケープ
- **パストラバーサル**: ファイルパスの検証、相対パス記号（..、./）の除去

### エラー情報の漏洩防止

- **本番環境**: 詳細なエラーメッセージを表示せず、一般的なメッセージのみ
- **開発環境**: 詳細なエラー情報とスタックトレースを表示
- **ログ**: 機密情報（パスワード、個人情報）をログに記録しない

## パフォーマンス考慮事項

### ファイル処理

- **ストリーミング処理**: 大きなCSVファイルは行ごとに読み込み、メモリ使用量を抑制
- **一時ファイルのクリーンアップ**: 処理完了後、24時間以内に一時ファイルを自動削除

### PDF生成

- **メモリ制限**: PHP memory_limitを256MB以上に設定
- **タイムアウト**: PHP max_execution_timeを60秒以上に設定
- **キャッシング**: 同一CSVからの再生成時、キャッシュされたPDFを返す（オプション）

### 同時実行

- **ファイルロック**: 同一ファイルへの同時書き込みを防止
- **プロセス分離**: 各リクエストは独立したプロセスで処理

## 拡張性設計

### テンプレートシステム

新しい帳票種類を追加する際の設計：

```php
interface ReportTemplate {
    public function getLayout(): array;
    public function getStyles(): array;
    public function render(array $data): string;
}

class PickingReportTemplate implements ReportTemplate {
    // 現在のピッキング帳票の実装
}

class InvoiceReportTemplate implements ReportTemplate {
    // 将来の請求書帳票の実装
}
```

### プラグインアーキテクチャ

将来の機能拡張（QRコード、バーコード）のための設計：

```php
interface PdfPlugin {
    public function apply(Mpdf $pdf, array $data): void;
}

class QrCodePlugin implements PdfPlugin {
    public function apply(Mpdf $pdf, array $data): void {
        // QRコード埋め込みロジック
    }
}

$pdfGenerator->addPlugin(new QrCodePlugin());
```

### 設定駆動型ルール

CSV解析ルール、変換ルール、計算ルールを外部設定ファイル（YAML/JSON）で管理：

```yaml
# config/parsing_rules.yaml
fields:
  - name: item_name
    delimiter: "\n"
    keyword: "アイテム："
  - name: part_name
    delimiter: ","
    keyword: "パーツ："

transformations:
  - field: size
    format: "タテ{value}cm"
    unit: "cm"
  - field: quantity
    default: "0"
    rounding: 0

calculations:
  - name: total_parts
    formula: "sum(items.*.parts.count)"
  - name: total_quantity
    formula: "sum(items.*.quantity)"
```

この設計により、コードを変更せずに新しいルールを追加・変更できます。
