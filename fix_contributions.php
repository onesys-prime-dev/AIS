<?php
/**
 * Fix government contribution computations:
 *  1. SSS sssEE field: show TOTAL (regular + WISP), not just regular
 *  2. PhilHealth: per-period = monthly EE / 2 (1.25% per semi-monthly)
 *  3. HDMF: per-period = monthly / 2 = ₱50 max
 *  4. JS SSS table: extend from 29,500 to 35,000 (2025 SSS Circular)
 *  5. DB SSS table: same extension
 *  6. PHP service: PhilHealth and HDMF return per-period amounts
 */

$file = 'app/Views/client_portal/payroll_management.php';
$src  = file_get_contents($file);
if ($src === false) die("Cannot read $file\n");

// Detect the line ending used at the JS section for SSS table (LF-only there)
$sssPos = strpos($src, '27000,750');
$chunk  = substr($src, $sssPos, 200);
$jsN    = (strpos($chunk, "\r\n") !== false) ? "\r\n" : "\n";  // LF in JS section

// Detect general CRLF for PHP/template sections
$n = "\r\n"; // file header uses CRLF

// ─── FIX 1+2+3: updateContributions ─────────────────────────────────────────
// Use CRLF (template section)
$old1 = '    // Pag-IBIG: 2% of monthly salary, employee mandatory cap ₱100 per semi-monthly period'.$n
      . '    const hdmf = Math.min(100, monthlySalary * 0.02);'.$n
      . $n
      . '    document.getElementById(\'sssEE\').value    = sssReg.toFixed(2);'.$n
      . '    if (document.getElementById(\'sssWisp\'))  document.getElementById(\'sssWisp\').value  = sssWisp.toFixed(2);'.$n
      . '    if (document.getElementById(\'sssTotal\')) document.getElementById(\'sssTotal\').value = sss.toFixed(2);'.$n
      . '    document.getElementById(\'phicEE\').value   = phic.toFixed(2);'.$n
      . '    document.getElementById(\'hdmfEE\').value   = hdmf.toFixed(2);'.$n
      . $n
      . '    return { sss, phic, hdmf };'.$n
      . '}';

$new1 = '    // Pag-IBIG: monthly max ₱100; per semi-monthly period = ₱50'.$n
      . '    const hdmfMonthly = Math.min(100, monthlySalary * 0.02);'.$n
      . '    const hdmf = hdmfMonthly / 2;'.$n
      . $n
      . '    // PhilHealth: monthly EE = 2.5% of salary; per semi-monthly period = 1.25%'.$n
      . '    const phicPerPeriod = phic / 2;'.$n
      . $n
      . '    // SSS: sssEE shows TOTAL (regular + WISP) — full amount deducted from employee'.$n
      . '    document.getElementById(\'sssEE\').value    = sss.toFixed(2);'.$n
      . '    if (document.getElementById(\'sssWisp\'))  document.getElementById(\'sssWisp\').value  = sssWisp.toFixed(2);'.$n
      . '    if (document.getElementById(\'sssTotal\')) document.getElementById(\'sssTotal\').value = sss.toFixed(2);'.$n
      . '    document.getElementById(\'phicEE\').value   = phicPerPeriod.toFixed(2);'.$n
      . '    document.getElementById(\'hdmfEE\').value   = hdmf.toFixed(2);'.$n
      . $n
      . '    return { sss, phic: phicPerPeriod, hdmf };'.$n
      . '}';

if (strpos($src, $old1) === false) die("FAIL fix1-3\n");
$src = str_replace($old1, $new1, $src);
echo "Fix 1-3 OK: SSS total, PhilHealth /2, HDMF /2.\n";

// ─── FIX 4: Extend JS SSS table to 35,000 (LF line endings in that section) ──
$old4 = '        [27000,750,600],[27500,750,625],[28000,750,650],[28500,750,675],[29000,750,700],[29500,750,725]'.$jsN
      . '    ];';

$new4 = '        [27000,750,600],[27500,750,625],[28000,750,650],[28500,750,675],[29000,750,700],[29500,750,725],'.$jsN
      . '        [30000,750,750],[30500,750,775],[31000,750,800],[31500,750,825],[32000,750,850],[32500,750,875],'.$jsN
      . '        [33000,750,900],[33500,750,925],[34000,750,950],[34500,750,975],[35000,750,1000]'.$jsN
      . '    ];';

