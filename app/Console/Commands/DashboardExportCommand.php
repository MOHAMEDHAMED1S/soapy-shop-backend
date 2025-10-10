<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DashboardExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:export {--type=overview} {--period=30} {--format=json} {--output=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export dashboard data';

    protected AnalyticsService $analyticsService;

    /**
     * Create a new command instance.
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $period = $this->option('period');
        $format = $this->option('format');
        $output = $this->option('output');

        $this->info("📊 Exporting dashboard data...");
        $this->info("Type: {$type}, Period: {$period} days, Format: {$format}");

        try {
            $data = $this->analyticsService->exportData($type, $period, $format);
            
            if ($output) {
                $this->exportToFile($data, $output, $format);
            } else {
                $this->displayData($data, $format);
            }

            $this->info("✅ Export completed successfully!");

        } catch (\Exception $e) {
            $this->error('❌ Error exporting data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Export data to file
     */
    protected function exportToFile(array $data, string $output, string $format)
    {
        $filename = $output ?: "dashboard_export_{$data['type']}_{$data['period']}d_" . now()->format('Y-m-d_H-i-s');
        
        switch ($format) {
            case 'json':
                $filename .= '.json';
                $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                break;
            case 'csv':
                $filename .= '.csv';
                $content = $this->convertToCsv($data);
                break;
            case 'xlsx':
                $filename .= '.xlsx';
                $content = $this->convertToXlsx($data);
                break;
            default:
                $filename .= '.txt';
                $content = $this->convertToText($data);
        }

        Storage::disk('public')->put("exports/{$filename}", $content);
        
        $this->info("📁 File saved: storage/app/public/exports/{$filename}");
    }

    /**
     * Display data in console
     */
    protected function displayData(array $data, string $format)
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
            case 'csv':
                $this->line($this->convertToCsv($data));
                break;
            case 'text':
                $this->line($this->convertToText($data));
                break;
            default:
                $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Convert data to CSV format
     */
    protected function convertToCsv(array $data): string
    {
        $csv = '';
        
        // Add metadata
        $csv .= "Export Type,{$data['type']}\n";
        $csv .= "Period,{$data['period']} days\n";
        $csv .= "Exported At,{$data['exported_at']}\n";
        $csv .= "Total Records,{$data['total_records']}\n\n";

        // Add data
        if (isset($data['data']) && is_array($data['data'])) {
            $this->arrayToCsv($data['data'], $csv);
        }

        return $csv;
    }

    /**
     * Convert array to CSV
     */
    protected function arrayToCsv(array $array, string &$csv, string $prefix = '')
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    // Numeric array - treat as rows
                    $csv .= implode(',', array_map([$this, 'escapeCsvValue'], $value)) . "\n";
                } else {
                    // Associative array - treat as key-value pairs
                    $csv .= "{$prefix}{$key}," . $this->escapeCsvValue($value) . "\n";
                }
            } else {
                $csv .= "{$prefix}{$key}," . $this->escapeCsvValue($value) . "\n";
            }
        }
    }

    /**
     * Escape CSV value
     */
    protected function escapeCsvValue($value): string
    {
        if (is_array($value)) {
            return '"' . str_replace('"', '""', json_encode($value)) . '"';
        }
        
        $value = (string) $value;
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        
        return $value;
    }

    /**
     * Convert data to XLSX format (simplified)
     */
    protected function convertToXlsx(array $data): string
    {
        // For simplicity, we'll create a CSV file with .xlsx extension
        // In a real application, you would use a library like PhpSpreadsheet
        return $this->convertToCsv($data);
    }

    /**
     * Convert data to text format
     */
    protected function convertToText(array $data): string
    {
        $text = "Dashboard Export Report\n";
        $text .= "=====================\n\n";
        $text .= "Export Type: {$data['type']}\n";
        $text .= "Period: {$data['period']} days\n";
        $text .= "Exported At: {$data['exported_at']}\n";
        $text .= "Total Records: {$data['total_records']}\n\n";

        if (isset($data['data']) && is_array($data['data'])) {
            $text .= "Data:\n";
            $text .= "-----\n";
            $this->arrayToText($data['data'], $text);
        }

        return $text;
    }

    /**
     * Convert array to text
     */
    protected function arrayToText(array $array, string &$text, int $indent = 0)
    {
        $spaces = str_repeat('  ', $indent);
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $text .= "{$spaces}{$key}:\n";
                $this->arrayToText($value, $text, $indent + 1);
            } else {
                $text .= "{$spaces}{$key}: {$value}\n";
            }
        }
    }
}
