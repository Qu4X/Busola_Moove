#!/usr/bin/env python3
"""
sync_upstream.py - Synchronize upstream Moove changes into theme_ebusola.

Usage:
    python scripts/sync_upstream.py
"""

import os

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

EXTENSIONS = {".php", ".mustache", ".js", ".map", ".scss", ".xml", ".txt", ".md"}

EXCLUDE_DIRS = {".git", "temp", "scripts", "node_modules"}

REPLACEMENTS = [
    ("theme_moove/moove/", "theme_ebusola/ebusola/"),
    ("theme_moove/moove", "theme_ebusola/ebusola"),
    ("theme_moove", "theme_ebusola"),
    ("theme_moove_", "theme_ebusola_"),
    ("/theme/moove/", "/theme/ebusola/"),
    ("is_moove_theme", "is_ebusola_theme"),
    ("theme->name === 'moove'", "theme->name === 'ebusola'"),
    ("'moove', $theme->parents", "'ebusola', $theme->parents"),
    ("filtervalues->theme[] = 'moove'", "filtervalues->theme[] = 'ebusola'"),
    ("themesettingmoove", "themesettingebusola"),
    ("theme_config::load('moove')", "theme_config::load('ebusola')"),
    ("\\theme_config::load('moove')", "\\theme_config::load('ebusola')"),
]

def main():
    print(f"Scanning {REPO_ROOT} for upstream theme_moove references...")
    updated_count = 0

    for root, dirs, files in os.walk(REPO_ROOT):
        # Exclude directories
        dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS]

        for file in files:
            ext = os.path.splitext(file)[1].lower()
            if ext not in EXTENSIONS:
                continue

            filepath = os.path.join(root, file)
            try:
                with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
                    content = f.read()

                modified = content
                for old_str, new_str in REPLACEMENTS:
                    modified = modified.replace(old_str, new_str)

                if modified != content:
                    with open(filepath, "w", encoding="utf-8", newline="\n") as f:
                        f.write(modified)
                    print(f"  [UPDATED] {os.path.relpath(filepath, REPO_ROOT)}")
                    updated_count += 1
            except Exception as e:
                print(f"  [ERROR] {filepath}: {e}")

    print(f"Sync complete. Updated {updated_count} files.")

if __name__ == "__main__":
    main()
