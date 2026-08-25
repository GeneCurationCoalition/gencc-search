#!/usr/bin/env bash
set -Eeuo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly DEFAULT_SEARCH_REPO="$(cd "${SCRIPT_DIR}/../../../.." && pwd)"

environment="staging"
build_target=""
search_image=""
sub_image=""
db_mode="migrate_only"
restore_source=""
restore_force="false"
confirm_restore=""
confirm_production=""
execute="false"
search_repo="${DEFAULT_SEARCH_REPO}"
sub_repo="$(cd "${DEFAULT_SEARCH_REPO}/.." && pwd)/gencc-sub"
ansible_dir="$(cd "${DEFAULT_SEARCH_REPO}/.." && pwd)/gencc-sub/deployment/ansible"

usage() {
  cat <<'USAGE'
Usage:
  deploy-gencc-apps.sh --build search|sub|both|none [options]

Options:
  --environment staging|production       Target environment (default: staging)
  --build search|sub|both|none            Required explicit build selection
  --search-image IMAGE                    Search build target or existing image
  --sub-image IMAGE                       Sub build target or existing image
  --db-mode migrate_only|restore_and_migrate
                                          Database mode (default: migrate_only)
  --restore-source URL                    Required .sql.gz URL for staging restore
  --restore-force                         Required destructive-restore switch
  --confirm-restore RESTORE               Required typed staging restore confirmation
  --confirm-production PRODUCTION         Required after fresh human production approval
  --execute                               Build, push, and deploy; otherwise preview only
  --search-repo PATH                      Override gencc-search repository
  --sub-repo PATH                         Override gencc-sub repository
  --ansible-dir PATH                      Override Ansible directory
  -h, --help                              Show this help

The full playbook restarts both containers and runs gencc-sub migrations even
when only one image changes.
USAGE
}

die() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

need_value() {
  [[ $# -ge 2 && -n "${2:-}" ]] || die "$1 requires a value"
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --environment) need_value "$@"; environment="$2"; shift 2 ;;
    --build) need_value "$@"; build_target="$2"; shift 2 ;;
    --search-image) need_value "$@"; search_image="$2"; shift 2 ;;
    --sub-image) need_value "$@"; sub_image="$2"; shift 2 ;;
    --db-mode) need_value "$@"; db_mode="$2"; shift 2 ;;
    --restore-source) need_value "$@"; restore_source="$2"; shift 2 ;;
    --restore-force) restore_force="true"; shift ;;
    --confirm-restore) need_value "$@"; confirm_restore="$2"; shift 2 ;;
    --confirm-production) need_value "$@"; confirm_production="$2"; shift 2 ;;
    --execute) execute="true"; shift ;;
    --search-repo) need_value "$@"; search_repo="$2"; shift 2 ;;
    --sub-repo) need_value "$@"; sub_repo="$2"; shift 2 ;;
    --ansible-dir) need_value "$@"; ansible_dir="$2"; shift 2 ;;
    -h|--help) usage; exit 0 ;;
    *) die "unknown argument: $1" ;;
  esac
done

[[ "$environment" == "staging" || "$environment" == "production" ]] || die "--environment must be staging or production"
[[ "$build_target" == "search" || "$build_target" == "sub" || "$build_target" == "both" || "$build_target" == "none" ]] || die "--build must explicitly be search, sub, both, or none"
[[ "$db_mode" == "migrate_only" || "$db_mode" == "restore_and_migrate" ]] || die "--db-mode must be migrate_only or restore_and_migrate"

if [[ "$environment" == "production" && "$db_mode" == "restore_and_migrate" ]]; then
  die "restore_and_migrate is blocked for production; use the separate manual emergency restoration procedure"
fi

