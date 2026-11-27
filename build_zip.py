"""Build an installable WordPress plugin ZIP archive for MyProperties Listing Map."""

from pathlib import Path
import zipfile

EXCLUDED_NAMES = {".DS_Store", "Thumbs.db", "desktop.ini", "__pycache__", ".git"}
EXCLUDED_SUFFIXES = {".pyc", ".pyo"}


def should_include(path: Path) -> bool:
    """Return True if the file should be included in the plugin archive."""
    if not path.is_file():
        return False
    if any(part in EXCLUDED_NAMES for part in path.parts):
        return False
    return path.suffix.lower() not in EXCLUDED_SUFFIXES


def build_plugin_zip(plugin_dir: Path, output_zip: Path) -> int:
    """Package plugin_dir into output_zip with POSIX archive paths and return file count."""
    if not plugin_dir.is_dir():
        raise FileNotFoundError(f"Plugin directory not found: {plugin_dir}")

    root_dir = plugin_dir.parent
    files = sorted(p for p in plugin_dir.rglob("*") if should_include(p))

    with zipfile.ZipFile(output_zip, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        for file_path in files:
            arcname = file_path.relative_to(root_dir).as_posix()
            zf.write(file_path, arcname=arcname)

    return len(files)


def main() -> None:
    """Create myproperties-listing-map.zip alongside the plugin directory."""
    base_dir = Path(__file__).resolve().parent
    plugin_dir = base_dir / "myproperties-listing-map"
    output_zip = base_dir / "myproperties-listing-map.zip"

    count = build_plugin_zip(plugin_dir, output_zip)
    size_bytes = output_zip.stat().st_size
    print(f"Created {output_zip} ({count} files, {size_bytes} bytes)")


if __name__ == "__main__":
    main()
