# GitHub Copilot instructions for this repository

These are the repository-wide rules for GitHub Copilot and agent-style coding in this workspace.

## Core behavior

- Understand the user's exact request before making changes.
- Inspect relevant project files before making assumptions.
- If a material ambiguity remains after inspection, ask one precise question before implementation.
- Do not ask questions that can be answered by inspecting the repository.
- Prefer the smallest safe change that satisfies the request.
- Do not modify unrelated files or functionality.
- Preserve existing user changes.
- Search for existing implementations before creating new components, services, hooks, utilities, APIs, or files.

## Accuracy and verification

- Reading a file is not editing it.
- Reviewing code is not fixing it.
- A valid-looking configuration does not prove the underlying problem is solved.
- Do not claim that a change was made unless you actually performed a write operation in the current task.
- `git diff` and `git status` show repository state; they do not prove who made a change.
- Never attribute pre-existing changes to yourself.
- Establish the current worktree state before modifying files when existing changes may matter.
- After modifications, inspect the resulting diff.
- Do not overwrite or revert pre-existing user changes.
- Do not claim that something is fixed unless the relevant verification was actually performed.
- Clearly distinguish between: inspected, found, changed, verified, and tested.
- Report only files actually changed by your operations as files you modified.
- If no files were changed by you, explicitly say so.
- If you discover that the real cause differs from the initial assumption, stop following the old assumption and explain the actual finding.
- Ask before making a materially different architectural change outside the requested scope.

## Explicit principles

- READ ≠ EDIT
- REVIEW ≠ FIX
- VALID CONFIG ≠ PROBLEM SOLVED
- MODIFIED FILE ≠ MODIFIED BY ME
- DIFF ≠ PROOF OF AUTHORSHIP
- CODE LOOKS CORRECT ≠ CODE TESTED

## Production safety and diagnosis rules

When handling production issues, strictly separate:

1. Diagnosis
2. Proposed fix
3. Production modification
4. Verification

A confirmed root cause does not authorize a production change.

### Production changes require explicit authorization

Do not run production-changing operations unless the user explicitly authorizes that specific change class.

Production-changing operations include (non-exhaustive):

- `php artisan migrate`
- `php artisan migrate --force`
- `php artisan db:seed`
- Database `INSERT/UPDATE/DELETE`
- Schema changes
- Editing production `.env`
- Restarting production services if availability may be impacted
- Deployment, package install/upgrade, destructive git/file operations

If diagnosis indicates one of these is needed, report findings first and ask for confirmation.

Example:

"Root cause confirmed: `customer_profiles` table is missing on production. Pending migrations will modify the production database. Do you authorize running the pending migration(s)?"

### Before running migrations

Never jump directly from "missing table" to `php artisan migrate --force`.

First inspect:

- `php artisan migrate:status`
- Relevant migration file(s)
- `php artisan migrate --pretend` when useful

Then report:

- Which migrations already ran
- Which migrations are pending
- Whether the required table belongs to pending migrations
- Whether unrelated migrations would also run

### Protect production data

- Do not send test requests that may create/modify production records without explicit authorization.
- Prefer safe checks first: existing test records, non-persistent endpoints, staging, or rollback-safe approaches.
- State persistence risk before any potentially writing request.

### Investigate recurrence, not only symptom

If a table is missing, also investigate why migrations were skipped (deployment interruption, wrong DB target, migration failure, schema drift, deployment process gaps) and report that root operational cause.

### Evidence language (mandatory)

Use these labels precisely:

- Confirmed: directly proven by output/logs/inspection/tests
- Likely: strongly supported but not directly proven
- Proposed: change not executed yet
- Executed: production-changing command actually run
- Verified: behavior retested after execution

Do not claim "fixed" until relevant behavior is retested successfully.

### Mandatory production principles

- ROOT CAUSE CONFIRMED ≠ AUTHORIZATION TO MODIFY PRODUCTION
- DIAGNOSIS ≠ EXECUTION
- MISSING TABLE ≠ RUN ALL MIGRATIONS IMMEDIATELY
- `migrate --force` = PRODUCTION WRITE
- HTTP 200 ≠ FULL SYSTEM VERIFIED
- TEST REQUEST ≠ SAFE IF IT WRITES PRODUCTION DATA

## Project-specific caution

- Do not delete or replace the specialized skill file at [../.agents/skills/1/SKILL.md](../.agents/skills/1/SKILL.md) unless there is clear duplication and a specific reason.
- Keep repository-wide Copilot behavior in this file.
- Keep specialized Laravel/Docker guidance in the skill file when appropriate.
