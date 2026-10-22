#!/usr/bin/env bash
set -euo pipefail

# Updating disposable email domains
echo "Updating disposable email domains list ..."
for i in {1..3}; do
    tmp="/tmp/disposable-domains.txt"
    domain="https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.txt"
    if curl -fsSLo "$tmp" "$domain" && mv "$tmp" /var/www/html/var/email/disposable-domains.txt; then
        echo "✓ Updated disposable domains list successfully"
        break
    else
        if [ $i -lt 3 ]; then
            echo "⚠ Attempt $i failed, retrying in 5 seconds..."
            sleep 5
        else
            echo "⚠ Could not update list, using existing version"
        fi
    fi
done
