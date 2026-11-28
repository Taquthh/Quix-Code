#!/usr/bin/env bash
# T0-07: label, milestone, dan branch protection via GitHub CLI.
# Pemakaian: bash scripts/github-setup.sh OWNER/REPO
set -euo pipefail
REPO="${1:?Pemakaian: github-setup.sh OWNER/REPO}"

label() { gh label create "$1" --color "$2" --description "$3" --repo "$REPO" --force; }

for i in 0 1 2 3 4 5 6 7 8; do label "stage:$i" "0e8a16" "Tahap $i"; done
for t in feature bug security docs; do label "type:$t" "1d76db" "Jenis: $t"; done
for p in must should could; do label "prio:$p" "fbca04" "Prioritas: $p"; done
for a in admin portal webhook billing i18n; do label "area:$a" "5319e7" "Area: $a"; done
label "blocked" "b60205" "Terblokir oleh tugas lain"

for i in 0 1 2 3 4 5 6 7 8; do
  gh api "repos/$REPO/milestones" -f title="Tahap $i" -f description="Tugas T$i-xx pada blueprint" >/dev/null || true
done

gh api -X PUT "repos/$REPO/branches/main/protection" --input - <<JSON
{
  "required_status_checks": { "strict": true, "contexts": ["qa", "security"] },
  "enforce_admins": true,
  "required_pull_request_reviews": { "required_approving_review_count": 0 },
  "restrictions": null,
  "allow_force_pushes": false,
  "allow_deletions": false
}
JSON
echo "Selesai untuk $REPO"
