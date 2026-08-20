#!/usr/bin/env bash
set -Eeuo pipefail

if [[ $# -lt 2 ]]; then
  echo "Usage: $0 <git_repository> <target_directory> [branch]" >&2
  exit 64
fi

repository="$1"
target_directory="$2"
branch="${3:-main}"

if [[ "$target_directory" != /* || "$target_directory" == "/" ]]; then
  echo "Le dossier cible doit être un chemin absolu et ne peut pas être /." >&2
  exit 64
fi

if [[ -e "$target_directory" && ! -d "$target_directory/.git" ]]; then
  echo "Le dossier cible existe mais n'est pas un dépôt Git." >&2
  exit 73
fi

if [[ -d "$target_directory/.git" ]]; then
  git -C "$target_directory" fetch --prune origin
  git -C "$target_directory" checkout "$branch"
  git -C "$target_directory" pull --ff-only origin "$branch"
else
  git clone --branch "$branch" --single-branch "$repository" "$target_directory"
fi

if [[ ! -f "$target_directory/.env" ]]; then
  cp "$target_directory/.env.example" "$target_directory/.env"
  echo "Configuration créée dans $target_directory/.env"
fi

mkdir -p "$target_directory/storage/cache" "$target_directory/storage/logs" "$target_directory/storage/sessions" "$target_directory/storage/backups" "$target_directory/public/uploads"
chmod -R u+rwX,g+rwX "$target_directory/storage" "$target_directory/public/uploads"

if command -v composer >/dev/null 2>&1; then
  composer install --working-dir="$target_directory" --no-dev --prefer-dist --no-interaction --optimize-autoloader
fi

echo "Déploiement terminé. Configurez .env puis ouvrez /install dans le navigateur."

