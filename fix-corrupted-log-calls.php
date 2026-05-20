<?php

declare(strict_types=1);

$files = glob('app/Domain/*/Actions/*.php');
$commandFiles = ['app/Domain/Admin/Console/Commands/AutoInactivateAccounts.php'];
$allFiles = array_merge($files, $commandFiles);

$baseDir = __DIR__;
$fixed = 0;
$errors = [];
$skipped = [];

foreach ($allFiles as $relative) {
    $path = $baseDir.'/'.$relative;
    if (! file_exists($path)) {
        continue;
    }

    $original = file_get_contents($path);
    $content = $original;

    // Only process files with the corrupted pattern: $this->log('...',\n    subjectType:
    if (! preg_match('/\$this->log\(\s*\'[^\']*\'\s*,\s*\n\s+subjectType:/s', $content)) {
        continue;
    }

    try {
        $content = fixCorruptedLogCalls($content);
        $content = removeTrailingNullParens($content);
    } catch (Throwable $e) {
        $errors[] = "$relative: ".$e->getMessage();
        echo "ERROR: $relative - ".$e->getMessage()."\n";

        continue;
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        $fixed++;
        echo "FIXED: $relative\n";
    } else {
        echo "UNCHANGED: $relative\n";
    }
}

echo "\n--- Summary ---\n";
echo "Fixed: $fixed\n";
if ($errors) {
    echo 'Errors: '.count($errors)."\n";
}

function fixCorruptedLogCalls(string $content): string
{
    $result = '';
    $pos = 0;
    $len = strlen($content);

    while ($pos < $len) {
        // Find corrupted $this->log( with named args pattern
        $start = strpos($content, '$this->log(', $pos);
        if ($start === false) {
            $result .= substr($content, $pos);
            break;
        }

        // Copy everything before this call
        $result .= substr($content, $pos, $start - $pos);

        // Find the matching close paren for $this->log(
        $parenStart = $start + strlen('$this->log(');
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
        // $i is after the )

        // Look ahead to see if this is a corrupted call (has subjectType: inside)
        $peekEnd = min($i, $start + 300);
        $peek = substr($content, $start, $peekEnd - $start);

        if (! str_contains($peek, 'subjectType:')) {
            // Not a corrupted call, emit as-is up to after the )
            $result .= substr($content, $start, $i - $start);
            // Check if we need to consume a semicolon
            if ($i < $len && $content[$i] === ';') {
                $result .= ';';
                $pos = $i + 1;
            } else {
                $pos = $i;
            }

            continue;
        }

        // The block inside $this->log() - from $parenStart to just before )
        $blockLen = $i - $parenStart - 1;
        $block = substr($content, $parenStart, $blockLen);

        // Detect call indentation
        $lineStart = strrpos(substr($content, 0, $start), "\n");
        $callIndent = '';
        if ($lineStart !== false) {
            $callIndent = substr($content, $lineStart + 1, $start - $lineStart - 1);
        }

        // Check what follows the )
        $afterBlock = ltrim(substr($content, $i, 15));

        // Build the replacement
        $replacement = rebuildLogCall($block, $callIndent);
        $result .= $replacement;

        // Skip past the ) and any trailing garbage
        if (str_starts_with($afterBlock, ', null);')) {
            $pos = $i + strlen(', null);');
        } elseif (str_starts_with($afterBlock, ',null);')) {
            $pos = $i + strlen(',null);');
        } elseif (str_starts_with($afterBlock, ');')) {
            $pos = $i + strlen(');');
        } else {
            // Just skip to after the original )
            $pos = $i;
        }
    }

    return $result;
}

function rebuildLogCall(string $block, string $indent): string
{
    $lines = explode("\n", trim($block));

    // Extract action from first argument (positional value)
    $firstLine = trim($lines[0]);
    $firstLine = rtrim($firstLine, ',');
    $action = $firstLine !== '' ? $firstLine : 'null';

    // Extract model variable from subjectId
    $model = 'null';
    if (preg_match('/subjectId:\s*(\$[a-zA-Z_]\w*)/', $block, $m)) {
        $model = $m[1];
    }

    // Extract payload between payload: and module:
    $payload = null;
    $payloadStr = '';
    $collecting = false;
    $baseIndent = null;

    // Find base indent from first named arg (subjectType:, subjectId:, etc.)
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            continue;
        }
        // Named args have the pattern word:
        if (preg_match('/^[a-zA-Z_]\w*:/', $trimmed)) {
            preg_match('/^(\s*)/', $line, $m);
            $baseIndent = strlen($m[1]);
            break;
        }
    }
    // Fallback: use first line's indent
    if ($baseIndent === null) {
        preg_match('/^(\s*)/', $lines[0], $m);
        $baseIndent = strlen($m[1]);
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        preg_match('/^(\s*)/', $line, $m);
        $curIndent = strlen($m[1]);

        // Detect end of payload: module: at base indent, or subjectType: at base indent (no payload case)
        if ($collecting && $curIndent === $baseIndent && preg_match('/^(module|subjectType|maskPii):/', $trimmed)) {
            $payload = $payloadStr;
            $collecting = false;

            continue;
        }

        if (! $collecting && str_starts_with($trimmed, 'payload:')) {
            $collecting = true;
            $valuePart = trim(substr($trimmed, 8)); // after 'payload:'
            $valuePart = rtrim($valuePart, ',');

            if ($valuePart === '') {
                continue; // multi-line follows
            }
            if (str_starts_with($valuePart, '$')) {
                $payload = $valuePart;
                $collecting = false;

                continue;
            }
            // Check if array closes on same line
            $open = substr_count($valuePart, '[');
            $close = substr_count($valuePart, ']');
            if ($open === $close) {
                $payload = $valuePart;
                $collecting = false;

                continue;
            }
            $payloadStr = $valuePart;

            continue;
        }

        if ($collecting) {
            $payloadStr .= "\n".$line;
        }
    }
    if ($collecting) {
        $payload = $payloadStr;
    }

    // Clean trailing comma/newlines from payload
    if ($payload !== null) {
        $payload = trim($payload);
        $payload = rtrim($payload, ',');
    }

    // Re-indent multi-line payload
    if ($payload !== null && str_contains($payload, "\n")) {
        $payload = reindentPayload($payload, $indent);
    }

    $parts = [$action, $model];
    if ($payload !== null) {
        $parts[] = $payload;
    }

    return $indent.'$this->log('.implode(', ', $parts).');';
}

function removeTrailingNullParens(string $content): string
{
    // Remove any orphaned , null ) ; patterns
    return preg_replace('/,\s*null\s*\)\s*;/', ');', $content);
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
