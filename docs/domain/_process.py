import re
import os

DOMAIN_DIR = "/home/reasnovynt/Projects/Dev/reasvyn/internara/docs/domain"

FILES = [
    "admin.md", "assessment.md", "assignment.md", "attendance.md", "auth.md",
    "certificate.md", "core.md", "document.md", "evaluation.md", "guidance.md",
    "incident.md", "internship.md", "logbook.md", "mentee.md", "mentor.md",
    "partnership.md", "placement.md", "registration.md", "schedule.md", "school.md",
    "settings.md", "setup.md", "shared.md", "user.md"
]

def find_line_with(lines, substr):
    for i, line in enumerate(lines):
        if substr in line:
            return i
    return None

def process_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    lines = content.split('\n')
    basename = os.path.basename(filepath)

    # ============================================================
    # STEP 1: Convert User Stories table to list format
    # ============================================================
    usr_idx = find_line_with(lines, '### User Stories & Rules')
    if usr_idx is not None:
        # Find the table header line after the heading
        scan = usr_idx + 1
        while scan < len(lines) and lines[scan].strip() == '':
            scan += 1
        if scan < len(lines) and '|' in lines[scan] and 'Role' in lines[scan]:
            # Found the table
            data_start = scan + 2  # skip header + separator
            data_end = data_start
            while data_end < len(lines) and lines[data_end].strip().startswith('|'):
                data_end += 1

            # Convert rows
            story_items = []
            for i in range(data_start, data_end):
                row = lines[i].strip()
                if row.startswith('|') and row.endswith('|'):
                    inner = row.strip('|').strip()
                    parts = [p.strip() for p in inner.split('|')]
                    if len(parts) >= 2:
                        role = parts[0]
                        story = '|'.join(parts[1:])
                        story_items.append(f"- **{role}:** {story}")

            # Replace table lines with list items
            lines = lines[:usr_idx + 1] + [''] + story_items + lines[data_end:]

    # ============================================================
    # STEP 1b: Ensure blank line before next heading after list
    # ============================================================
    result = '\n'.join(lines)
    result = re.sub(r'^(- \*\*.*)\n(##?)#', r'\1\n\n\2#', result, flags=re.MULTILINE)

    # ============================================================
    # STEP 2: Find orphaned bullets after Dependencies table
    # ============================================================
    lines = result.split('\n')

    dep_idx = find_line_with(lines, '## Dependencies')
    if dep_idx is None:
        with open(filepath, 'w') as f:
            f.write(result)
        print(f"  (no Dependencies in {basename})")
        return

    # Find the table start (first | line after the heading)
    tbl_start = dep_idx + 1
    while tbl_start < len(lines) and lines[tbl_start].strip() == '':
        tbl_start += 1

    if tbl_start >= len(lines) or '|' not in lines[tbl_start]:
        with open(filepath, 'w') as f:
            f.write(result)
        print(f"  (no dependency table in {basename})")
        return

    # Find table end: last | line (including continuation lines)
    tbl_last = tbl_start
    for i in range(tbl_start, len(lines)):
        s = lines[i].strip()
        if s.startswith('|'):
            tbl_last = i
        elif tbl_last > tbl_start and s == '':
            # Check next non-empty line
            nxt = None
            for j in range(i + 1, len(lines)):
                if lines[j].strip():
                    nxt = lines[j].strip()
                    break
            if nxt and nxt.startswith('- '):
                break  # next non-empty is a bullet - table done
            elif nxt and nxt.startswith('|'):
                continue  # table continues after blank
            elif nxt and not nxt.startswith('#') and not nxt.startswith('|'):
                # continuation
                tbl_last = i  # include this blank? no, blank line isn't part of table
                # Actually, just stop the table here
                break
            else:
                break
        elif tbl_last > tbl_start and s.startswith('- '):
            break
        elif tbl_last > tbl_start and not s.startswith('#') and not s.startswith('|') and s:
            # Continuation of a table cell (no leading |, but not a heading or blank)
            tbl_last = i
        elif tbl_last > tbl_start:
            break

    # Find bullet items after the table
    scan_from = tbl_last + 1
    # Skip trailing blank lines from table area
    while scan_from < len(lines) and lines[scan_from].strip() == '':
        scan_from += 1

    # Collect bullets (including continuation lines)
    bullet_line_indices = []
    for i in range(scan_from, len(lines)):
        s = lines[i].strip()
        if s.startswith('- '):
            bullet_line_indices.append(i)
        elif bullet_line_indices and s == '':
            # Blank line between bullets - keep
            bullet_line_indices.append(i)
        elif bullet_line_indices and not s.startswith('#') and not s.startswith('|'):
            # Continuation of a bullet
            bullet_line_indices.append(i)
        elif bullet_line_indices:
            break
        else:
            break

    if not bullet_line_indices:
        with open(filepath, 'w') as f:
            f.write(result)
        print(f"  (no orphaned bullets in {basename})")
        return

    # Trim trailing blank lines from bullets
    while bullet_line_indices and lines[bullet_line_indices[-1]].strip() == '':
        bullet_line_indices.pop()

    # The bullet lines to move
    bullets_text = '\n'.join(lines[i] for i in bullet_line_indices)

    # Remove bullets from Dependencies area
    first_bullet_idx = bullet_line_indices[0]
    last_bullet_idx = bullet_line_indices[-1]
    new_lines = lines[:first_bullet_idx] + lines[last_bullet_idx + 1:]

    # ============================================================
    # STEP 3: Insert bullets under User Stories section (after stories)
    # ============================================================
    usr_idx = find_line_with(new_lines, '### User Stories & Rules')

    if usr_idx is not None:
        # Find the end of the user stories list
        # The list consists of consecutive lines starting with "- " (either "- **" stories or "- " rules)
        list_end = usr_idx
        for i in range(usr_idx + 1, len(new_lines)):
            s = new_lines[i].strip()
            if s == '':
                continue  # skip blank lines
            if s.startswith('- '):
                list_end = i
            else:
                break  # reached the next section heading

        # Insert bullet lines after list_end
        insert_pos = list_end + 1

        # Ensure a blank line before insertion
        if insert_pos < len(new_lines) and new_lines[insert_pos].strip() != '':
            new_lines.insert(insert_pos, '')
            insert_pos += 1

        for bl_text in bullets_text.split('\n'):
            new_lines.insert(insert_pos, bl_text)
            insert_pos += 1

        # Ensure a blank line after bullets
        if insert_pos < len(new_lines) and new_lines[insert_pos].strip() != '':
            new_lines.insert(insert_pos, '')
    else:
        # No User Stories heading - create one before Dependencies
        dep_idx = find_line_with(new_lines, '## Dependencies')
        if dep_idx is not None:
            insert_pos = dep_idx
            new_lines.insert(insert_pos, '')
            new_lines.insert(insert_pos, '### User Stories & Rules')
            for bl_text in reversed(bullets_text.split('\n')):
                new_lines.insert(insert_pos + 1, bl_text)

    result = '\n'.join(new_lines)

    # ============================================================
    # STEP 4: Clean up excessive blank lines (4+ -> 2)
    # ============================================================
    result = re.sub(r'\n{4,}', '\n\n\n', result)

    with open(filepath, 'w') as f:
        f.write(result)

    print(f"Processed: {basename}")


for fname in FILES:
    fpath = os.path.join(DOMAIN_DIR, fname)
    if os.path.exists(fpath):
        process_file(fpath)
    else:
        print(f"NOT FOUND: {fname}")

print("\nDone!")
