import os
import re

replacements = [
    # Namespaces and imports
    (r'App\\Domain\\Admin', r'App\\Domain\\SysAdmin'),
    (r'App\\Domain\\Settings', r'App\\Domain\\SysAdmin'),
    (r'Tests\\Feature\\Admin', r'Tests\\Feature\\SysAdmin'),
    
    # Views and Blade references
    (r"view\('admin\.", r"view('sysadmin."),
    (r"view\('settings\.", r"view('sysadmin.setting."),
    (r"@include\('admin\.", r"@include('sysadmin."),
    (r"@include\('settings\.", r"@include('sysadmin.setting."),
    
    # Component aliases and routing references (e.g. x-admin:: to x-sysadmin::, administration:: layouts)
    (r"'admin\.", r"'sysadmin."),
    (r'"admin\.', r'"sysadmin.'),
    (r'administration::', r'sysadmin::'),
    (r'admin::', r'sysadmin::'),
    (r'settings::', r'sysadmin::'),
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
