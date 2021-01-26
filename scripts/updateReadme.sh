#!/usr/bin/env bash
now=$(date)
sed -i "" "2s/.*/*Last Updated: ${now}*/" README.md
