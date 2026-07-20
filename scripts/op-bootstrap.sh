#!/usr/bin/env bash
#
# op-bootstrap.sh — ON-DEMAND setup for 1Password CLI access.
#
# Run this manually only when you actually need to fetch credentials from the
# dedicated 1Password "Claude" vault (e.g. to compare the live Cloud86 shared24
# files against this repo, or to deploy). It is NOT a SessionStart hook and does
# NOT run automatically — that keeps credential handling out of every session.
#
#   Usage:  bash scripts/op-bootstrap.sh
#
# This script contains NO secrets. It relies on the service account token being
# provided as the environment variable OP_SERVICE_ACCOUNT_TOKEN, which must be
# configured as an *environment secret* in the Claude Code environment settings
# (web UI) — never committed to the repo and never pasted into chat.
#
# What it does (all idempotent, read-only):
#   1. Installs the `op` CLI if it is not already present.
#   2. Verifies the service account token can authenticate.
#   3. Lists vault names and item titles (no secret values) so the right
#      Cloud86 credential can be located.

set -uo pipefail

OP_VERSION="v2.30.3"
INSTALL_DIR="/usr/local/bin"

info()  { echo "[op-bootstrap] $*"; }
warn()  { echo "[op-bootstrap] WARN: $*" >&2; }
fail()  { echo "[op-bootstrap] ERROR: $*" >&2; exit 1; }

install_op() {
    if command -v op >/dev/null 2>&1; then
        info "op already installed: $(op --version 2>/dev/null)"
        return 0
    fi

    local arch url tmp
    case "$(uname -m)" in
        x86_64)        arch="amd64" ;;
        aarch64|arm64) arch="arm64" ;;
        *) fail "unsupported architecture: $(uname -m)" ;;
    esac

    url="https://cache.agilebits.com/dist/1P/op2/pkg/${OP_VERSION}/op_linux_${arch}_${OP_VERSION}.zip"
    tmp="$(mktemp -d)"
    trap 'rm -rf "${tmp}"' RETURN

    info "downloading op ${OP_VERSION} (${arch})..."
    curl -fsSL --max-time 60 -o "${tmp}/op.zip" "${url}" \
        || fail "download failed — the environment network policy may block cache.agilebits.com"
    unzip -o -q "${tmp}/op.zip" op -d "${tmp}" || fail "unzip failed"
    install -m 0755 "${tmp}/op" "${INSTALL_DIR}/op" \
        || fail "could not install op to ${INSTALL_DIR} (permissions?)"
    info "installed op -> ${INSTALL_DIR}/op ($(op --version 2>/dev/null))"
}

verify_and_list() {
    if [ -z "${OP_SERVICE_ACCOUNT_TOKEN:-}" ]; then
        warn "OP_SERVICE_ACCOUNT_TOKEN is not set."
        warn "Set it as an environment secret in the Claude Code environment settings, then re-run this script."
        exit 2
    fi

    info "authenticating service account (read-only)..."
    if ! op vault list --format=json >/dev/null 2>&1; then
        fail "authentication/vault list failed — check the token, the vault grant, and egress to my.1password.com"
    fi
    info "authentication OK. Vaults granted to this service account:"
    op vault list

    echo
    info "Item titles per vault (no secret values are shown):"
    # Iterate vaults and print item titles only, so the Cloud86 credential can be located.
    op vault list --format=json 2>/dev/null \
        | grep -oE '"name":[[:space:]]*"[^"]*"' \
        | sed -E 's/.*"name":[[:space:]]*"([^"]*)"/\1/' \
        | while IFS= read -r vault; do
              echo "  vault: ${vault}"
              op item list --vault "${vault}" 2>/dev/null \
                  | sed 's/^/    /' || true
          done

    echo
    info "Done. To fetch a specific field on demand (example), use:"
    info "  op read \"op://<vault>/<item>/<field>\""
    info "Nothing was written to disk; credentials are only read when you explicitly request them."
}

install_op
verify_and_list
