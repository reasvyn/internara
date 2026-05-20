<?php

declare(strict_types=1);

// ====== Run only when invoked directly (not via require/include) ======
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'fix-audit-calls.php') {
    main();

    return;
}

function main(): void
{
    $files = [
        'app/Domain/Certificate/Actions/IssueCertificateAction.php',
        'app/Domain/Certificate/Actions/CreateCertificateTemplateAction.php',
        'app/Domain/Certificate/Actions/RevokeCertificateAction.php',
        'app/Domain/Partnership/Actions/TerminatePartnershipAction.php',
        'app/Domain/Partnership/Actions/CreatePartnershipAction.php',
        'app/Domain/Partnership/Actions/CreateCompanyAction.php',
        'app/Domain/Partnership/Actions/RenewPartnershipAction.php',
        'app/Domain/Partnership/Actions/UpdatePartnershipAction.php',
        'app/Domain/Partnership/Actions/DeletePartnershipAction.php',
        'app/Domain/Partnership/Actions/DeleteCompanyAction.php',
        'app/Domain/Partnership/Actions/UpdateCompanyAction.php',
        'app/Domain/Mentor/Actions/CreateMentorAction.php',
        'app/Domain/Mentor/Actions/UpdateMentorAction.php',
        'app/Domain/Mentor/Actions/VerifySupervisionLogAction.php',
        'app/Domain/Mentor/Actions/CreateSupervisionLogAction.php',
        'app/Domain/Mentor/Actions/DeleteMentorAction.php',
        'app/Domain/Mentee/Actions/CreateMenteeAction.php',
        'app/Domain/Mentee/Actions/DeleteMenteeAction.php',
        'app/Domain/Mentee/Actions/UpdateMenteeAction.php',
        'app/Domain/Attendance/Actions/ClockOutAction.php',
        'app/Domain/Attendance/Actions/ClockInAction.php',
        'app/Domain/Attendance/Actions/VerifyAttendanceAction.php',
        'app/Domain/Attendance/Actions/SubmitAbsenceAction.php',
        'app/Domain/Attendance/Actions/UpdateAttendanceAction.php',
        'app/Domain/Attendance/Actions/DeleteAttendanceAction.php',
        'app/Domain/Attendance/Actions/CreateAttendanceAction.php',
        'app/Domain/Attendance/Actions/ProcessAbsenceAction.php',
        'app/Domain/Schedule/Actions/CreateScheduleAction.php',
        'app/Domain/Schedule/Actions/UpdateScheduleAction.php',
        'app/Domain/Schedule/Actions/DeleteScheduleAction.php',
        'app/Domain/Logbook/Actions/DeleteLogbookAction.php',
        'app/Domain/Logbook/Actions/UpdateLogbookAction.php',
        'app/Domain/Logbook/Actions/SubmitLogbookAction.php',
        'app/Domain/Logbook/Actions/CreateLogbookAction.php',
        'app/Domain/Guidance/Actions/CreateHandbookAction.php',
        'app/Domain/Guidance/Actions/AcknowledgeHandbookAction.php',
        'app/Domain/Admin/Console/Commands/AutoInactivateAccounts.php',
        'app/Domain/Document/Actions/RenderDocumentAction.php',
        'app/Domain/School/Actions/UpdateAcademicYearAction.php',
        'app/Domain/School/Actions/UpdateSchoolAction.php',
        'app/Domain/School/Actions/DeleteDepartmentAction.php',
        'app/Domain/School/Actions/UpdateDepartmentAction.php',
        'app/Domain/School/Actions/DeleteAcademicYearAction.php',
        'app/Domain/School/Actions/CreateDepartmentAction.php',
        'app/Domain/Internship/Actions/SubmitReportAction.php',
        'app/Domain/Internship/Actions/AddSupervisorReportNotesAction.php',
        'app/Domain/Internship/Actions/CreateReportAction.php',
        'app/Domain/Internship/Actions/CreateBriefingAction.php',
        'app/Domain/Internship/Actions/CreateInternshipAction.php',
        'app/Domain/Internship/Actions/RequestReportRevisionAction.php',
        'app/Domain/Internship/Actions/DeleteInternshipAction.php',
        'app/Domain/Internship/Actions/RecordBriefingAttendanceAction.php',
        'app/Domain/Internship/Actions/UpdateInternshipAction.php',
        'app/Domain/Internship/Actions/ApproveReportAction.php',
        'app/Domain/Internship/Actions/OverrideBriefingAttendanceAction.php',
        'app/Domain/Incident/Actions/ResolveIncidentAction.php',
        'app/Domain/Incident/Actions/UpdateIncidentAction.php',
        'app/Domain/Incident/Actions/ReportIncidentAction.php',
        'app/Domain/Setup/Actions/SetupDepartmentAction.php',
        'app/Domain/Setup/Actions/SetupSchoolAction.php',
        'app/Domain/Setup/Actions/SetupSuperAdminAction.php',
        'app/Domain/Auth/Actions/GenerateRecoverySlipAction.php',
        'app/Domain/Auth/Actions/RedeemRecoverySlipAction.php',
        'app/Domain/Evaluation/Actions/EvaluateMentorAction.php',
        'app/Domain/Placement/Actions/DeletePlacementAction.php',
        'app/Domain/Placement/Actions/UpdatePlacementAction.php',
        'app/Domain/Placement/Actions/RejectPlacementChangeAction.php',
        'app/Domain/Placement/Actions/CreatePlacementAction.php',
        'app/Domain/Assessment/Actions/SchedulePresentationAction.php',
        'app/Domain/Assessment/Actions/CompletePresentationAction.php',
        'app/Domain/Assessment/Actions/ScorePresentationAction.php',
        'app/Domain/Placement/Actions/RequestPlacementChangeAction.php',
        'app/Domain/Placement/Actions/ApprovePlacementChangeAction.php',
    ];

    $baseDir = __DIR__;
    $modified = 0;
    $errors = [];

    foreach ($files as $relative) {
        $path = $baseDir.'/'.$relative;
        if (! file_exists($path)) {
            echo "NOT FOUND: $relative\n";

            continue;
        }

        $original = file_get_contents($path);
        $content = $original;

        $isCommand = str_contains($relative, 'Console/Commands');

        if (! str_contains($content, '$this->logAudit->execute')) {
            echo "SKIP (no logAudit): $relative\n";

            continue;
        }

        try {
            if ($isCommand) {
                $content = addSmartLoggerUse($content);
                $content = replaceAllLogAuditCalls($content, 'SmartLogger');
                $content = removeEmptyConstructor($content);
            } else {
                $content = addActionImport($content);
                $content = addExtendsAction($content);
                $content = replaceAllLogAuditCalls($content, 'log');
                $content = removeEmptyConstructor($content);
                $content = cleanupDBImport($content);
            }
        } catch (Throwable $e) {
            $errors[] = "$relative: ".$e->getMessage();
            echo "ERROR: $relative - ".$e->getMessage()."\n";

            continue;
        }

        if ($content !== $original) {
            file_put_contents($path, $content);
            $modified++;
            echo "MODIFIED: $relative\n";
        } else {
            echo "UNCHANGED: $relative\n";
        }
    }

    echo "\n--- Summary ---\n";
    echo "Modified: $modified\n";
    if ($errors) {
        echo 'Errors: '.count($errors)."\n";
        foreach ($errors as $e) {
            echo "  - $e\n";
        }
    }
}

