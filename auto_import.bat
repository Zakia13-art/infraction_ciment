@echo off
REM Wialon Auto Import - executed every 48h
REM Imports last 2 days: before yesterday + yesterday

set "FROM="
set "TO="
for /f "usebackq" %%d in (`powershell -command "(Get-Date).AddDays(-2).ToString('yyyy-MM-dd')"`) do set FROM=%%d
for /f "usebackq" %%d in (`powershell -command "(Get-Date).AddDays(-1).ToString('yyyy-MM-dd')"`) do set TO=%%d

echo [%date% %time%] Import Wialon %FROM% vers %TO% >> C:\xampp\htdocs\infraction_ciment\import_log.txt

curl -s -o C:\xampp\htdocs\infraction_ciment\last_import_result.html "http://localhost/infraction_ciment/WialonImport.php?from=%FROM%&to=%TO%" >> C:\xampp\htdocs\infraction_ciment\import_log.txt 2>&1

echo [%date% %time%] Terminé >> C:\xampp\htdocs\infraction_ciment\import_log.txt
echo. >> C:\xampp\htdocs\infraction_ciment\import_log.txt
