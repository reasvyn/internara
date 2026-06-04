import os
import re

replacements = [
    # Namespaces and imports
    (r'App\\Domain\\Certification\\Aggregates\\Document', r'App\\Domain\\Document\\Aggregates\\OfficialDocument'),
    
    # Views and Blade references
    (r"view\('certification\.document\.", r"view('document.official-document."),
    (r"@include\('certification\.document\.", r"@include('document.official-document."),
    
    # Component aliases and routing references
    (r"'certification\.document\.", r"'document.official-document."),
    (r'"certification\.document\.', r'"document.official-document.'),
    (r'certification::document\.', r'document::official-document.'),
]

directories = [
    'app',
    'tests',
    'database',
    'bootstrap',
    'routes',
    'config',
    'resources/views',
]

def replace_in_file(filepath):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
    except UnicodeDecodeError:
        return
        
    modified = content
    for pattern, repl in replacements:
        modified = re.sub(pattern, repl, modified)
        
    if modified != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(modified)
        print(f"Updated: {filepath}")

for d in directories:
    for root, dirs, files in os.walk(d):
        for file in files:
            if file.endswith(('.php', '.blade.php', '.json', '.md')):
                replace_in_file(os.path.join(root, file))