// ====== Helper Functions ======

function addActionImport(string $content): string
{
    if (str_contains($content, 'use App\Domain\Core\Actions\Action;')) {
        return $content;
    }

    $actionUse = 'use App\Domain\Core\Actions\Action;';

    if (preg_match_all('/^(use App\\\\Domain[^;]+;)$/m', $content, $matches)) {
        $domainUses = $matches[1];
        $insertBefore = null;
        foreach ($domainUses as $use) {
            if (strcmp($use, $actionUse) > 0) {
                $insertBefore = $use;
                break;
            }
        }

        if ($insertBefore === null) {
            $content = str_replace(end($domainUses), end($domainUses)."\n".$actionUse, $content);
        } else {
            $content = str_replace($insertBefore, $actionUse."\n".$insertBefore, $content);
        }
    } else {
        $content = preg_replace(
            '/^(namespace .+;)$/m',
            "$1\n\n".$actionUse,
            $content
        );
    }

    return $content;
}

function addExtendsAction(string $content): string
{
    if (preg_match('/^class \w+ extends /m', $content)) {
        return $content;
    }

    return preg_replace(
        '/^(class \w+Action)\s*$/m',
        '$1 extends Action',
        $content
    );
}

function removeEmptyConstructor(string $content): string
{
    $content = preg_replace(
        '/^\s+public function __construct\(\s*\)\s*\{\s*\}\s*\n+/m',
        '',
        $content
    );
    $content = preg_replace('/\n{3,}/m', "\n\n", $content);

    return $content;
}

