<?php
/**
 * PDF Generator
 * 
 * Generates PDF documents from order data using mPDF.
 */

declare(strict_types=1);

namespace PickingReport\Generators;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use PickingReport\Models\OrderData;
use PickingReport\Models\Item;
use PickingReport\Models\Part;
use PickingReport\Exceptions\PdfGenerationException;

class PdfGenerator
{
    private string $outputDir;
    private string $paperSize;
    private string $orientation;

    public function __construct(
        string $outputDir = './storage/pdf',
        string $paperSize = 'A4',
        string $orientation = 'P'
    ) {
        $this->outputDir = $outputDir;
        $this->paperSize = $paperSize;
        $this->orientation = $orientation;
        
        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0775, true);
        }
    }

    /**
     * Generate PDF from order data
     * 
     * @param OrderData $data Order data to generate PDF from
     * @param string|null $templatePath Optional template path
     * @return string Path to generated PDF file
     * @throws PdfGenerationException
     */
    public function generate(OrderData $data, ?string $templatePath = null): string
    {
        try {
            // Create mPDF instance with Japanese font support
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => $this->paperSize,
                'orientation' => $this->orientation,
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_header' => 10,
                'margin_footer' => 10,
                'default_font' => 'dejavusanscondensed',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);
            
            // Set font substitutions for better Japanese support
            $mpdf->SetDefaultBodyCSS('font-family', 'dejavusanscondensed');

            // Build HTML content
            $html = $this->buildHtml($data);
            
            // Write HTML to PDF
            $mpdf->WriteHTML($html);
            
            // Generate output filename
            $filename = $this->generateFilename($data);
            $outputPath = $this->outputDir . '/' . $filename;
            
            // Save PDF
            $mpdf->Output($outputPath, 'F');
            
            return $outputPath;
            
        } catch (MpdfException $e) {
            throw new PdfGenerationException(
                "Failed to generate PDF: " . $e->getMessage(),
                0,
                $e
            );
        } catch (\Exception $e) {
            throw new PdfGenerationException(
                "Unexpected error during PDF generation: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Build complete HTML for PDF
     */
    private function buildHtml(OrderData $data): string
    {
        $html = '<!DOCTYPE html>';
        $html .= '<html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= $this->getStyles();
        $html .= '</head><body>';
        
        // Header
        $html .= $this->renderHeader($data);
        
        // Items
        foreach ($data->getItems() as $index => $item) {
            $html .= $this->renderItemBlock($item, $index + 1);
        }
        
        // Footer with totals
        $html .= $this->renderFooter($data);
        
        $html .= '</body></html>';
        
        return $html;
    }

    /**
     * Get CSS styles for PDF
     */
    private function getStyles(): string
    {
        return '<style>
            body {
                font-family: "DejaVu Sans", "Arial Unicode MS", sans-serif;
                font-size: 10pt;
                line-height: 1.4;
            }
            .header {
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .header-title {
                font-size: 16pt;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .header-info {
                display: table;
                width: 100%;
            }
            .header-row {
                display: table-row;
            }
            .header-label {
                display: table-cell;
                font-weight: bold;
                width: 120px;
                padding: 3px 0;
            }
            .header-value {
                display: table-cell;
                padding: 3px 0;
            }
            .item-block {
                margin-bottom: 25px;
                page-break-inside: avoid;
            }
            .item-header {
                background-color: #f0f0f0;
                padding: 8px;
                font-weight: bold;
                font-size: 11pt;
                border: 1px solid #333;
                margin-bottom: 5px;
            }
            .parts-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 5px;
            }
            .parts-table th {
                background-color: #e0e0e0;
                border: 1px solid #333;
                padding: 6px;
                text-align: left;
                font-weight: bold;
            }
            .parts-table td {
                border: 1px solid #333;
                padding: 6px;
            }
            .parts-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .footer {
                margin-top: 30px;
                border-top: 2px solid #333;
                padding-top: 10px;
            }
            .footer-totals {
                font-size: 11pt;
                font-weight: bold;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
        </style>';
    }

    /**
     * Render header section with order information
     */
    public function renderHeader(OrderData $data): string
    {
        $html = '<div class="header">';
        $html .= '<div class="header-title">ピッキング帳票</div>';
        $html .= '<div class="header-info">';
        
        $html .= '<div class="header-row">';
        $html .= '<div class="header-label">受注番号:</div>';
        $html .= '<div class="header-value">' . htmlspecialchars($data->getOrderNumber()) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="header-row">';
        $html .= '<div class="header-label">受注日:</div>';
        $html .= '<div class="header-value">' . htmlspecialchars($data->getOrderDate()) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="header-row">';
        $html .= '<div class="header-label">顧客名:</div>';
        $html .= '<div class="header-value">' . htmlspecialchars($data->getCustomerName()) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="header-row">';
        $html .= '<div class="header-label">納期:</div>';
        $html .= '<div class="header-value">' . htmlspecialchars($data->getDeliveryDate()) . '</div>';
        $html .= '</div>';
        
        $html .= '</div>'; // header-info
        $html .= '</div>'; // header
        
        return $html;
    }

    /**
     * Render item block with parts table
     */
    public function renderItemBlock(Item $item, int $itemNumber): string
    {
        $html = '<div class="item-block">';
        
        // Item header
        $html .= '<div class="item-header">';
        $html .= 'アイテム ' . $itemNumber . ': ' . htmlspecialchars($item->getItemName());
        $html .= ' (コード: ' . htmlspecialchars($item->getItemCode()) . ')';
        $html .= ' - 数量: ' . $item->getQuantity();
        $html .= '</div>';
        
        // Parts table
        if (!empty($item->getParts())) {
            $html .= $this->renderPartsTable($item->getParts());
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render parts table
     * 
     * @param Part[] $parts Array of parts
     */
    public function renderPartsTable(array $parts): string
    {
        $html = '<table class="parts-table">';
        
        // Table header
        $html .= '<thead><tr>';
        $html .= '<th style="width: 15%;">パーツコード</th>';
        $html .= '<th style="width: 30%;">パーツ名</th>';
        $html .= '<th style="width: 10%;" class="text-center">数量</th>';
        $html .= '<th style="width: 15%;" class="text-right">幅</th>';
        $html .= '<th style="width: 15%;" class="text-right">高さ</th>';
        $html .= '<th style="width: 15%;">備考</th>';
        $html .= '</tr></thead>';
        
        // Table body
        $html .= '<tbody>';
        foreach ($parts as $part) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($part->getPartCode()) . '</td>';
            $html .= '<td>' . htmlspecialchars($part->getPartName()) . '</td>';
            $html .= '<td class="text-center">' . $part->getQuantity() . '</td>';
            
            // Width
            $width = $part->getSpecification('formatted_width') 
                ?? ($part->getWidth() !== null ? $part->getWidth() . 'cm' : '-');
            $html .= '<td class="text-right">' . htmlspecialchars($width) . '</td>';
            
            // Height
            $height = $part->getSpecification('formatted_height') 
                ?? ($part->getHeight() !== null ? $part->getHeight() . 'cm' : '-');
            $html .= '<td class="text-right">' . htmlspecialchars($height) . '</td>';
            
            // Notes
            $notes = $part->getSpecification('notes') ?? '-';
            $html .= '<td>' . htmlspecialchars($notes) . '</td>';
            
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        
        $html .= '</table>';
        
        return $html;
    }

    /**
     * Render footer with totals
     */
    private function renderFooter(OrderData $data): string
    {
        $html = '<div class="footer">';
        $html .= '<div class="footer-totals">';
        
        $totalParts = $data->getMetadataValue('total_parts');
        $totalQuantity = $data->getMetadataValue('total_quantity');
        
        if ($totalParts !== null) {
            $html .= '<div>総パーツ数: ' . $totalParts . '</div>';
        }
        
        if ($totalQuantity !== null) {
            $html .= '<div>総数量: ' . $totalQuantity . '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Save PDF to file
     * 
     * @param string $content PDF content
     * @param string $outputPath Output file path
     * @return bool Success status
     */
    public function savePdf(string $content, string $outputPath): bool
    {
        $result = file_put_contents($outputPath, $content);
        return $result !== false;
    }

    /**
     * Generate filename for PDF
     */
    private function generateFilename(OrderData $data): string
    {
        $orderNumber = preg_replace('/[^a-zA-Z0-9-_]/', '_', $data->getOrderNumber());
        $timestamp = date('YmdHis');
        return "picking_report_{$orderNumber}_{$timestamp}.pdf";
    }

    /**
     * Get output directory
     */
    public function getOutputDir(): string
    {
        return $this->outputDir;
    }
}