if [[ "$db_mode" == "restore_and_migrate" ]]; then
  [[ "$environment" == "staging" ]] || die "restore_and_migrate is staging-only"
  [[ "$restore_source" =~ ^(https://|gs://).+\.sql\.gz$ ]] || die "--restore-source must be an https:// or gs:// .sql.gz URL"
  [[ "$restore_force" == "true" ]] || die "restore_and_migrate requires --restore-force"
  [[ "$confirm_restore" == "RESTORE" ]] || die "restore_and_migrate requires --confirm-restore RESTORE"
else
  [[ -z "$restore_source" && "$restore_force" == "false" && -z "$confirm_restore" ]] || die "restore arguments are valid only with --db-mode restore_and_migrate"
fi

builds_search="false"
builds_sub="false"
[[ "$build_target" == "search" || "$build_target" == "both" ]] && builds_search="true"
[[ "$build_target" == "sub" || "$build_target" == "both" ]] && builds_sub="true"

validate_repo() {
  local repo="$1" name="$2"
  [[ -d "$repo/.git" ]] || die "$name repository not found at $repo"
  git -C "$repo" diff --quiet -- || die "$name has tracked unstaged changes"
  git -C "$repo" diff --cached --quiet -- || die "$name has staged but uncommitted changes"
}

validate_image_ref() {
  local ref="$1" name="$2"
  [[ "$ref" =~ ^ghcr\.io/genecurationcoalition/gencc-(search|sub)(:([A-Za-z0-9_][A-Za-z0-9_.-]{0,127})|@sha256:[0-9a-f]{64})$ ]] || die "$name image must be a tagged or digest-pinned GenCC GHCR reference: $ref"
}

derive_image() {
  local repo="$1" app="$2" explicit="$3" branch version sha
  if [[ -n "$explicit" ]]; then
    validate_image_ref "$explicit" "$app"
    [[ "$explicit" == "ghcr.io/genecurationcoalition/gencc-${app}:"* ]] || die "$app build image must use the gencc-${app} repository and a tag"
    printf '%s\n' "$explicit"
    return
  fi
  branch="$(git -C "$repo" symbolic-ref --quiet --short HEAD || true)"
  if [[ "$branch" =~ ^release/([0-9]+\.[0-9]+\.[0-9]+)$ ]]; then
    version="${BASH_REMATCH[1]}"
    sha="$(git -C "$repo" rev-parse --short=7 HEAD)"
    printf 'ghcr.io/genecurationcoalition/gencc-%s:%s-rc-%s\n' "$app" "$version" "$sha"
    return
  fi
  die "$app is on '$branch'; pass an explicit --${app}-image tag when building outside release/X.Y.Z"
}

[[ "$builds_search" == "false" || -d "$search_repo/.git" ]] || die "search repository not found at $search_repo"
[[ "$builds_sub" == "false" || -d "$sub_repo/.git" ]] || die "sub repository not found at $sub_repo"
[[ "$builds_search" == "false" ]] || validate_repo "$search_repo" "gencc-search"
[[ "$builds_sub" == "false" ]] || validate_repo "$sub_repo" "gencc-sub"

if [[ "$builds_search" == "true" ]]; then
  search_image="$(derive_image "$search_repo" search "$search_image")"
fi
if [[ "$builds_sub" == "true" ]]; then
  sub_image="$(derive_image "$sub_repo" sub "$sub_image")"
fi

host="gencc-vm"
public_base="https://search-stage.thegencc.org"
[[ "$environment" == "production" ]] && host="gencc-prod-vm" && public_base="https://thegencc.org"

read_live_images() {
  local output
  command -v ssh >/dev/null || die "ssh is required"
  output="$(ssh -o BatchMode=yes "$host" 'sudo -iu gencc env XDG_RUNTIME_DIR=/run/user/$(id -u gencc) podman inspect --format "{{.Name}} {{.Config.Image}}" gencc-search gencc-sub')" || die "could not read live images from $host"
  current_search_image="$(awk '$1=="gencc-search" {print $2}' <<<"$output")"
  current_sub_image="$(awk '$1=="gencc-sub" {print $2}' <<<"$output")"
  [[ -n "$current_search_image" && -n "$current_sub_image" ]] || die "could not parse the live image pair from $host"
  validate_image_ref "$current_search_image" "current search"
  validate_image_ref "$current_sub_image" "current sub"
}

read_live_images
[[ -n "$search_image" ]] || search_image="$current_search_image"
[[ -n "$sub_image" ]] || sub_image="$current_sub_image"
validate_image_ref "$search_image" "search"
validate_image_ref "$sub_image" "sub"

printf '\nDeployment plan\n'
printf '  Environment:          %s\n' "$environment"
printf '  VM:                   %s\n' "$host"
printf '  Build target:         %s\n' "$build_target"
printf '  Current search image: %s\n' "$current_search_image"
printf '  Current sub image:    %s\n' "$current_sub_image"
printf '  Search image:         %s\n' "$search_image"
printf '  Sub image:            %s\n' "$sub_image"
printf '  Database mode:        %s\n' "$db_mode"
[[ -n "$restore_source" ]] && printf '  Restore source:       %s\n' "$restore_source"
printf '  Full-playbook impact: both containers restart; gencc-sub migrations run\n\n'

if [[ "$environment" == "production" ]]; then
  printf >&2 '%s\n' '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'
  printf >&2 '%s\n' '!! PRODUCTION IMPACT: BOTH LIVE APPLICATION CONTAINERS WILL RESTART. !!'
  printf >&2 '!! VM: %s\n' "$host"
  printf >&2 '!! Search: %s\n' "$search_image"
  printf >&2 '!! Sub: %s\n' "$sub_image"
  printf >&2 '!! Database: %s\n' "$db_mode"
  printf >&2 '%s\n\n' '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'
  [[ "$confirm_production" == "PRODUCTION" ]] || die "fresh human production approval is required, followed by --confirm-production PRODUCTION"
fi

if [[ "$execute" != "true" ]]; then
  printf 'DRY RUN: no image build, push, Ansible run, or live mutation performed.\n'
  exit 0
fi

command -v podman >/dev/null || die "podman is required for builds"
command -v jq >/dev/null || die "jq is required"
command -v curl >/dev/null || die "curl is required"
command -v ansible-playbook >/dev/null || die "ansible-playbook is required"
[[ -d "$ansible_dir" ]] || die "Ansible directory not found at $ansible_dir"

tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/deploy-gencc-apps.XXXXXX")"
cleanup() { rm -rf "$tmp_dir"; }
trap cleanup EXIT

image_repo_path() {
  local without_host="${1#ghcr.io/}"
  printf '%s\n' "${without_host%%[:@]*}"
}

image_reference_part() {
  local without_host="${1#ghcr.io/}"
  if [[ "$without_host" == *@* ]]; then
    printf '%s\n' "${without_host#*@}"
  else
    printf '%s\n' "${without_host##*:}"
  fi
}

ghcr_token() {
  local repo="$1"
  curl -fsSLG --data-urlencode "scope=repository:${repo}:pull" --data-urlencode 'service=ghcr.io' https://ghcr.io/token | jq -er '.token'
}

remote_manifest() {
  local ref="$1" repo reference token
  repo="$(image_repo_path "$ref")"
  reference="$(image_reference_part "$ref")"
  token="$(ghcr_token "$repo")" || return 1
  curl -fsSL \
    -H "Authorization: Bearer ${token}" \
    -H 'Accept: application/vnd.oci.image.manifest.v1+json, application/vnd.docker.distribution.manifest.v2+json, application/vnd.oci.image.index.v1+json, application/vnd.docker.distribution.manifest.list.v2+json' \
    "https://ghcr.io/v2/${repo}/manifests/${reference}"
}

remote_config() {
  local ref="$1" manifest repo token media digest child
  manifest="$(remote_manifest "$ref")" || return 1
  media="$(jq -r '.mediaType // ""' <<<"$manifest")"
  if [[ "$media" == *index* || "$media" == *manifest.list* ]]; then
    child="$(jq -er '.manifests[] | select(.platform.os == "linux" and .platform.architecture == "amd64") | .digest' <<<"$manifest" | head -n1)" || return 1
    repo="$(image_repo_path "$ref")"
    token="$(ghcr_token "$repo")" || return 1
    manifest="$(curl -fsSL -H "Authorization: Bearer ${token}" -H 'Accept: application/vnd.oci.image.manifest.v1+json, application/vnd.docker.distribution.manifest.v2+json' "https://ghcr.io/v2/${repo}/manifests/${child}")" || return 1
  fi
  digest="$(jq -er '.config.digest' <<<"$manifest")" || return 1
  repo="$(image_repo_path "$ref")"
  token="$(ghcr_token "$repo")" || return 1
  curl -fsSL -H "Authorization: Bearer ${token}" "https://ghcr.io/v2/${repo}/blobs/${digest}"
}

tag_of() {
  [[ "$1" == *:* ]] || return 1
  printf '%s\n' "${1##*:}"
}

verify_local_image() {
  local ref="$1" source="$2" revision="$3" version="$4"
  podman image inspect "$ref" | jq -e \
    --arg source "$source" --arg revision "$revision" --arg version "$version" \
    '.[0].Architecture == "amd64"
      and .[0].Os == "linux"
      and .[0].Labels["org.opencontainers.image.source"] == $source
      and .[0].Labels["org.opencontainers.image.revision"] == $revision
      and .[0].Labels["org.opencontainers.image.version"] == $version
      and (.[0].Labels["org.opencontainers.image.created"] | length > 0)' >/dev/null
}

verify_remote_image() {
  local ref="$1" source="$2" revision="$3" version="$4" config
  config="$(remote_config "$ref")" || die "could not read the remote GHCR config for $ref"
  jq -e --arg source "$source" --arg revision "$revision" --arg version "$version" \
    '.architecture == "amd64"
      and .os == "linux"
      and .config.Labels["org.opencontainers.image.source"] == $source
      and .config.Labels["org.opencontainers.image.revision"] == $revision
      and .config.Labels["org.opencontainers.image.version"] == $version
      and (.config.Labels["org.opencontainers.image.created"] | length > 0)' <<<"$config" >/dev/null \
    || die "remote architecture or OCI labels do not match for $ref"
}

build_and_push() {
  local repo="$1" app="$2" ref="$3" context source revision version created
  if remote_manifest "$ref" >/dev/null 2>&1; then
    die "refusing to overwrite existing GHCR tag: $ref"
  fi
  context="$tmp_dir/$app"
  mkdir -p "$context"
  git -C "$repo" archive HEAD | tar -x -C "$context"
  source="https://github.com/GeneCurationCoalition/gencc-${app}"
  revision="$(git -C "$repo" rev-parse HEAD)"
  version="$(tag_of "$ref")"
  created="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
  printf 'Building %s from committed HEAD %s\n' "$ref" "$revision"
  podman build --platform linux/amd64 \
    --build-arg "APP_VERSION=${version}" \
    --label "org.opencontainers.image.source=${source}" \
    --label "org.opencontainers.image.revision=${revision}" \
    --label "org.opencontainers.image.version=${version}" \
    --label "org.opencontainers.image.created=${created}" \
    --tag "$ref" "$context"
  verify_local_image "$ref" "$source" "$revision" "$version" || die "local architecture or OCI label verification failed for $ref"
  if remote_manifest "$ref" >/dev/null 2>&1; then
    die "refusing to overwrite GHCR tag created during the build: $ref"
  fi
  podman push "$ref"
  verify_remote_image "$ref" "$source" "$revision" "$version"
  printf 'Verified remote linux/amd64 image and OCI labels: %s\n' "$ref"
}

[[ "$builds_search" == "false" ]] || build_and_push "$search_repo" search "$search_image"
[[ "$builds_sub" == "false" ]] || build_and_push "$sub_repo" sub "$sub_image"

run_playbook() {
  local target_search="$1" target_sub="$2" target_mode="$3" target_source="${4:-}" target_force="${5:-false}"
  local inventory vault extra
  inventory="$ansible_dir/inventories/${environment}.ini"
  vault="$ansible_dir/gencc-staging-ansible-vault-passphrase.txt"
  [[ "$environment" == "production" ]] && vault="$ansible_dir/gencc-prod-ansible-vault-passphrase.txt"
  [[ -f "$inventory" ]] || die "inventory not found: $inventory"
  [[ -f "$vault" ]] || die "vault password file not found: $vault"
  extra="$(jq -cn \
    --arg search "$target_search" --arg sub "$target_sub" --arg mode "$target_mode" \
    --arg source "$target_source" --argjson force "$target_force" \
    '{gencc_search_image:$search,gencc_sub_image:$sub,gencc_db_bootstrap_mode:$mode,gencc_db_restore_source:$source,gencc_db_restore_force:$force}')"
  (
    cd "$ansible_dir"
    ANSIBLE_CONFIG="$ansible_dir/ansible.cfg" ansible-playbook \
      -i "$inventory" playbooks/site.yml --vault-password-file "$vault" --extra-vars "$extra"
  )
}

verify_remote_services() {
  local output search_active sub_active running_search running_sub
  output="$(ssh -o BatchMode=yes "$host" 'sudo -iu gencc env XDG_RUNTIME_DIR=/run/user/$(id -u gencc) bash -lc '\''systemctl --user is-active gencc-search.service; systemctl --user is-active gencc-sub.service; podman inspect --format "{{.Name}} {{.Config.Image}}" gencc-search gencc-sub'\''')" || return 1
  search_active="$(sed -n '1p' <<<"$output")"
  sub_active="$(sed -n '2p' <<<"$output")"
  running_search="$(awk '$1=="gencc-search" {print $2}' <<<"$output")"
  running_sub="$(awk '$1=="gencc-sub" {print $2}' <<<"$output")"
  [[ "$search_active" == "active" && "$sub_active" == "active" && "$running_search" == "$search_image" && "$running_sub" == "$sub_image" ]]
}

wait_for_health() {
  local attempt status
  for attempt in {1..12}; do
    status="$(curl -sS -o "$tmp_dir/health.body" -w '%{http_code}' "$public_base/-/healthz" || true)"
    if [[ "$status" == "200" && "$(tr -d '\r\n' <"$tmp_dir/health.body")" == "ok" ]]; then
      return 0
    fi
    sleep 5
  done
  return 1
}

verify_recovery() {
  local output status
  output="$(ssh -o BatchMode=yes "$host" 'sudo -iu gencc env XDG_RUNTIME_DIR=/run/user/$(id -u gencc) bash -lc '\''systemctl --user is-active gencc-search.service; systemctl --user is-active gencc-sub.service; podman inspect --format "{{.Name}} {{.Config.Image}}" gencc-search gencc-sub'\''')" || return 1
  [[ "$(sed -n '1p' <<<"$output")" == "active" && "$(sed -n '2p' <<<"$output")" == "active" ]] || return 1
  [[ "$(awk '$1=="gencc-search" {print $2}' <<<"$output")" == "$current_search_image" ]] || return 1
  [[ "$(awk '$1=="gencc-sub" {print $2}' <<<"$output")" == "$current_sub_image" ]] || return 1
  status="$(curl -sS -o "$tmp_dir/recovery-health.body" -w '%{http_code}' "$public_base/-/healthz" || true)"
  [[ "$status" == "200" && "$(tr -d '\r\n' <"$tmp_dir/recovery-health.body")" == "ok" ]]
}

rollback_once() {
  printf >&2 '\nDeployment verification failed. Attempting one rollback to:\n  Search: %s\n  Sub: %s\n' "$current_search_image" "$current_sub_image"
  if run_playbook "$current_search_image" "$current_sub_image" migrate_only "" false && verify_recovery; then
    printf >&2 'Rollback recovery verified. Stopping; inspect the deployment output and service logs.\n'
  else
    printf >&2 'Rollback did not verify. Immediate operator intervention is required on %s.\n' "$host"
  fi
  exit 1
}

printf 'Running %s Ansible deployment...\n' "$environment"
run_playbook "$search_image" "$sub_image" "$db_mode" "$restore_source" "$restore_force" || rollback_once
verify_remote_services || rollback_once
wait_for_health || rollback_once
printf 'Verified active services, deployed image references, and public health endpoint.\n'
printf '\nDeployment complete: %s\n' "$environment"
