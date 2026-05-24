# Commit and Deploy

This repository does not allow shell execution from the editor agent, so this file contains the exact commands to run locally in `D:\insurance2026`.

## Recommended steps

1. Review your current changes:

```bash
cd /d D:\insurance2026
git status --short
```

2. Stage safe files only:

```bash
git add -A -- ':!insurance2026-vps.tar.gz' ':!.env' ':!.env.production' ':!docker/secrets/*'
```

3. Commit with a descriptive message:

```bash
git commit -m "chore: add custom workspace agent and finalize deploy changes"
```

4. Push the branch upstream:

```bash
git push origin HEAD
```

5. Deploy using the production script:

```bash
./deploy-prod.sh
```

If `deploy-prod.sh` is not the correct deployment script for your current environment, use one of these instead:

```bash
./deploy-local-direct.sh
# or
./deploy-extract-and-build.sh
```

## Notes

- Make sure your SSH key is installed and working before running the deploy script.
- If the deploy script requires environment values or a specific branch, set them before running it.
- Do not commit deployment archives, secrets, or `.env` values unless they are intentionally part of the repo and reviewed.
