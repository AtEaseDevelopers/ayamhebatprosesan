#!/usr/bin/env bash
#
# backup.sh — archive project files + MySQL/MariaDB dump and upload to
# Google Drive via a pre-configured rclone remote. Keeps the last N
# archives on the remote and prunes older ones.
#
# Usage:
#   1. Edit the CONFIG block below.
#   2. chmod +x laravel-backup.sh
#   3. ./laravel-backup.sh
#
# Requires: rclone (with a configured remote), mysqldump, tar, gzip.

set -euo pipefail
IFS=$'\n\t'

# ──────────────────────────────────────────────────────────────────────
# CONFIG — edit these before first run
# ──────────────────────────────────────────────────────────────────────

# Auto-detects the directory this script lives in as the project root.
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="ayamhebatprosesan.com"

# MySQL / MariaDB
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="ayamhebatprosesan"
DB_USER="ayamhebatprosesa"
DB_PASSWORD="KEjileU8RQRRu0O18swTUh4He"

# rclone destination — must be an existing remote + path.
# Run `rclone listremotes` to see configured remote names.
RCLONE_REMOTE="gdrive:Backups/${PROJECT_NAME}"

# Retention: how many most-recent archives to keep on the remote.
KEEP_LAST=3

# Local staging directory for building the archive.
STAGING_DIR="/tmp"

# Paths inside the project to exclude from the file archive.
EXCLUDES=(
    "node_modules"
    "vendor"
)

# ──────────────────────────────────────────────────────────────────────
# Implementation
# ──────────────────────────────────────────────────────────────────────

log() { printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }
die() { printf '[%s] ERROR: %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" >&2; exit 1; }

TS="$(date +%Y%m%d-%H%M%S)"
WORK="${STAGING_DIR}/${PROJECT_NAME}-${TS}"
BUNDLE="${STAGING_DIR}/${PROJECT_NAME}-${TS}.tar.gz"
DEFAULTS_FILE=""

cleanup() {
    local rc=$?
    [[ -n "$DEFAULTS_FILE" && -f "$DEFAULTS_FILE" ]] && rm -f "$DEFAULTS_FILE"
    [[ -d "$WORK" ]] && rm -rf "$WORK"
    [[ -f "$BUNDLE" ]] && rm -f "$BUNDLE"
    exit $rc
}
trap cleanup EXIT

# Pre-flight checks
for cmd in rclone mysqldump tar gzip; do
    command -v "$cmd" >/dev/null 2>&1 || die "$cmd not found in PATH"
done

REMOTE_NAME="${RCLONE_REMOTE%%:*}"
if ! rclone listremotes | grep -qx "${REMOTE_NAME}:"; then
    die "rclone remote '${REMOTE_NAME}:' is not configured. Run 'rclone config' first."
fi

[[ -d "$PROJECT_DIR" ]] || die "PROJECT_DIR does not exist: $PROJECT_DIR"

mkdir -p "$WORK"
log "Project: $PROJECT_DIR"
log "Staging: $WORK"
log "Remote:  $RCLONE_REMOTE"

# ── Database dump ────────────────────────────────────────────────────
# Pass credentials via a 0600 defaults-extra-file so the password is
# never visible in `ps`.
DEFAULTS_FILE="$(mktemp)"
chmod 600 "$DEFAULTS_FILE"
cat > "$DEFAULTS_FILE" <<EOF
[client]
host=${DB_HOST}
port=${DB_PORT}
user=${DB_USER}
password=${DB_PASSWORD}
EOF

DUMP_FILE="${WORK}/${DB_NAME}-${TS}.sql.gz"
log "Dumping database '${DB_NAME}' (incl. routines, triggers, events)…"
mysqldump \
    --defaults-extra-file="$DEFAULTS_FILE" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --events \
    --hex-blob \
    "$DB_NAME" \
    | gzip > "$DUMP_FILE"

rm -f "$DEFAULTS_FILE"
DEFAULTS_FILE=""

DUMP_SIZE="$(du -h "$DUMP_FILE" | cut -f1)"
ROUTINE_COUNT="$(gunzip -c "$DUMP_FILE" | grep -cE '^(CREATE PROCEDURE|CREATE FUNCTION|/\*!50003 CREATE.* PROCEDURE| /\*!50003 CREATE.* FUNCTION)' || true)"
log "Dump size: ${DUMP_SIZE} — stored procedures/functions captured: ${ROUTINE_COUNT}"

# ── Project file archive ─────────────────────────────────────────────
log "Archiving project files…"
TAR_EXCLUDES=()
for pattern in "${EXCLUDES[@]}"; do
    TAR_EXCLUDES+=(--exclude="$pattern")
done

FILES_ARCHIVE="${WORK}/${PROJECT_NAME}-files-${TS}.tar.gz"
tar "${TAR_EXCLUDES[@]}" \
    -czf "$FILES_ARCHIVE" \
    -C "$(dirname "$PROJECT_DIR")" "$(basename "$PROJECT_DIR")"

FILES_SIZE="$(du -h "$FILES_ARCHIVE" | cut -f1)"
log "Files archive size: ${FILES_SIZE}"

# ── Bundle dump + files into one tarball ─────────────────────────────
log "Bundling…"
tar -czf "$BUNDLE" -C "$WORK" \
    "$(basename "$DUMP_FILE")" \
    "$(basename "$FILES_ARCHIVE")"

BUNDLE_SIZE="$(du -h "$BUNDLE" | cut -f1)"
log "Bundle: $(basename "$BUNDLE") (${BUNDLE_SIZE})"

# ── Upload ───────────────────────────────────────────────────────────
log "Uploading to ${RCLONE_REMOTE}/ …"
rclone copy --progress "$BUNDLE" "${RCLONE_REMOTE}/"

if ! rclone lsf "${RCLONE_REMOTE}/" --include "$(basename "$BUNDLE")" | grep -q .; then
    die "Upload verification failed: $(basename "$BUNDLE") not found on remote."
fi
log "Upload verified."

# ── Retention: keep the last N archives ──────────────────────────────
log "Pruning to keep last ${KEEP_LAST} archives…"
mapfile -t REMOTE_ARCHIVES < <(
    rclone lsf "${RCLONE_REMOTE}/" --include "${PROJECT_NAME}-*.tar.gz" | sort
)

TOTAL="${#REMOTE_ARCHIVES[@]}"
if (( TOTAL > KEEP_LAST )); then
    DELETE_COUNT=$(( TOTAL - KEEP_LAST ))
    for (( i=0; i<DELETE_COUNT; i++ )); do
        old="${REMOTE_ARCHIVES[$i]}"
        log "  deleting old: ${old}"
        rclone deletefile "${RCLONE_REMOTE}/${old}"
    done
fi

KEPT="$(rclone lsf "${RCLONE_REMOTE}/" --include "${PROJECT_NAME}-*.tar.gz" | wc -l | tr -d ' ')"
log "Done. Uploaded: $(basename "$BUNDLE"). Archives retained on remote: ${KEPT}."

# ──────────────────────────────────────────────────────────────────────
# Cron example — run daily at 02:30 and append output to ~/backup.log:
#
#   30 2 * * * /path/to/project/backup.sh >> "$HOME/backup.log" 2>&1
#
# Edit your crontab with: crontab -e
# ──────────────────────────────────────────────────────────────────────
