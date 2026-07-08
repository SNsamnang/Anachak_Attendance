@echo off
REM Delete all SalarySummary related files

echo Deleting SalarySummary files...

del "app\Services\SalarySummaryService.php"
del "app\Models\SalarySummary.php"
del "app\Http\Controllers\SalarySummaryController.php"
del "app\Console\Commands\SyncSalarySummaries.php"
del "app\Console\Commands\RegenerateSalarySummaries.php"
del "app\Console\Commands\GenerateDailySalarySummary.php"
del "app\Console\Commands\DebugSalarySummary.php"
del "database\migrations\2026_06_12_000000_create_salary_summaries_table.php"

REM Delete folder
rmdir /s /q "resources\views\salary-summaries"

echo Cleanup complete! All SalarySummary files have been removed.