if (strpos($src, $old4) === false) die("FAIL fix4: SSS table end not found (jsN=".bin2hex($jsN).")\n");
$src = str_replace($old4, $new4, $src);
echo "Fix 4 OK: JS SSS table extended to 35,000.\n";

file_put_contents($file, $src);
echo "Saved $file\n\n";

// ─── FIX 5: Extend DB SSS table to 35,000 ─────────────────────────────────────
$db = new mysqli('127.0.0.1', 'root', '', 'ais');
if ($db->connect_errno) die("DB error: ".$db->connect_error."\n");

$r    = $db->query("SELECT effective_year, is_active FROM sss_brackets WHERE range_start=29500 ORDER BY effective_year DESC LIMIT 1");
$row  = $r->fetch_assoc();
if (!$row) die("FAIL fix5: 29,500 bracket missing.\n");
$year = $row['effective_year'];
$act  = $row['is_active'];

// Close existing open-ended bracket at 29,500
$db->query("UPDATE sss_brackets SET range_end=29999.99 WHERE range_start=29500 AND effective_year=$year AND range_end IS NULL");
echo "Fix 5a OK: 29,500 bracket capped at 29,999.99 (affected ".$db->affected_rows." row).\n";

// Check if 30,000 already exists
$r2  = $db->query("SELECT COUNT(*) n FROM sss_brackets WHERE range_start=30000 AND effective_year=$year");
if ($r2->fetch_assoc()['n'] > 0) {
    echo "Fix 5b SKIP: 30,000+ brackets already present.\n";
} else {
    // Get the sss_brackets table columns to insert correctly
    $wisp  = 750;
    $count = 0;
    for ($start = 30000; $start <= 35000; $start += 500) {
        $end   = ($start < 35000) ? ($start + 499.99) : 'NULL';
        $endV  = ($end === 'NULL') ? 'NULL' : $end;
        $sql = "INSERT INTO sss_brackets (effective_year, is_active, range_start, range_end, ee_regular, ee_wisp, er_regular, er_ec, er_wisp)
                VALUES ($year, $act, $start, ".($endV==='NULL'?'NULL':"'$endV'").", 750.00, $wisp.00, 1500.00, 30.00, $wisp.00)";
        if (!$db->query($sql)) echo "  Row $start failed: ".$db->error."\n";
        else $count++;
        $wisp += 25;
    }
    echo "Fix 5b OK: Inserted $count brackets (30,000–35,000).\n";
}
$db->close();

// ─── FIX 6: PHP service — per-period amounts ──────────────────────────────────
$svcFile = 'app/Services/Client/PayrollComputationService.php';
$svc     = file_get_contents($svcFile);
if ($svc === false) die("Cannot read $svcFile\n");
$svcN    = (strpos($svc, "\r\n") !== false) ? "\r\n" : "\n";

// PhilHealth
$oldPhic = '        $cappedSalary  = min($monthlySalary, $cap);'.$svcN
         . '        $employeeShare = ($cappedSalary * $rate) / 2;   // total premium split 50/50'.$svcN
         . '        return round($employeeShare, 2);';
$newPhic = '        $cappedSalary  = min($monthlySalary, $cap);'.$svcN
         . '        // Monthly EE share = 50% of total premium (2.5% of salary).'.$svcN
         . '        // Per semi-monthly period = half of monthly EE share (1.25%).'.$svcN
         . '        $employeeShare = ($cappedSalary * $rate) / 2;'.$svcN
         . '        return round($employeeShare / 2, 2);';

if (strpos($svc, $oldPhic) !== false) {
    $svc = str_replace($oldPhic, $newPhic, $svc);
    echo "Fix 6a OK: PhilHealth returns per-period amount.\n";
} else {
    echo "WARN fix6a: PhilHealth block not found.\n";
}

// HDMF
$oldHdmf = '        return min(round($monthlySalary * $rate, 2), $max);';
$newHdmf = '        // Monthly HDMF max = ₱100; per semi-monthly period = ₱50.'.$svcN
         . '        return round(min($monthlySalary * $rate, $max) / 2, 2);';

if (strpos($svc, $oldHdmf) !== false) {
    $svc = str_replace($oldHdmf, $newHdmf, $svc);
    echo "Fix 6b OK: HDMF returns per-period amount.\n";
} else {
    echo "WARN fix6b: HDMF block not found.\n";
}

file_put_contents($svcFile, $svc);
echo "Saved $svcFile\n\n";
echo "All contribution fixes applied.\n";
