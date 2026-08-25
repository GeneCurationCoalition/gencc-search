---
name: deploy-gencc-apps
description: Build and deploy GenCC Search and Submission Portal container images to staging or production with image preservation, database safeguards, verification, and rollback. Use for GenCC container releases and deployments; ordinary application development does not need this skill.
---

# Deploy GenCC Apps

Use `scripts/deploy-gencc-apps.sh` as the deployment entrypoint. Run it from any directory; it locates `gencc-search` from the skill path and expects the sibling `gencc-sub` repository unless overridden.

## Choose the operation

Always make the build selection explicit with `--build search|sub|both|none`. An unqualified deployment targets `staging`; never infer production.

- Selected applications are built from `git archive HEAD` after verifying that their tracked worktree and index are clean. Untracked files cannot enter the image.
- On a `release/X.Y.Z` branch, the default immutable image is `X.Y.Z-rc-<7-character-SHA>`. On another branch, pass an explicit image for every selected build.
- An explicit `--search-image` or `--sub-image` sets that application's image. Otherwise an unbuilt application's currently running image is preserved.
- The default database mode is `migrate_only`.
- The full Ansible playbook restarts both containers and runs `gencc-sub` migrations even when only one image changes.

Begin with a dry run (omit `--execute`) and review the displayed environment, VM, current images, resolved images, and database mode. Add `--execute` only when the displayed plan matches the user's request.

## Production approval boundary

Before any production build, push, or deployment, stop and show the human a prominent warning containing the exact production VM, resolved search and sub images, and database mode. Ask for fresh, explicit production approval. Earlier staging approval, a broad deployment request, or a saved command permission is not production approval.

Only after the human approves, rerun with both `--environment production` and `--confirm-production PRODUCTION`. Never supply or invent that confirmation on the human's behalf.

Production `restore_and_migrate` is blocked. Production restoration is a separate manual emergency procedure.

## Staging database restoration

Prefer `migrate_only`. Use `restore_and_migrate` only when the human explicitly requested a staging restore and supplied an `https://` or `gs://` `.sql.gz` source. It additionally requires all of:

```text
--db-mode restore_and_migrate
--restore-source <https://...sql.gz|gs://...sql.gz>
--restore-force
--confirm-restore RESTORE
```

This mode drops and recreates the staging database.

## Examples

Preview a stage-first search deployment while preserving the running sub image:

```bash
.agents/skills/deploy-gencc-apps/scripts/deploy-gencc-apps.sh --build search
```

Deploy two already-published images to staging:

```bash
.agents/skills/deploy-gencc-apps/scripts/deploy-gencc-apps.sh \
  --build none \
  --search-image ghcr.io/genecurationcoalition/gencc-search:2.2.0 \
  --sub-image ghcr.io/genecurationcoalition/gencc-sub:2.2.0 \
  --execute
```

The script refuses to overwrite a GHCR tag, builds `linux/amd64`, applies and verifies OCI source/revision/version/created labels, checks the remote manifest, runs Ansible with both image references, and verifies services and public endpoints. If deployment health verification fails, it makes one rollback attempt to the captured pre-deployment image pair, verifies basic recovery, and stops with diagnostics.

Use `--help` for repository overrides and the complete command interface.
