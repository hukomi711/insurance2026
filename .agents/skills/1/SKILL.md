---
name: 1
description: Workspace guidance for the insurance2026 Laravel application. Use for debugging, feature work, deployment, and Docker-related tasks in this repository.
---

# Insurance2026 Workspace Guidance

Use this skill when working in this repository.

## Project context

- This is a Laravel application with a Vite/Vue frontend and Docker-based deployment.
- Important deployment rules: do not run `php artisan config:cache` on this stack.
- Keep the application environment file readable by the container user to avoid runtime null values.

## Working rules

- Prefer minimal, targeted changes.
- Verify behavior with relevant tests or smoke checks before finishing.
- For PHP changes, rebuild or restart the affected container when needed.
- For asset-only changes, avoid unnecessary container rebuilds.

## Accuracy and verification rules

- Do not confuse reading a file with editing it.
- Do not claim a file was changed unless you executed a real write/edit operation in the current task.
- Do not treat `git diff` or `git status` as proof that you authored a change in this turn.
- `git diff` answers: "What differs from the Git reference right now?"
- It does not answer: "What did I change in this session?"
- If changes existed before your work, describe them as pre-existing and do not attribute them to yourself.
- If you did not make a real edit, say: "I have not made any file changes yet."
- If you did make a real edit, confirm it by rereading the file or checking the resulting diff.

## Typical tasks

- Investigate Laravel or Docker issues.
- Help with deployment or environment fixes.
- Implement or adjust features in the app.
- Review changes for correctness and safety.
