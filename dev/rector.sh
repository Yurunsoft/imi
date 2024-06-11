#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)
cd "$__DIR__/.."
__DIR__=$(pwd)

components=(
#  "core" 默认不处理
  "amqp"
  "apidoc"
  "fpm"
  "grpc"
  "jwt"
  "kafka"
  "mqtt"
  "pgsql"
  "queue"
  "rate-limit"
  "roadrunner"
  "rpc"
  "shared-memory"
  "smarty"
  "snowflake"
  "swoole"
  "swoole-tracker"
  "workerman"
  "workerman-gateway"
  "connection-center"
  "database"
  "model"
  "redis"
)

analyze_component() {
  component="$1"
  use_dry_run="$2"
  original_dir=$(pwd)
  echo "process: $component, dry-run: $use_dry_run"

  analyse_configuration=""

  args=()

  if [ "$component" != "core" ]; then
    cd "$__DIR__/src/Components/$component"
  fi

  if [ "$use_dry_run" == "true" ]; then
    args+=("--dry-run")
  fi

  "$__DIR__/vendor/bin/rector" process "${args[@]}"

  cd "$original_dir"
  sleep 1
}

use_dry_run="false"
input_components=()

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run)
      use_dry_run="true"
      shift
      ;;
    -n)
      use_dry_run="true"
      shift
      ;;
    *)
      # 如果不是 --dry-run 标志，将参数添加到 components 数组中
      input_components+=("$1")
      shift
      ;;
  esac
done

if [ ${#input_components[@]} -eq 0 ]; then
  # If no arguments are provided, analyze all components
  for component in "${components[@]}"; do
    analyze_component "$component" "$use_dry_run"
  done
else
  # Analyze the specified components provided as arguments
  for component in "${input_components[@]}"; do
    if [[ " ${components[@]} " =~ " $component " || "core" == "$component" ]]; then
      analyze_component "$component" "$use_dry_run"
    else
      echo "Invalid component name: $component"
    fi
  done
fi