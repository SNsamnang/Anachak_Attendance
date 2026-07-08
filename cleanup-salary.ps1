# PowerShell cleanup script for SalarySummary files

Write-Host "Deleting SalarySummary related files..."

$filesToDelete = @(
    "app\Services\SalarySummaryService.php",
    "app\Models\SalarySummary.php",
    "app\Http\Controllers\SalarySummaryController.php",
    "app\Console\Commands\SyncSalarySummaries.php",
    "app\Console\Commands\RegenerateSalarySummaries.php",
    "app\Console\Commands\GenerateDailySalarySummary.php",
    "app\Console\Commands\DebugSalarySummary.php",
    "database\migrations\2026_06_12_000000_create_salary_summaries_table.php"
)

foreach ($file in $filesToDelete) {
    if (Test-Path $file) {
        Remove-Item $file -Force
        Write-Host "✓ Deleted: $file"
    }
}

# Delete folder
if (Test-Path "resources\views\salary-summaries") {
    Remove-Item "resources\views\salary-summaries" -Recurse -Force
    Write-Host "✓ Deleted: resources\views\salary-summaries\"
}

Write-Host "`n✓ Cleanup complete! All SalarySummary files have been removed."