function cleanupDBImport(string $content): string
{
    preg_match_all('/DB::(\w+)/', $content, $dbCalls);
    $otherUses = array_filter($dbCalls[1], fn ($u) => $u !== 'transaction');

    if (! empty($otherUses)) {
        return $content;
    }

    $content = preg_replace(
        '/^use Illuminate\\\\Support\\\\Facades\\\\DB;\s*\n/m',
        '',
        $content
    );

    if (str_contains($content, 'extends Action')) {
        $content = str_replace('DB::transaction(', '$this->transaction(', $content);
    }

    return $content;
}

function addSmartLoggerUse(string $content): string
{
    if (str_contains($content, 'use App\Domain\Core\Support\SmartLogger;')) {
        return $content;
    }

    $smartLoggerUse = 'use App\Domain\Core\Support\SmartLogger;';

    if (preg_match_all('/^(use [^;]+;)$/m', $content, $matches)) {
        $lastUse = end($matches[1]);
        $content = str_replace($lastUse, $lastUse."\n".$smartLoggerUse, $content);
    } else {
        $content = preg_replace(
            '/^(namespace .+;)$/m',
            "$1\n\n".$smartLoggerUse,
            $content
        );
    }

    return $content;
}

function replaceAllLogAuditCalls(string $content, string $mode): string
{
    $result = '';
    $pos = 0;
    $len = strlen($content);

    while ($pos < $len) {
        $start = strpos($content, '$this->logAudit->execute(', $pos);
        if ($start === false) {
            $result .= substr($content, $pos);
            break;
        }

        // Copy everything before the call
        $result .= substr($content, $pos, $start - $pos);

        // Find the matching close: paren then semicolon
        $parenStart = $start + strlen('$this->logAudit->execute(');
        $depth = 1;
        $i = $parenStart;
        while ($i < $len && $depth > 0) {
            $c = $content[$i];
            if ($c === '(') {
                $depth++;
            } elseif ($c === ')') {
                $depth--;
            }
            $i++;
        }
        // $i is now after the closing paren

        // Expect semicolon
        if ($i < $len && $content[$i] === ';') {
            $i++;
        }

        // Extract the argument block (content between parens, exclusive)
        // After consuming ), $i = Q + 1. After consuming ;, $i = Q + 2.
        // We want content from $parenStart (P+1) to Q-1.
        // Length = (Q-1) - (P+1) + 1 = Q - P - 1
        // Q = $i - 2, P = $parenStart - 1
        // Length = ($i - 2) - ($parenStart - 1) - 1 = $i - $parenStart - 2
        $blockLen = $i - $parenStart - 2;
        $block = substr($content, $parenStart, $blockLen);

        // Detect indentation of the $this->logAudit->execute( call
        $callIndent = '';
        $lineStart = strrpos(substr($content, 0, $start), "\n");
        if ($lineStart !== false) {
            $callIndent = substr($content, $lineStart + 1, $start - $lineStart - 1);
        } else {
            $callIndent = str_repeat(' ', $start);
        }

        // Build replacement
        $replacement = buildReplacement($block, $mode, $callIndent);
        $result .= $replacement;

        $pos = $i;
    }

    return $result;
}

function buildReplacement(string $block, string $mode, string $callIndent): string
{
    $block = trim($block);
    $lines = explode("\n", $block);

    $args = parseTopLevelArgs($lines);

    $actionValue = $args['action'] ?? 'null';
    $subjectIdValue = $args['subjectId'] ?? null;
    $payloadValue = $args['payload'] ?? null;

    $modelVar = null;
    if ($subjectIdValue !== null) {
        if (preg_match('/^(\$[a-zA-Z_]\w*)/', $subjectIdValue, $m)) {
            $modelVar = $m[1];
        }
    }

    if ($mode === 'SmartLogger') {
        return buildSmartLoggerReplacement($actionValue, $modelVar, $payloadValue, $callIndent);
    }

    return buildLogReplacement($actionValue, $modelVar, $payloadValue, $callIndent);
}

function parseTopLevelArgs(array $lines): array
{
    // Find base indent from first non-empty line
    $baseIndent = null;
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        preg_match('/^(\s*)/', $line, $m);
        $baseIndent = strlen($m[1] ?? '');
        break;
    }

    if ($baseIndent === null) {
        return [];
    }

    $args = [];
    $currentName = null;
    $currentValue = '';
    $depth = 0;
    $inArg = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            if ($inArg) {
                $currentValue .= "\n";
            }

            continue;
        }

        preg_match('/^(\s*)/', $line, $m);
        $indent = strlen($m[1] ?? '');

        // Track bracket/paren depth across lines
        $depth += substr_count($trimmed, '[') - substr_count($trimmed, ']');
        $depth += substr_count($trimmed, '(') - substr_count($trimmed, ')');

        // New argument detection: at base indent, has colon, not inside brackets/parens
        $isNewArg = ($indent === $baseIndent && $depth <= 0 && str_contains($trimmed, ':'));

        if ($isNewArg) {
            // Save previous argument
            if ($currentName !== null) {
                $args[$currentName] = normalizeArgValue($currentValue);
            }

            $colonPos = strpos($trimmed, ':');
            $currentName = trim(substr($trimmed, 0, $colonPos));
            $currentValue = trim(substr($trimmed, $colonPos + 1));
            $inArg = true;
        } elseif ($inArg) {
            // Continuation of current argument
            $currentValue .= "\n".$line;
        }
    }

    // Save last argument
    if ($currentName !== null) {
        $args[$currentName] = normalizeArgValue($currentValue);
    }

    return $args;
}

function normalizeArgValue(string $value): string
{
    $value = trim($value);
    $value = rtrim($value, ',');

    return trim($value);
}

function buildLogReplacement(string $action, ?string $modelVar, ?string $payload, string $indent): string
{
    $parts = [];
    $parts[] = $action;
    $parts[] = $modelVar ?? 'null';
    if ($payload !== null) {
        $parts[] = reindentPayload($payload, $indent);
    }

    $argsStr = implode(', ', $parts);

    return $indent.'$this->log('.$argsStr.');';
}

function buildSmartLoggerReplacement(string $action, ?string $modelVar, ?string $payload, string $indent): string
{
    $lines = [];
    $lines[] = $indent.'SmartLogger::info('.$action.')';
    $lines[] = $indent.'    ->event('.$action.')';
    if ($modelVar !== null) {
        $lines[] = $indent.'    ->about('.$modelVar.')';
    }
    if ($payload !== null) {
        $lines[] = $indent.'    ->withPayload('.reindentPayload($payload, $indent.'    ').')';
    }
    $lines[] = $indent.'    ->activityOnly()';
    $lines[] = $indent.'    ->save();';

    return implode("\n", $lines);
}

function reindentPayload(string $payload, string $baseIndent): string
{
    $lines = explode("\n", $payload);
    if (count($lines) <= 1) {
        return $payload;
    }

    $result = [];
    foreach ($lines as $i => $line) {
        if ($i === 0) {
            $result[] = $line;
        } else {
            $trimmed = ltrim($line);
            if ($trimmed === '' || $trimmed === ']' || $trimmed === '],' || $trimmed === '})') {
                $result[] = $baseIndent.$trimmed;
            } else {
                $result[] = $baseIndent.'    '.$trimmed;
            }
        }
    }

    return implode("\n", $result);
}
